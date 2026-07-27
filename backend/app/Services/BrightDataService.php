<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrightDataService
{
    protected $apiKey;
    protected $apiUrl;
    public $datasets;

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

    public function setDataset(string $datasetKey, string $datasetId): void
    {
        $this->datasets[$datasetKey] = $datasetId;
    }

    public function scrapePosts(string $platform, string $username, int $limit = 50, bool $returnRaw = false): array
    {
        $datasetCandidates = $this->getDatasetCandidates($platform);

        if (empty($datasetCandidates)) {
            throw new \Exception("Platform {$platform} not supported");
        }

        $url = $this->buildUrl($platform, $username);

        $payload = [
            'input' => [[
                'url' => $url,
            ]],
            'limit_per_input' => $limit,
        ];

        $endpoints = $this->getRequestEndpoints();
        $lastError = null;

        foreach ($datasetCandidates as $datasetId) {
            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::timeout(120)
                        ->withHeaders([
                            'Authorization' => "Bearer {$this->apiKey}",
                            'Content-Type' => 'application/json',
                        ])->asJson()
                        ->post($endpoint . '?dataset_id=' . urlencode($datasetId) . '&notify=false&include_errors=true', $payload);

                    if ($response->successful()) {
                        $data = $response->json();

                        if ($returnRaw) {
                            return $data;
                        }

                        return $this->parseResponse($platform, $data);
                    }

                    $message = $response->body();
                    $lastError = $message;

                    if ($response->status() === 401 || stripos($message, 'customer is not active') !== false) {
                        throw new \Exception('Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.');
                    }

                    if ($response->status() === 404 || stripos($message, 'collector not found') !== false) {
                        $lastError = 'Collector not found for dataset ' . $datasetId . ': ' . $message;
                        break;
                    }

                    throw new \Exception("Bright Data API error: " . $message);
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                }
            }
        }

        Log::error("Bright Data scrape failed: " . $lastError);

        if ($lastError && stripos($lastError, 'customer is not active') !== false) {
            throw new \Exception('Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.');
        }

        if ($lastError && (stripos($lastError, 'collector not found') !== false || stripos($lastError, 'not found') !== false)) {
            throw new \Exception("Bright Data collector/dataset not found for platform {$platform}. Details: {$lastError}");
        }

        throw new \Exception('Bright Data API error: ' . $lastError);
    }

    protected function buildUrl(string $platform, string $username): string
    {
        $urls = [
            'twitter' => "https://x.com/{$username}",
            'tiktok' => "https://www.tiktok.com/@{$username}",
        ];

        if ($platform === 'instagram') {
            if (str_contains($username, '/')) {
                return 'https://www.instagram.com' . $username;
            }

            return "https://www.instagram.com/{$username}/";
        }

        return $urls[$platform] ?? "https://www.{$platform}.com/{$username}";
    }

    protected function getRequestEndpoints(): array
    {
        return [
            "{$this->apiUrl}/scrape",
            "{$this->apiUrl}/trigger",
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

    protected function getDatasetCandidates(string $platform): array
    {
        $resolvedKey = $this->resolveDatasetKey($platform);
        $keysToTry = array_values(array_unique(array_filter([
            $platform,
            $resolvedKey,
            $platform === 'instagram' ? 'instagram' : null,
            $platform === 'twitter' ? 'twitter_posts' : null,
            $platform === 'tiktok' ? 'tiktok_posts' : null,
        ])));

        $candidates = [];

        foreach ($keysToTry as $key) {
            if (!isset($this->datasets[$key]) || !is_string($this->datasets[$key]) || $this->datasets[$key] === '') {
                continue;
            }

            $candidates[] = $this->datasets[$key];
        }

        if (empty($candidates)) {
            $candidates = array_values(array_filter(array_map(static function ($datasetId) {
                return is_string($datasetId) && $datasetId !== '' ? $datasetId : null;
            }, $this->datasets)));
        }

        return array_values(array_unique($candidates));
    }

    protected function parseResponse(string $platform, array $data): array
    {
        $posts = [];

        $items = $data['data'] ?? $data['items'] ?? $data['records'] ?? $data['results'] ?? [];

        if (empty($items) && isset($data['posts']) && is_array($data['posts'])) {
            $items = $data['posts'];
        }

        if (empty($items) && isset($data['result']) && is_array($data['result'])) {
            $items = $data['result'];
        }

        if (empty($items) && isset($data['response']) && is_array($data['response'])) {
            $items = $data['response'];
        }

        if (isset($data['input']) && is_array($data['input']) && !empty($data['input'])) {
            $inputItem = $data['input'];
            if (isset($inputItem['url']) || isset($inputItem[0]['url'])) {
                $items = $items ?: [];
            }
        }

        if (is_array($items) && isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }

        if (is_array($items) && isset($items['result']) && is_array($items['result'])) {
            $items = $items['result'];
        }

        if (is_array($items) && isset($items['response']) && is_array($items['response'])) {
            $items = $items['response'];
        }

        if (!is_array($items)) {
            return $posts;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['error']) && isset($item['error_code'])) {
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
