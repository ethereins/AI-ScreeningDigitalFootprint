<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\PlatformProfile;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaService
{
    protected $aggregator;

    public function __construct(SocialMediaAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    public function processPersona(string $name, array $usernames, ?string $email = null): array
    {
        DB::beginTransaction();

        try {
            // 1. Cari atau buat Persona
            $persona = Persona::updateOrCreate(
                ['name' => $name],
                ['email' => $email]
            );

            // 2. Scrape data
            $scrapedData = $this->aggregator->collectPersonaData($name, $usernames);

            // 3. Simpan ke database
            foreach ($scrapedData['persona']['social_media'] as $platformData) {
                $this->savePlatformData($persona->id, $platformData);
            }

            DB::commit();

            // 4. Format output
            return $this->formatPersona($persona);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process persona: " . $e->getMessage());
            throw $e;
        }
    }

    protected function savePlatformData(int $personaId, array $platformData)
    {
        $platform = $platformData['platform'];
        $username = $platformData['username'];

        if (!$username) {
            return;
        }

        // Simpan Profile
        PlatformProfile::updateOrCreate(
            [
                'persona_id' => $personaId,
                'platform' => $platform,
            ],
            [
                'username' => $username,
                'full_name' => $platformData['full_name'] ?? $username,
                'avatar_url' => $platformData['avatar_url'] ?? null,
                'profile_url' => $platformData['profile_url'] ?? null,
                'post_count' => count($platformData['posts'] ?? []),
                'last_scraped_at' => now(),
            ]
        );

        // Simpan Posts
        foreach ($platformData['posts'] ?? [] as $post) {
            SocialPost::updateOrCreate(
                [
                    'persona_id' => $personaId,
                    'platform' => $platform,
                    'post_id' => $post['post_id'] ?? null,
                ],
                [
                    'username' => $username,
                    'url' => $post['url'] ?? "https://{$platform}.com/p/{$post['post_id']}",
                    'text' => $post['text'] ?? null,
                    'image_url' => $post['image_url'] ?? null,
                    'video_url' => $post['video_url'] ?? null,
                    'posted_at' => $post['posted_at'] ?? now(),
                    'likes' => $post['likes'] ?? 0,
                    'comments' => $post['comments'] ?? 0,
                    'shares' => $post['shares'] ?? 0,
                    'profile_name' => $platformData['full_name'] ?? null,
                    'profile_url' => $platformData['profile_url'] ?? null,
                    'metadata' => $post,
                    'status' => 'processed',
                ]
            );
        }
    }

    public function formatPersona(Persona $persona): array
    {
        $persona->load(['profiles', 'posts']);

        $socialMedia = [];

        foreach ($persona->profiles as $profile) {
            $platform = $profile->platform;
            $posts = $persona->posts->where('platform', $platform);

            $socialMedia[] = [
                'platform' => $platform,
                'username' => $profile->username,
                'email' => $persona->email,
                'post_count' => $posts->count(),
                'profile_url' => $profile->profile_url,
                'avatar_url' => $profile->avatar_url,
                'created_at' => $profile->created_at->toIso8601String(),
                'updated_at' => $profile->updated_at->toIso8601String(),
                'posts' => $posts->map(function ($post) {
                    return [
                        'url' => $post->url ?? "https://{$post->platform}.com/p/{$post->post_id}",
                        'text' => $post->text,
                        'image_url' => $post->image_url,
                        'video_url' => $post->video_url,
                    ];
                })->values()->toArray(),
            ];
        }

        // Urutkan: instagram, tiktok, twitter
        $order = ['instagram', 'tiktok', 'twitter'];
        usort($socialMedia, function ($a, $b) use ($order) {
            return array_search($a['platform'], $order) - array_search($b['platform'], $order);
        });

        return [
            'persona' => [
                'name' => $persona->name,
                'social_media' => $socialMedia,
            ],
        ];
    }

    public function getPersonaById(int $id): ?array
    {
        $persona = Persona::find($id);
        if (!$persona) {
            return null;
        }
        return $this->formatPersona($persona);
    }

    public function getPersonaByName(string $name): ?array
    {
        $persona = Persona::where('name', $name)->first();
        if (!$persona) {
            return null;
        }
        return $this->formatPersona($persona);
    }
}
