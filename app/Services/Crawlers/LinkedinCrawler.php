<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;

class LinkedinCrawler extends BaseCrawler
{
    public function __construct()
    {
        $this->apiKey = config('services.scraper.api_key');
        $this->baseUrl = config('services.scraper.api_url', 'https://api.scrapingbee.com/v1');
    }

    public function getPlatformName(): string
    {
        return 'linkedin';
    }

    public function validateCredentials(): bool
    {
        return !empty($this->apiKey);
    }

    public function fetchPosts($username, $limit = 50): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('LinkedIn scraper API key is not configured');
        }

        $payload = [
            'api_key' => $this->apiKey,
            'url' => "https://www.linkedin.com/in/{$username}/detail/recent-activity/",
            'render_js' => true,
            'wait_for' => 2000,
            'extract_rules' => json_encode([
                'posts' => [
                    'selector' => '.occludable-update',
                    'type' => 'list',
                    'children' => [
                        'post_id' => ['selector' => '[data-urn]', 'attr' => 'data-urn'],
                        'text' => ['selector' => '.feed-shared-update-v2__description', 'type' => 'text'],
                        'timestamp' => ['selector' => 'time', 'attr' => 'datetime'],
                        'image_url' => ['selector' => 'img', 'attr' => 'src']
                    ]
                ]
            ])
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception('LinkedIn scraper failed: ' . $response->body());
        }

        $body = $response->json();
        $posts = [];

        foreach ($body['posts'] ?? [] as $item) {
            if (empty($item['post_id'])) {
                continue;
            }

            $posts[] = $this->parsePost($item);

            if (count($posts) >= $limit) {
                break;
            }
        }

        return $posts;
    }

    public function parsePost($data): array
    {
        return [
            'post_id' => $data['post_id'],
            'platform' => 'linkedin',
            'text' => $this->sanitizeText($data['text'] ?? ''),
            'raw_text' => $data['text'] ?? '',
            'image_url' => $data['image_url'] ?? null,
            'video_url' => null,
            'thumbnail_url' => $data['image_url'] ?? null,
            'posted_at' => $data['timestamp'] ?? now(),
            'metadata' => [
                'source' => 'linkedin',
            ]
        ];
    }
}
