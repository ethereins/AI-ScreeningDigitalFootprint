<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;

class FacebookCrawler extends BaseCrawler
{
    public function __construct()
    {
        $this->accessToken = config('services.facebook.access_token');
        $this->baseUrl = 'https://graph.facebook.com/v20.0';
    }

    public function getPlatformName(): string
    {
        return 'facebook';
    }

    public function validateCredentials(): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/me", [
                'access_token' => $this->accessToken,
                'fields' => 'id,name'
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchPosts($username, $limit = 50): array
    {
        try {
            // Untuk Facebook, biasanya pakai page ID atau user ID
            // Cari user/page ID dulu
            $userResponse = Http::get("{$this->baseUrl}/{$username}", [
                'access_token' => $this->accessToken,
                'fields' => 'id,name,username'
            ]);

            if (!$userResponse->successful()) {
                throw new \Exception("Failed to fetch user: " . $userResponse->body());
            }

            $userId = $userResponse->json('id');

            // Ambil posts
            $response = Http::get("{$this->baseUrl}/{$userId}/posts", [
                'access_token' => $this->accessToken,
                'fields' => 'id,message,created_time,likes,comments,shares,attachments,type',
                'limit' => min($limit, 100)
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch posts: " . $response->body());
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
        $attachments = $data['attachments']['data'][0] ?? null;
        $mediaUrl = null;
        $mediaType = null;

        if ($attachments) {
            $mediaType = $attachments['type'] ?? null;
            if ($mediaType === 'photo') {
                $mediaUrl = $attachments['media']['image']['src'] ?? null;
            } elseif ($mediaType === 'video') {
                $mediaUrl = $attachments['media']['source'] ?? null;
            }
        }

        return [
            'post_id' => $data['id'],
            'platform' => 'facebook',
            'text' => $this->sanitizeText($data['message'] ?? ''),
            'raw_text' => $data['message'] ?? '',
            'image_url' => $mediaType === 'photo' ? $mediaUrl : null,
            'video_url' => $mediaType === 'video' ? $mediaUrl : null,
            'thumbnail_url' => $attachments['media']['image']['src'] ?? null,
            'posted_at' => $data['created_time'] ?? now(),
            'metadata' => [
                'likes' => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'shares' => $data['shares']['count'] ?? 0,
                'type' => $data['type'] ?? null,
                'permalink' => "https://facebook.com/{$data['id']}",
                'hashtags' => $this->extractHashtags($data['message'] ?? ''),
                'mentions' => $this->extractMentions($data['message'] ?? '')
            ]
        ];
    }
}
