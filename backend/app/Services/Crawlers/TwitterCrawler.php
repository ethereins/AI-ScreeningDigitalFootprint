<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;

class TwitterCrawler extends BaseCrawler
{
    public function __construct()
    {
        $this->apiKey = config('services.twitter.bearer_token');
        $this->baseUrl = 'https://api.twitter.com/2';
    }

    public function getPlatformName(): string
    {
        return 'twitter';
    }

    public function validateCredentials(): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/users/by/username/elonmusk");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchPosts($username, $limit = 50): array
    {
        try {
            // Cari user ID
            $userResponse = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/users/by/username/{$username}");

            if (!$userResponse->successful()) {
                throw new \Exception("Failed to fetch user: " . $userResponse->body());
            }

            $userId = $userResponse->json('data.id');

            // Ambil tweets
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/users/{$userId}/tweets", [
                    'max_results' => min($limit, 100),
                    'tweet.fields' => 'created_at,public_metrics,attachments,context_annotations,lang',
                    'expansions' => 'attachments.media_keys',
                    'media.fields' => 'url,preview_image_url,type'
                ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch tweets: " . $response->body());
            }

            $data = $response->json();
            $posts = [];

            foreach ($data['data'] ?? [] as $tweet) {
                $posts[] = $this->parsePost($tweet, $data['includes']['media'] ?? []);
            }

            $this->logInfo("Fetched " . count($posts) . " tweets for user {$username}");

            return $posts;

        } catch (\Exception $e) {
            $this->logError("Error fetching tweets for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    public function parsePost($data, $media = []): array
    {
        // Cari media yang terkait
        $mediaUrl = null;
        $thumbnailUrl = null;

        if (isset($data['attachments']['media_keys'])) {
            foreach ($media as $item) {
                if (in_array($item['media_key'], $data['attachments']['media_keys'])) {
                    if ($item['type'] === 'video' || $item['type'] === 'animated_gif') {
                        $mediaUrl = $item['url'] ?? $item['preview_image_url'] ?? null;
                    } else {
                        $mediaUrl = $item['url'] ?? null;
                    }
                    $thumbnailUrl = $item['preview_image_url'] ?? null;
                    break;
                }
            }
        }

        return [
            'post_id' => $data['id'],
            'platform' => 'twitter',
            'text' => $this->sanitizeText($data['text']),
            'raw_text' => $data['text'],
            'image_url' => $mediaUrl,
            'video_url' => null, // Twitter API v2 media_url untuk video
            'thumbnail_url' => $thumbnailUrl,
            'posted_at' => $data['created_at'] ?? now(),
            'metadata' => [
                'likes' => $data['public_metrics']['like_count'] ?? 0,
                'retweets' => $data['public_metrics']['retweet_count'] ?? 0,
                'replies' => $data['public_metrics']['reply_count'] ?? 0,
                'quotes' => $data['public_metrics']['quote_count'] ?? 0,
                'lang' => $data['lang'] ?? null,
                'hashtags' => $this->extractHashtags($data['text']),
                'mentions' => $this->extractMentions($data['text'])
            ]
        ];
    }
}
