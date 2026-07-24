<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\AnalysisResult;
use App\Services\CrawlerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Candidate::withCount('posts');

        // Filter by platform
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by risk level
        if ($request->has('risk_level')) {
            $query->whereHas('analysisResults', function ($q) use ($request) {
                $q->where('risk_level', $request->risk_level);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $candidates = $query->paginate(20);

        // Add risk summary to each candidate
        $candidates->getCollection()->transform(function ($candidate) {
            $candidate->risk_summary = $candidate->risk_summary;
            return $candidate;
        });

        return response()->json([
            'data' => $candidates->items(),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total()
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $candidate = Candidate::with(['posts' => function ($query) {
            $query->latest('posted_at')->limit(100);
        }, 'analysisResults' => function ($query) {
            $query->latest()->limit(100);
        }])->findOrFail($id);

        return response()->json([
            'profile' => [
                'id' => $candidate->id,
                'username' => $candidate->username,
                'full_name' => $candidate->full_name,
                'email' => $candidate->email,
                'platform' => $candidate->platform,
                'profile_url' => $candidate->profile_url,
                'avatar_url' => $candidate->avatar_url,
                'last_crawled_at' => $candidate->last_crawled_at
            ],
            'risk_summary' => $candidate->risk_summary,
            'posts' => $candidate->posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'text' => $post->display_text ?? $post->text,
                    'image_url' => $post->image_url,
                    'video_url' => $post->video_url,
                    'posted_at' => $post->posted_at,
                    'platform' => $post->platform,
                    'status' => $post->status,
                    'analysis' => $post->analysisResult ? [
                        'risk_score' => $post->analysisResult->risk_score,
                        'risk_level' => $post->analysisResult->risk_level,
                        'context_category' => $post->analysisResult->context_category,
                        'context_explanation' => $post->analysisResult->context_explanation
                    ] : null
                ];
            })
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:candidates',
            'full_name' => 'nullable|string',
            'email' => 'nullable|email',
            'platform' => 'required|in:twitter,facebook,instagram,tiktok,linkedin,threads',
            'profile_url' => 'nullable|url',
            'avatar_url' => 'nullable|url'
        ]);

        $candidate = Candidate::create($validated);

        return response()->json([
            'success' => true,
            'data' => $candidate
        ], 201);
    }

    public function risk($id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);

        return response()->json([
            'candidate_id' => $candidate->id,
            'username' => $candidate->username,
            'risk_summary' => $candidate->risk_summary,
        ]);
    }

    public function posts($id): JsonResponse
    {
        $candidate = Candidate::with(['posts' => function ($query) {
            $query->latest('posted_at')->limit(100);
        }, 'posts.analysisResult'])->findOrFail($id);

        return response()->json([
            'candidate_id' => $candidate->id,
            'posts' => $candidate->posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'text' => $post->display_text,
                    'image_url' => $post->image_url,
                    'video_url' => $post->video_url,
                    'posted_at' => $post->posted_at,
                    'platform' => $post->platform,
                    'status' => $post->status,
                    'analysis' => $post->analysisResult ? [
                        'risk_score' => $post->analysisResult->risk_score,
                        'risk_level' => $post->analysisResult->risk_level,
                        'context_category' => $post->analysisResult->context_category,
                        'context_explanation' => $post->analysisResult->context_explanation,
                    ] : null
                ];
            })
        ]);
    }

    public function crawl(Request $request, $id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $crawler = app(CrawlerService::class);

        try {
            $limit = $request->input('limit', 50);
            $posts = $crawler->crawlCandidate($candidate, $limit);

            return response()->json([
                'success' => true,
                'message' => "Crawled " . count($posts) . " posts for {$candidate->username}",
                'posts_count' => count($posts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function crawlAll(Request $request): JsonResponse
    {
        $crawler = app(CrawlerService::class);
        $candidates = Candidate::needsCrawling()->get();

        $results = [];
        foreach ($candidates as $candidate) {
            try {
                $posts = $crawler->crawlCandidate($candidate, 50);
                $results[] = [
                    'candidate' => $candidate->username,
                    'status' => 'success',
                    'posts' => count($posts)
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'candidate' => $candidate->username,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Candidate deleted successfully'
        ]);
    }
}
