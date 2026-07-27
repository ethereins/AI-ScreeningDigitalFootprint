<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    protected $fillable = [
        'persona_id',
        'platform',
        'username',
        'post_id',
        'url',
        'text',
        'image_url',
        'video_url',
        'posted_at',
        'likes',
        'comments',
        'shares',
        'profile_name',
        'profile_url',
        'metadata',
        'status',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'metadata' => 'array',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
