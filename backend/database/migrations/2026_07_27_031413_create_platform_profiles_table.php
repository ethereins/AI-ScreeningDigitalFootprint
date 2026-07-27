<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('username')->nullable();
            $table->string('full_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('profile_url')->nullable();
            $table->integer('post_count')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();

            $table->unique(['persona_id', 'platform']);
            $table->index(['platform', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_profiles');
    }
};
