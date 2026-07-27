<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformProfile extends Model
{
    protected $fillable = [
        'persona_id',
        'platform',
        'username',
        'full_name',
        'avatar_url',
        'profile_url',
        'post_count',
        'last_scraped_at',
    ];

    protected $casts = [
        'last_scraped_at' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
