<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Models\AnalysisResult;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzePostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $post;

    public function __construct(SocialPost $post)
    {
        $this->post = $post;
    }

    public function handle(AIService $aiService)
    {
        try {
            // Update status
            $this->post->update(['status' => 'processing']);

            Log::info("Analyzing post {$this->post->id}");

            // Siapkan payload untuk AI Service
            $payload = [
                'text' => $this->post->text,
                'image_url' => $this->post->image_url,
                'video_url' => $this->post->video_url,
                'platform' => $this->post->platform,
                'post_id' => $this->post->post_id
            ];

            // Panggil AI Service
            $result = $aiService->analyzePost($payload);

            // Simpan hasil analisis
            $ocrText = null;
            $transcriptText = null;

            if ($this->post->image_url) {
                try {
                    $ocr = $aiService->ocrImage($this->post->image_url);
                    $ocrText = $ocr['text'] ?? null;
                } catch (\Exception $e) {
                    Log::warning("OCR failed for post {$this->post->id}: {$e->getMessage()}");
                }
            }

            if ($this->post->video_url) {
                try {
                    $transcription = $aiService->transcribeVideo($this->post->video_url);
                    $transcriptText = $transcription['text'] ?? null;
                } catch (\Exception $e) {
                    Log::warning("Transcription failed for post {$this->post->id}: {$e->getMessage()}");
                }
            }

            AnalysisResult::updateOrCreate(
                ['social_post_id' => $this->post->id],
                [
                    'candidate_id' => $this->post->candidate_id,
                    'toxicity' => $result['scores']['toxicity'] ?? null,
                    'threat' => $result['scores']['threat'] ?? null,
                    'insult' => $result['scores']['insult'] ?? null,
                    'obscene' => $result['scores']['obscene'] ?? null,
                    'identity_attack' => $result['scores']['identity_attack'] ?? null,
                    'sexual_explicit' => $result['scores']['sexual_explicit'] ?? null,
                    'hate_speech' => $result['scores']['hate_speech'] ?? null,
                    'offensive' => $result['scores']['offensive'] ?? null,
                    'abusive' => $result['scores']['abusive'] ?? null,
                    'risk_score' => $result['risk_score'] ?? null,
                    'risk_level' => $result['risk_level'] ?? null,
                    'context_category' => $result['context']['category'] ?? null,
                    'context_explanation' => $result['context']['explanation'] ?? null,
                    'context_full' => $result['context'] ?? null,
                    'full_response' => $result
                ]
            );

            $this->post->update([
                'status' => 'analyzed',
                'analyzed_at' => now(),
                'ocr_text' => $ocrText,
                'transcript_text' => $transcriptText,
            ]);

            Log::info("Post {$this->post->id} analyzed successfully", [
                'risk_score' => $result['risk_score'] ?? null,
                'risk_level' => $result['risk_level'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to analyze post {$this->post->id}: " . $e->getMessage());

            $this->post->update(['status' => 'failed']);

            // Retry jika masih bisa
            if ($this->attempts() < $this->tries) {
                $this->release(60); // retry after 60 seconds
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Job failed permanently for post {$this->post->id}: " . $exception->getMessage());

        $this->post->update([
            'status' => 'failed'
        ]);
    }
}
