<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\SocialPost;
use App\Models\CrawlerLog;
use Illuminate\Support\Facades\Log;

class CrawlerService
{
    protected $platformManager;

    public function __construct(PlatformManager $platformManager)
    {
        $this->platformManager = $platformManager;
    }

    /**
     * Crawl single candidate
     */
    public function crawlCandidate(Candidate $candidate, $limit = 50): array
    {
        try {
            $crawler = $this->platformManager->getCrawler($candidate->platform);

            $this->log($candidate, 'fetch', "Starting crawl for {$candidate->username}");

            $posts = $crawler->fetchPosts($candidate->username, $limit);

            $stored = 0;
            foreach ($posts as $postData) {
                $this->storePost($candidate, $postData);
                $stored++;
            }

            $candidate->update([
                'last_crawled_at' => now()
            ]);

            $this->log($candidate, 'fetch', "Successfully crawled {$stored} posts", true);

            return $posts;

        } catch (\Exception $e) {
            $this->log($candidate, 'error', $e->getMessage(), false);
            Log::error("Crawler error for {$candidate->username}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store post to database and dispatch analysis job
     */
    protected function storePost(Candidate $candidate, array $data): SocialPost
{
    // Cek apakah post sudah ada
    $existing = SocialPost::where('post_id', $data['post_id'])
        ->where('platform', $data['platform'])
        ->first();

    if ($existing) {
        return $existing;
    }

    $post = SocialPost::create([
        'candidate_id' => $candidate->id,
        'post_id' => $data['post_id'],
        'platform' => $data['platform'],
        'text' => $data['text'] ?? null,
        'raw_text' => $data['raw_text'] ?? null,
        'image_url' => $data['image_url'] ?? null,
        'video_url' => $data['video_url'] ?? null,
        'thumbnail_url' => $data['thumbnail_url'] ?? null,
        'posted_at' => $data['posted_at'] ?? now(),
        'metadata' => $data['metadata'] ?? [],
        'status' => 'pending'
    ]);

    // Dispatch job untuk analisis
    dispatch(new \App\Jobs\AnalyzePostJob($post));

    return $post;
}

    /**
     * Log crawler activity
     */
    protected function log(Candidate $candidate, $action, $message, $success = true): void
    {
        CrawlerLog::create([
            'candidate_id' => $candidate->id,
            'platform' => $candidate->platform,
            'action' => $action,
            'message' => $message,
            'success' => $success
        ]);
    }
}
