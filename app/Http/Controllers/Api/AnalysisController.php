<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\AnalysisResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    public function getRiskSummary($candidateId): JsonResponse
    {
        $candidate = Candidate::findOrFail($candidateId);

        $summary = $candidate->risk_summary;

        // Detail scores per category
        $results = AnalysisResult::where('candidate_id', $candidateId)
            ->latest()
            ->limit(100)
            ->get();

        $scores = [
            'toxicity' => $results->avg('toxicity'),
            'threat' => $results->avg('threat'),
            'insult' => $results->avg('insult'),
            'obscene' => $results->avg('obscene'),
            'identity_attack' => $results->avg('identity_attack'),
            'sexual_explicit' => $results->avg('sexual_explicit'),
            'hate_speech' => $results->avg('hate_speech'),
            'offensive' => $results->avg('offensive'),
            'abusive' => $results->avg('abusive'),
        ];

        return response()->json([
            'candidate_id' => $candidateId,
            'risk_level' => $summary['risk_level'],
            'risk_score' => $summary['risk_score'],
            'total_posts' => $summary['total'],
            'safe' => $summary['safe'],
            'need_review' => $summary['need_review'],
            'high_risk' => $summary['high_risk'],
            'average_scores' => $scores
        ]);
    }

    public function getHighRiskPosts($candidateId): JsonResponse
    {
        $posts = AnalysisResult::where('candidate_id', $candidateId)
            ->whereIn('risk_level', ['HIGH', 'CRITICAL'])
            ->with('post')
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    public function getTrends(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        $trends = AnalysisResult::selectRaw(
            "DATE(created_at) as date,
            AVG(risk_score) as avg_risk,
            COUNT(*) as total,
            SUM(CASE WHEN risk_level = 'HIGH' THEN 1 ELSE 0 END) as high_risk_count,
            SUM(CASE WHEN risk_level = 'MEDIUM' THEN 1 ELSE 0 END) as medium_risk_count,
            SUM(CASE WHEN risk_level = 'LOW' THEN 1 ELSE 0 END) as low_risk_count"
        )
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json($trends);
    }
}
