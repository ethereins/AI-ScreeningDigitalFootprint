<?php

use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\AnalysisController;
use Illuminate\Support\Facades\Route;

// Candidate endpoints
Route::prefix('candidates')->group(function () {
    Route::get('/', [CandidateController::class, 'index']);
    Route::post('/', [CandidateController::class, 'store']);
    Route::get('/{id}', [CandidateController::class, 'show']);
    Route::get('/{id}/risk', [CandidateController::class, 'risk']);
    Route::get('/{id}/posts', [CandidateController::class, 'posts']);
    Route::post('/{id}/crawl', [CandidateController::class, 'crawl']);
    Route::post('/crawl-all', [CandidateController::class, 'crawlAll']);
    Route::delete('/{id}', [CandidateController::class, 'destroy']);
});

// Analysis endpoints
Route::prefix('analysis')->group(function () {
    Route::get('/summary/{candidateId}', [AnalysisController::class, 'getRiskSummary']);
    Route::get('/high-risk/{candidateId}', [AnalysisController::class, 'getHighRiskPosts']);
    Route::get('/trends', [AnalysisController::class, 'getTrends']);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
