<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();

            $table->float('toxicity')->nullable();
            $table->float('threat')->nullable();
            $table->float('insult')->nullable();
            $table->float('obscene')->nullable();
            $table->float('identity_attack')->nullable();
            $table->float('sexual_explicit')->nullable();
            $table->float('hate_speech')->nullable();
            $table->float('offensive')->nullable();
            $table->float('abusive')->nullable();

            $table->float('risk_score')->nullable();
            $table->string('risk_level')->nullable(); // LOW, MEDIUM, HIGH, CRITICAL

            $table->string('context_category')->nullable();
            $table->text('context_explanation')->nullable();
            $table->json('context_full')->nullable();
            $table->json('full_response')->nullable();

            $table->timestamps();

            $table->index(['candidate_id', 'risk_level']);
            $table->index('risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
