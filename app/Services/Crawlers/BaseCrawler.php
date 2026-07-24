<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseCrawler
{
    protected $apiKey;
    protected $apiSecret;
    protected $accessToken;
    protected $baseUrl;

    /**
     * Fetch posts from platform
     */
    abstract public function fetchPosts($username, $limit = 50): array;

    /**
     * Parse single post data
     */
    abstract public function parsePost($data): array;

    /**
     * Get platform name
     */
    abstract public function getPlatformName(): string;

    /**
     * Validate credentials
     */
    abstract public function validateCredentials(): bool;

    /**
     * Get headers for API request
     */
    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
    }

    /**
     * Sanitize text (remove emojis, extra spaces, etc.)
     */
    protected function sanitizeText($text): string
    {
        if (empty($text)) {
            return '';
        }

        // Remove emojis
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text);
        $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text);
        $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $text);
        $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $text);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extract hashtags from text
     */
    protected function extractHashtags($text): array
    {
        preg_match_all('/#([\w]+)/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Extract mentions from text
     */
    protected function extractMentions($text): array
    {
        preg_match_all('/@([\w]+)/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Log error
     */
    protected function logError($message, $context = []): void
    {
        Log::error("[Crawler: {$this->getPlatformName()}] " . $message, $context);
    }

    /**
     * Log info
     */
    protected function logInfo($message, $context = []): void
    {
        Log::info("[Crawler: {$this->getPlatformName()}] " . $message, $context);
    }
}
