<?php

namespace Tests\Feature;

use App\Services\SocialMediaAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PersonaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_or_create_persists_platform_data(): void
    {
        $aggregator = Mockery::mock(SocialMediaAggregator::class);
        $aggregator->shouldReceive('collectPersonaData')
            ->once()
            ->with('Alice', ['instagram' => 'alice', 'tiktok' => null, 'twitter' => null])
            ->andReturn([
                'persona' => [
                    'name' => 'Alice',
                    'social_media' => [
                        [
                            'platform' => 'instagram',
                            'post_count' => 1,
                            'profile_url' => 'https://instagram.com/alice',
                            'avatar_url' => null,
                            'full_name' => 'Alice Smith',
                            'username' => 'alice',
                            'created_at' => now()->toIso8601String(),
                            'updated_at' => now()->toIso8601String(),
                            'posts' => [
                                [
                                    'text' => 'Hello world',
                                    'image_url' => 'https://example.com/hello.jpg',
                                    'video_url' => null,
                                    'platform' => 'instagram',
                                    'posted_at' => now()->toIso8601String(),
                                    'likes' => 12,
                                    'comments' => 3,
                                    'post_id' => 'p123',
                                    'url' => 'https://instagram.com/p/p123',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(SocialMediaAggregator::class, $aggregator);

        $response = $this->postJson('/api/persona/search', [
            'name' => 'Alice',
            'instagram' => 'alice',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('persona.name', 'Alice')
            ->assertJsonPath('persona.social_media.0.platform', 'instagram');

        $this->assertDatabaseHas('personas', ['name' => 'Alice']);
        $this->assertDatabaseHas('platform_profiles', ['platform' => 'instagram', 'username' => 'alice']);
        $this->assertDatabaseHas('social_posts', ['post_id' => 'p123', 'platform' => 'instagram']);
    }
}
