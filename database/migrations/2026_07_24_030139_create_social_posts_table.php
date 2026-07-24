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
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->string('post_id');
            $table->string('platform');
            $table->text('text')->nullable();
            $table->text('raw_text')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->float('risk_score')->nullable();
            $table->text('ocr_text')->nullable();
            $table->text('transcript_text')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'platform']);
            $table->index(['candidate_id', 'platform']);
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
