<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('persona_id')->nullable();
            $table->string('post_id')->nullable();
            $table->string('platform');
            $table->string('username')->nullable();
            $table->text('text')->nullable();
            $table->text('raw_text')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->string('profile_name')->nullable();
            $table->string('profile_url')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending');
            $table->float('risk_score')->nullable();
            $table->text('ocr_text')->nullable();
            $table->text('transcript_text')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'platform']);
            $table->index(['candidate_id', 'platform']);
            $table->index(['persona_id', 'platform']);
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
