<?php

use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\AnalysisController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonaController;


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


Route::prefix('persona')->group(function () {
    // 🔥 SEARCH atau BUAT persona baru
    Route::post('/search', [PersonaController::class, 'searchOrCreate']);

    // 📖 Ambil data persona berdasarkan ID
    Route::get('/{id}', [PersonaController::class, 'show']);

    // 📖 Ambil data persona berdasarkan nama
    Route::get('/name/{name}', [PersonaController::class, 'findByName']);

    // 📖 Ambil data persona berdasarkan username
    Route::get('/username/{username}', [PersonaController::class, 'findByUsername']);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
