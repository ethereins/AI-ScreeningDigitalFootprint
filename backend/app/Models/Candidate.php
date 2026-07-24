<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'platform',
        'profile_url',
        'avatar_url',
        'raw_data',
        'last_crawled_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'last_crawled_at' => 'datetime',
    ];

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function posts(): HasMany
    {
        return $this->socialPosts();
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function crawlerLogs(): HasMany
    {
        return $this->hasMany(CrawlerLog::class);
    }

    public function getRiskSummaryAttribute(): array
    {
        $results = $this->analysisResults()
            ->whereNotNull('risk_score')
            ->latest()
            ->limit(100)
            ->get();

        if ($results->isEmpty()) {
            return [
                'total' => 0,
                'safe' => 0,
                'need_review' => 0,
                'high_risk' => 0,
                'risk_level' => 'UNKNOWN',
                'risk_score' => null,
            ];
        }

        $safe = $results->whereNull('risk_level')->count();
        $needReview = $results->where('risk_level', 'MEDIUM')->count();
        $highRisk = $results->whereIn('risk_level', ['HIGH', 'CRITICAL'])->count();
        $avgScore = $results->avg('risk_score');

        return [
            'total' => $results->count(),
            'safe' => $safe,
            'need_review' => $needReview,
            'high_risk' => $highRisk,
            'risk_level' => $this->getOverallRiskLevel($avgScore),
            'risk_score' => round($avgScore, 2),
        ];
    }

    private function getOverallRiskLevel($score): string
    {
        if ($score === null) {
            return 'UNKNOWN';
        }

        if ($score >= 80) {
            return 'CRITICAL';
        }

        if ($score >= 60) {
            return 'HIGH';
        }

        if ($score >= 30) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    public function scopeNeedsCrawling($query, $hours = 24)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_crawled_at')
              ->orWhere('last_crawled_at', '<', now()->subHours($hours));
        });
    }
}
