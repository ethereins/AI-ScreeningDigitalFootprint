<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Services\CrawlerService;
use Illuminate\Console\Command;

class CrawlCandidates extends Command
{
    protected $signature = 'crawl:candidates
                            {--platform= : Platform to crawl (instagram, x, tiktok)}
                            {--limit=50 : Number of posts to fetch}
                            {--all : Crawl all candidates}';

    protected $description = 'Crawl social media posts for candidates';

    public function handle(CrawlerService $crawler)
    {
        $query = Candidate::query();

        if ($this->option('platform')) {
            $query->where('platform', $this->option('platform'));
        }

        if (!$this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('last_crawled_at')
                  ->orWhere('last_crawled_at', '<', now()->subHours(24));
            });
        }

        $candidates = $query->get();
        $limit = (int) $this->option('limit');

        if ($candidates->isEmpty()) {
            $this->warn('No candidates found to crawl');
            return 0;
        }

        $this->info("Found {$candidates->count()} candidates to crawl");

        foreach ($candidates as $candidate) {
            try {
                $this->info("Crawling {$candidate->username} ({$candidate->platform})...");
                $posts = $crawler->crawlCandidate($candidate, $limit);
                $this->info("✓ Done - " . count($posts) . " posts fetched");
            } catch (\Exception $e) {
                $this->error("✗ Failed: " . $e->getMessage());
            }
        }

        $this->info('Crawling completed!');
        return 0;
    }
}
