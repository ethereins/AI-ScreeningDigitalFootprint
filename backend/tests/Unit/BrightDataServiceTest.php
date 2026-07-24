<?php

namespace Tests\Unit;

use App\Services\BrightDataService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrightDataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.brightdata.api_key', 'test-key');
        config()->set('services.brightdata.api_url', 'https://api.brightdata.com/datasets/v3');
        config()->set('services.brightdata.datasets', [
            'instagram' => 'dataset-id',
            'instagram_posts' => 'dataset-id',
        ]);
    }

    public function test_scrape_posts_throws_clear_error_for_inactive_customer(): void
    {
        Http::fake([
            'https://api.brightdata.com/datasets/v3/trigger' => Http::response('Customer is not active', 401, ['Content-Type' => 'text/plain']),
            'https://api.brightdata.com/datasets/v3/scrape' => Http::response('Customer is not active', 401, ['Content-Type' => 'text/plain']),
        ]);

        $service = new BrightDataService();

        $this->expectExceptionMessage('Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.');

        $service->scrapePosts('instagram', 'zuck', 3);
    }
}
