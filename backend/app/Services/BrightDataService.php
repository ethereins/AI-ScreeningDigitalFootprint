<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrightDataService
{
    protected $apiKey;
    protected $apiUrl;
    protected $datasets;

    public function __construct()
    {
        $this->apiKey = config('services.brightdata.api_key');
        $this->apiUrl = config('services.brightdata.api_url', 'https://api.brightdata.com/datasets/v3');
        $this->datasets = config('services.brightdata.datasets', []);
    }

    public function healthCheck(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->apiUrl}/health");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Bright Data health check failed: " . $e->getMessage());
            return false;
        }
    }

    public function scrapePosts(string $platform, string $username, int $limit = 50): array
    {
        $datasetKey = $this->resolveDatasetKey($platform);
        $datasetId = $this->datasets[$datasetKey] ?? null;

        if (!$datasetId) {
            throw new \Exception("Platform {$platform} not supported");
        }

        $url = $this->buildUrl($platform, $username);

        $payload = [[
            'url' => $url,
            'limit' => $limit
        ]];

        $endpoints = $this->getRequestEndpoints();
        $lastError = null;

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                    ])->post($endpoint, [
                        'dataset_id' => $datasetId,
                        'format' => 'json',
                        'payload' => $payload
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->parseResponse($platform, $data);
                }

                $message = $response->body();
                $lastError = $message;

                if ($response->status() === 401 || stripos($message, 'customer is not active') !== false) {
                    throw new \Exception('Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.');
                }

                if ($response->status() === 404 || stripos($message, 'collector not found') !== false) {
                    continue;
                }

                throw new \Exception("Bright Data API error: " . $message);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        Log::error("Bright Data scrape failed: " . $lastError);
        throw new \Exception(
            $lastError && stripos($lastError, 'customer is not active') !== false
                ? 'Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.'
                : 'Bright Data API error: ' . $lastError
        );
    }

    protected function buildUrl(string $platform, string $username): string
    {
        $urls = [
            'instagram' => "https://www.instagram.com/{$username}/",
            'twitter' => "https://x.com/{$username}",
            'tiktok' => "https://www.tiktok.com/@{$username}",
        ];

        return $urls[$platform] ?? "https://www.{$platform}.com/{$username}";
    }

    protected function getRequestEndpoints(): array
    {
        return [
            "{$this->apiUrl}/trigger",
            "{$this->apiUrl}/scrape",
        ];
    }

    protected function resolveDatasetKey(string $platform): string
    {
        return match ($platform) {
            'instagram' => 'instagram_posts',
            'instagram_posts' => 'instagram_posts',
            'instagram_profiles' => 'instagram',
            default => $platform,
        };
    }

    protected function parseResponse(string $platform, array $data): array
    {
        $posts = [];

        $items = $data['data'] ?? $data['items'] ?? $data['records'] ?? $data['results'] ?? [];

        if (empty($items) && isset($data['posts']) && is_array($data['posts'])) {
            $items = $data['posts'];
        }

        if (!is_array($items)) {
            return $posts;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $postData = [
                'post_id' => $item['id'] ?? $item['post_id'] ?? uniqid(),
                'platform' => $platform,
                'text' => $item['text'] ?? $item['caption'] ?? '',
                'raw_text' => $item['text'] ?? $item['caption'] ?? '',
                'image_url' => $item['image_url'] ?? $item['media_url'] ?? null,
                'video_url' => $item['video_url'] ?? null,
                'thumbnail_url' => $item['thumbnail_url'] ?? null,
                'posted_at' => $item['created_at'] ?? $item['timestamp'] ?? $item['datetime'] ?? now(),
                'metadata' => [
                    'likes' => $item['likes'] ?? 0,
                    'comments' => $item['comments'] ?? 0,
                    'shares' => $item['shares'] ?? 0,
                    'profile' => $data['account'] ?? $data['profile'] ?? null,
                    'full_name' => $data['full_name'] ?? $data['profile_name'] ?? null,
                    'profile_url' => $data['profile_url'] ?? $data['url'] ?? null,
                ],
            ];

            $posts[] = $postData;
        }

        return $posts;
    }
}
