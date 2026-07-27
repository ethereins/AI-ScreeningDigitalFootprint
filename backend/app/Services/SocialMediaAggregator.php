<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SocialMediaAggregator
{
    protected $brightDataService;

    public function __construct(BrightDataService $brightDataService)
    {
        $this->brightDataService = $brightDataService;
    }

    public function collectPersonaData(string $name, array $usernames): array
    {
        $socialMedia = [];

        $platforms = [
            'instagram' => $usernames['instagram'] ?? null,
            'tiktok' => $usernames['tiktok'] ?? null,
            'twitter' => $usernames['twitter'] ?? null,
        ];

        foreach ($platforms as $platform => $username) {
            if ($username) {
                try {
                    $data = $this->fetchPlatformData($platform, $username);
                    if ($data) {
                        $socialMedia[] = $data;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to fetch {$platform}: " . $e->getMessage());
                    $socialMedia[] = $this->emptyPlatformData($platform);
                }
            } else {
                $socialMedia[] = $this->emptyPlatformData($platform);
            }
        }

        return [
            'persona' => [
                'name' => $name,
                'social_media' => $socialMedia,
            ],
        ];
    }

    protected function fetchPlatformData(string $platform, string $username): array
    {
        $posts = [];
        $profileData = [];

        try {
            $rawPosts = $this->brightDataService->scrapePosts($platform, $username, 20);

            if (!empty($rawPosts)) {
                foreach ($rawPosts as $post) {
                    $posts[] = [
                        'text' => $post['text'] ?? null,
                        'image_url' => $post['image_url'] ?? null,
                        'video_url' => $post['video_url'] ?? null,
                        'platform' => $platform,
                        'posted_at' => $post['posted_at'] ?? now()->toIso8601String(),
                        'likes' => $post['metadata']['likes'] ?? 0,
                        'comments' => $post['metadata']['comments'] ?? 0,
                        'post_id' => $post['post_id'] ?? null,
                        'url' => $post['url'] ?? null,
                    ];
                }

                if (!empty($rawPosts[0]['metadata'])) {
                    $profileData = $rawPosts[0]['metadata'];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Bright Data error for {$platform}/{$username}: " . $e->getMessage());
        }

        $profileUrl = $this->getProfileUrl($platform, $username);
        $fullName = $profileData['full_name'] ?? $username;
        $postCount = count($posts);

        return [
            'platform' => $platform,
            'post_count' => $postCount,
            'profile_url' => $profileUrl,
            'avatar_url' => null,
            'full_name' => $fullName,
            'username' => $username,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'posts' => $posts,
        ];
    }

    protected function emptyPlatformData(string $platform): array
    {
        return [
            'platform' => $platform,
            'post_count' => 0,
            'profile_url' => null,
            'avatar_url' => null,
            'full_name' => null,
            'username' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'posts' => [],
        ];
    }

    protected function getProfileUrl(string $platform, string $username): string
    {
        $urls = [
            'instagram' => "https://instagram.com/{$username}",
            'tiktok' => "https://tiktok.com/@{$username}",
            'twitter' => "https://twitter.com/{$username}",
        ];

        return $urls[$platform] ?? '';
    }
}
