<?php

namespace App\Services;

use App\Services\Crawlers\InstagramCrawler;
use App\Services\Crawlers\TwitterCrawler;
use App\Services\Crawlers\TikTokCrawler;

class PlatformManager
{
    protected $platforms = [
        'instagram' => InstagramCrawler::class,
        'x' => TwitterCrawler::class,
        'tiktok' => TikTokCrawler::class,
    ];

    public function getCrawler($platform)
    {
        $normalizedPlatform = $this->normalizePlatform($platform);

        if (!isset($this->platforms[$normalizedPlatform])) {
            throw new \Exception("Platform {$platform} not supported");
        }

        return app($this->platforms[$normalizedPlatform]);
    }

    public function getSupportedPlatforms(): array
    {
        return array_keys($this->platforms);
    }

    public function validatePlatform($platform): bool
    {
        return in_array($this->normalizePlatform($platform), $this->getSupportedPlatforms());
    }

    protected function normalizePlatform($platform): string
    {
        $value = strtolower(trim((string) $platform));

        return match ($value) {
            'twitter' => 'x',
            default => $value,
        };
    }
}
