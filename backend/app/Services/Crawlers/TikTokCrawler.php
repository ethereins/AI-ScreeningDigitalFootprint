<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;

class TikTokCrawler extends BaseCrawler
{
    public function __construct()
    {
        // TikTok API biasanya pakai RapidAPI atau unofficial API
        $this->apiKey = config('services.rapidapi.key');
        $this->baseUrl = 'https://tiktok-api23.p.rapidapi.com';
    }

    public function getPlatformName(): string
    {
        return 'tiktok';
    }

    public function validateCredentials(): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/user/info", [
                    'username' => 'tiktok'
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchPosts($username, $limit = 50): array
    {
        try {
            // Ambil user info
            $userResponse = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/user/info", [
                    'username' => $username
                ]);

            if (!$userResponse->successful()) {
                throw new \Exception("Failed to fetch user: " . $userResponse->body());
            }

            $userData = $userResponse->json();
            $userId = $userData['data']['user']['id'] ?? null;

            if (!$userId) {
                throw new \Exception("User ID not found");
            }

            // Ambil videos
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/user/posts", [
                    'user_id' => $userId,
                    'count' => min($limit, 30)
                ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch posts: " . $response->body());
            }

            $data = $response->json();
            $posts = [];

            foreach ($data['data']['videos'] ?? [] as $item) {
                $posts[] = $this->parsePost($item);
            }

            $this->logInfo("Fetched " . count($posts) . " posts for user {$username}");

            return $posts;

        } catch (\Exception $e) {
            $this->logError("Error fetching posts for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    public function parsePost($data): array
    {
        return [
            'post_id' => $data['id'],
            'platform' => 'tiktok',
            'text' => $this->sanitizeText($data['desc'] ?? ''),
            'raw_text' => $data['desc'] ?? '',
            'image_url' => $data['cover'] ?? null,
            'video_url' => $data['play'] ?? null,
            'thumbnail_url' => $data['cover'] ?? null,
            'posted_at' => isset($data['create_time']) ? date('Y-m-d H:i:s', $data['create_time']) : now(),
            'metadata' => [
                'likes' => $data['digg_count'] ?? 0,
                'comments' => $data['comment_count'] ?? 0,
                'shares' => $data['share_count'] ?? 0,
                'plays' => $data['play_count'] ?? 0,
                'duration' => $data['video']['duration'] ?? null,
                'hashtags' => $this->extractHashtags($data['desc'] ?? ''),
                'mentions' => $this->extractMentions($data['desc'] ?? '')
            ]
        ];
    }
}
