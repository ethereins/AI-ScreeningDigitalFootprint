<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id', 'platform', 'action', 'message', 'payload', 'success'
    ];

    protected $casts = [
        'payload' => 'array'
    ];
}
