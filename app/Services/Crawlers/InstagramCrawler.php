<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;

class InstagramCrawler extends BaseCrawler
{
    public function __construct()
    {
        $this->accessToken = config('services.instagram.access_token');
        $this->baseUrl = 'https://graph.instagram.com';

        // Jika pakai RapidAPI
        // $this->apiKey = config('services.rapidapi.key');
        // $this->baseUrl = 'https://instagram-scraper-api2.p.rapidapi.com';
    }

    public function getPlatformName(): string
    {
        return 'instagram';
    }

    public function validateCredentials(): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/me", [
                'access_token' => $this->accessToken,
                'fields' => 'id,username'
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchPosts($username, $limit = 50): array
    {
        try {
            // Cari user ID
            $userResponse = Http::get("{$this->baseUrl}/me", [
                'access_token' => $this->accessToken,
                'fields' => 'id,username'
            ]);

            if (!$userResponse->successful()) {
                throw new \Exception("Failed to fetch user: " . $userResponse->body());
            }

            $userId = $userResponse->json('id');

            // Ambil media posts
            $response = Http::get("{$this->baseUrl}/{$userId}/media", [
                'access_token' => $this->accessToken,
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count',
                'limit' => min($limit, 100)
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch media: " . $response->body());
            }

            $data = $response->json();
            $posts = [];

            foreach ($data['data'] ?? [] as $item) {
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
        $isVideo = $data['media_type'] === 'VIDEO';
        $isImage = $data['media_type'] === 'IMAGE';
        $isCarousel = $data['media_type'] === 'CAROUSEL_ALBUM';

        return [
            'post_id' => $data['id'],
            'platform' => 'instagram',
            'text' => $this->sanitizeText($data['caption'] ?? ''),
            'raw_text' => $data['caption'] ?? '',
            'image_url' => $isImage ? $data['media_url'] : ($isCarousel ? $data['media_url'] ?? null : null),
            'video_url' => $isVideo ? $data['media_url'] : null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'posted_at' => $data['timestamp'] ?? now(),
            'metadata' => [
                'likes' => $data['like_count'] ?? 0,
                'comments' => $data['comments_count'] ?? 0,
                'media_type' => $data['media_type'],
                'permalink' => $data['permalink'] ?? null,
                'hashtags' => $this->extractHashtags($data['caption'] ?? ''),
                'mentions' => $this->extractMentions($data['caption'] ?? '')
            ]
        ];
    }
}
