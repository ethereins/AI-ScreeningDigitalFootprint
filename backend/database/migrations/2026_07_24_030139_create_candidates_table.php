<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('platform'); // twitter, facebook, instagram, linkedin, tiktok, threads
            $table->string('profile_url')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
