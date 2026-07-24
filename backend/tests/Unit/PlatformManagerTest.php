<?php

namespace Tests\Unit;

use App\Services\PlatformManager;
use PHPUnit\Framework\TestCase;

class PlatformManagerTest extends TestCase
{
    public function test_supported_platforms_are_only_the_three_requested_platforms(): void
    {
        $manager = new PlatformManager();

        $this->assertSame(['instagram', 'x', 'tiktok'], $manager->getSupportedPlatforms());
    }

    public function test_twitter_is_normalized_to_supported_platforms(): void
    {
        $manager = new PlatformManager();

        $this->assertTrue($manager->validatePlatform('twitter'));
        $this->assertFalse($manager->validatePlatform('facebook'));
        $this->assertFalse($manager->validatePlatform('linkedin'));
    }
}
