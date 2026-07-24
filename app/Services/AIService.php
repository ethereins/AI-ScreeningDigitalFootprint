<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ai_service.url', 'http://localhost:8000');
        $this->timeout = config('services.ai_service.timeout', 120);
    }

    /**
     * Analyze a post (text + image + video)
     */
    public function analyzePost(array $payload): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/analyze-post", $payload);

            if (!$response->successful()) {
                throw new \Exception("AI Service error: " . $response->body());
            }

            $result = $response->json();

            // Validasi response
            if (!isset($result['risk_score']) || !isset($result['risk_level'])) {
                throw new \Exception("Invalid response from AI Service");
            }

            Log::info("AI Service analysis successful", [
                'risk_score' => $result['risk_score'],
                'risk_level' => $result['risk_level']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("AI Service call failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze text only
     */
    public function analyzeText(string $text): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/analyze-text", ['text' => $text]);

            if (!$response->successful()) {
                throw new \Exception("AI Service error: " . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("AI Service text analysis failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * OCR image
     */
    public function ocrImage(string $imageUrl): array
    {
        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/ocr", ['image_url' => $imageUrl]);

            if (!$response->successful()) {
                throw new \Exception("OCR failed: " . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("OCR failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transcribe video
     */
    public function transcribeVideo(string $videoUrl): array
    {
        try {
            $response = Http::timeout(120)
                ->post("{$this->baseUrl}/transcribe", ['video_url' => $videoUrl]);

            if (!$response->successful()) {
                throw new \Exception("Transcription failed: " . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Transcription failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Health check
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
