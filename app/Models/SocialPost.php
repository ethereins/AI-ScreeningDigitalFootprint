<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'post_id',
        'platform',
        'text',
        'raw_text',
        'image_url',
        'video_url',
        'thumbnail_url',
        'posted_at',
        'metadata',
        'status',
        'risk_score',
        'ocr_text',
        'transcript_text',
    ];

    protected $casts = [
        'metadata' => 'array',
        'posted_at' => 'datetime',
        'risk_score' => 'float',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function analysisResult(): HasOne
    {
        return $this->hasOne(AnalysisResult::class, 'social_post_id');
    }

    public function getDisplayTextAttribute(): string
    {
        if (!empty($this->text)) {
            return $this->text;
        }

        if (!empty($this->ocr_text)) {
            return $this->ocr_text;
        }

        return $this->transcript_text ?? $this->raw_text ?? 'No text content';
    }

    public function getRiskLabelAttribute(): string
    {
        if ($this->risk_score === null) {
            return 'unknown';
        }

        if ($this->risk_score >= 75) {
            return 'high';
        }

        if ($this->risk_score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNotAnalyzed($query)
    {
        return $query->whereNull('analyzed_at');
    }
}
