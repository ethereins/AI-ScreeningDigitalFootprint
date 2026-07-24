<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_post_id',
        'candidate_id',
        'toxicity',
        'threat',
        'insult',
        'obscene',
        'identity_attack',
        'sexual_explicit',
        'hate_speech',
        'offensive',
        'abusive',
        'risk_score',
        'risk_level',
        'context_category',
        'context_explanation',
        'context_full',
        'full_response',
    ];

    protected $casts = [
        'context_full' => 'array',
        'full_response' => 'array',
    ];

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
