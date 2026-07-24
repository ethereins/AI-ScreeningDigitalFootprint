<?php

namespace App\Services;

use App\Services\Crawlers\InstagramCrawler;
use App\Services\Crawlers\TwitterCrawler;
use App\Services\Crawlers\FacebookCrawler;
use App\Services\Crawlers\TikTokCrawler;
use App\Services\Crawlers\LinkedinCrawler;
use App\Services\Crawlers\ThreadsCrawler;

class PlatformManager
{
    protected $platforms = [
        'instagram' => InstagramCrawler::class,
        'twitter' => TwitterCrawler::class,
        'facebook' => FacebookCrawler::class,
        'tiktok' => TikTokCrawler::class,
        'linkedin' => LinkedinCrawler::class,
        'threads' => ThreadsCrawler::class,
    ];

    public function getCrawler($platform)
    {
        if (!isset($this->platforms[$platform])) {
            throw new \Exception("Platform {$platform} not supported");
        }

        return app($this->platforms[$platform]);
    }

    public function getSupportedPlatforms(): array
    {
        return array_keys($this->platforms);
    }

    public function validatePlatform($platform): bool
    {
        return in_array($platform, $this->getSupportedPlatforms());
    }
}
