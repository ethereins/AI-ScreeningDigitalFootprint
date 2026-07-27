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
            'https://api.brightdata.com/datasets/v3/scrape' => Http::response('Customer is not active', 401, ['Content-Type' => 'text/plain']),
            'https://api.brightdata.com/datasets/v3/trigger' => Http::response('Customer is not active', 401, ['Content-Type' => 'text/plain']),
        ]);

        $service = new BrightDataService();

        $this->expectExceptionMessage('Bright Data customer/account is not active. Please activate the Bright Data account or use a valid API key.');

        $service->scrapePosts('instagram', 'zuck', 3);
    }

    public function test_set_dataset_updates_the_mapping(): void
    {
        $service = new BrightDataService();

        $service->setDataset('instagram', 'gd_lk5ns7kz21pck8jpis');

        $this->assertSame('gd_lk5ns7kz21pck8jpis', $service->datasets['instagram']);
    }

    public function test_scrape_posts_falls_back_to_another_dataset_when_first_collector_is_missing(): void
    {
        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $datasetId = $query['dataset_id'] ?? null;
            $body = $request->data();

            if ($datasetId === 'dataset-a') {
                return Http::response('Collector not found', 404, ['Content-Type' => 'text/plain']);
            }

            return Http::response(['data' => [['id' => 'post-1', 'text' => 'Recovered successfully']]], 200, ['Content-Type' => 'application/json']);
        });

        $service = new BrightDataService();
        $service->setDataset('instagram', 'dataset-a');
        $service->setDataset('instagram_posts', 'dataset-b');

        $posts = $service->scrapePosts('instagram', 'zuck', 3, false);

        $this->assertIsArray($posts);
        $this->assertCount(1, $posts);
        $this->assertSame('Recovered successfully', $posts[0]['text'] ?? null);
    }
}
