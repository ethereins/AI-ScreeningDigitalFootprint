<?php

namespace Database\Seeders;

use App\Models\AnalysisResult;
use App\Models\Candidate;
use App\Models\SocialPost;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        $candidate = Candidate::create([
            'username' => 'johndoe',
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'platform' => 'twitter',
            'profile_url' => 'https://twitter.com/johndoe',
            'avatar_url' => 'https://example.com/avatar/johndoe.jpg',
            'raw_data' => ['source' => 'seed'],
        ]);

        $post = SocialPost::create([
            'candidate_id' => $candidate->id,
            'post_id' => 'tweet-12345',
            'platform' => 'twitter',
            'text' => 'This is a sample seed post for screening.',
            'raw_text' => 'This is a sample seed post for screening.',
            'image_url' => null,
            'video_url' => null,
            'thumbnail_url' => null,
            'posted_at' => now()->subDays(1),
            'metadata' => ['likes' => 10, 'retweets' => 2],
            'status' => 'analyzed',
            'risk_score' => 12,
        ]);

        AnalysisResult::create([
            'social_post_id' => $post->id,
            'candidate_id' => $candidate->id,
            'toxicity' => 0.02,
            'threat' => 0.0,
            'insult' => 0.0,
            'obscene' => 0.0,
            'identity_attack' => 0.0,
            'sexual_explicit' => 0.0,
            'hate_speech' => 0.0,
            'offensive' => 0.0,
            'abusive' => 0.0,
            'risk_score' => 12,
            'risk_level' => 'LOW',
            'context_category' => 'neutral',
            'context_explanation' => 'Seed post with low risk',
            'context_full' => [],
            'full_response' => [],
        ]);
    }
}
