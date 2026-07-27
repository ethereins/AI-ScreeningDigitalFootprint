<?php
// app/Models/Persona.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $fillable = ['name', 'email'];

    public function posts()
    {
        return $this->hasMany(SocialPost::class);
    }

    public function profiles()
    {
        return $this->hasMany(PlatformProfile::class);
    }
}
