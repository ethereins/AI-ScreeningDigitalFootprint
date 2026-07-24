<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawler_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('action');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();

            $table->index(['candidate_id', 'platform']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawler_logs');
    }
};
