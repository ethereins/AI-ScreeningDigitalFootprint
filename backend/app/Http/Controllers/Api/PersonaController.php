<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\PlatformProfile;
use App\Models\SocialPost;
use App\Services\SocialMediaAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaController extends Controller
{
    protected $aggregator;

    public function __construct(SocialMediaAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * 🔥 ENDPOINT UTAMA: Search + Scrape + Simpan + Output JSON
     * POST /api/persona/search
     */
    public function searchOrCreate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'instagram' => 'nullable|string',
            'tiktok' => 'nullable|string',
            'twitter' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1. Cari atau buat Persona
            $persona = Persona::where('name', $validated['name'])->first();

            if (!$persona) {
                $persona = Persona::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                ]);
            }

            // 2. Scrape data dari semua platform
            $usernames = [
                'instagram' => $validated['instagram'] ?? null,
                'tiktok' => $validated['tiktok'] ?? null,
                'twitter' => $validated['twitter'] ?? null,
            ];

            $scrapedData = $this->aggregator->collectPersonaData($validated['name'], $usernames);

            // 3. Simpan hasil scraping ke database
            foreach ($scrapedData['persona']['social_media'] as $platformData) {
                $this->savePlatformData($persona->id, $platformData);
            }

            DB::commit();

            // 4. Ambil data lengkap dari database dan format sesuai teman
            $result = $this->formatPersona($persona);

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process persona: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process persona: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan data per platform ke database
     */
    private function savePlatformData(int $personaId, array $platformData)
    {
        $platform = $platformData['platform'];
        $username = $platformData['username'] ?? $this->extractUsername($platformData['profile_url'] ?? '', $platform);

        // Simpan atau update Platform Profile
        $profile = PlatformProfile::updateOrCreate(
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

        // Simpan posts
        foreach ($platformData['posts'] ?? [] as $post) {
            $postUrl = $post['url'] ?? $post['post_url'] ?? null;
            if (!$postUrl && isset($post['post_id'])) {
                $postUrl = "https://{$platform}.com/p/{$post['post_id']}";
            }

            SocialPost::updateOrCreate(
                [
                    'persona_id' => $personaId,
                    'platform' => $platform,
                    'post_id' => $post['post_id'] ?? null,
                ],
                [
                    'username' => $username,
                    'url' => $postUrl,
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

    /**
     * 📖 Ambil data persona dari database (tanpa scrape)
     * GET /api/persona/{id}
     */
    public function show($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        }

        return response()->json($this->formatPersona($persona));
    }

    /**
     * 📖 Ambil data persona berdasarkan nama
     * GET /api/persona/name/{name}
     */
    public function findByName($name)
    {
        $persona = Persona::where('name', $name)->first();

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        }

        return response()->json($this->formatPersona($persona));
    }

    /**
     * 📖 Ambil data persona berdasarkan username social media
     * GET /api/persona/username/{username}
     */
    public function findByUsername($username)
    {
        $profile = PlatformProfile::where('username', $username)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Username not found'
            ], 404);
        }

        $persona = Persona::find($profile->persona_id);

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Persona not found'
            ], 404);
        }

        return response()->json($this->formatPersona($persona));
    }

    /**
     * 🎯 Format data persona sesuai struktur yang diminta teman
     */
    private function formatPersona($persona)
    {
        $profiles = PlatformProfile::where('persona_id', $persona->id)->get();

        $socialMedia = [];

        foreach ($profiles as $profile) {
            $posts = SocialPost::where('persona_id', $persona->id)
                ->where('platform', $profile->platform)
                ->orderBy('posted_at', 'desc')
                ->get();

            $socialMedia[] = [
                'platform' => $profile->platform,
                'username' => $profile->username,
                'email' => $persona->email,
                'post_count' => $posts->count(),
                'profile_url' => $profile->profile_url,
                'avatar_url' => $profile->avatar_url,
                'created_at' => $profile->created_at ? $profile->created_at->toIso8601String() : now()->toIso8601String(),
                'updated_at' => $profile->updated_at ? $profile->updated_at->toIso8601String() : now()->toIso8601String(),
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

        return [
            'persona' => [
                'name' => $persona->name,
                'social_media' => $socialMedia,
            ],
        ];
    }

    private function extractUsername(?string $url, string $platform): string
    {
        if (!$url) {
            return '';
        }

        $patterns = [
            'instagram' => '/instagram\.com\/([^\/?]+)/',
            'tiktok' => '/tiktok\.com\/@([^\/?]+)/',
            'twitter' => '/twitter\.com\/([^\/?]+)/',
        ];

        if (isset($patterns[$platform]) && preg_match($patterns[$platform], $url, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
