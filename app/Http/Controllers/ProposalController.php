<?php

namespace App\Http\Controllers;

use App\Services\AI\GroqAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProposalController extends Controller
{
    public function __construct(
        private GroqAIService $ai
    ) {}

    /**
     * Generate a proposal for a job
     */
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'description' => 'required|string',
                'budget' => 'nullable|string',
                'skills' => 'nullable|array',
                'hourly_rate' => 'nullable|string',
                'job_type' => 'nullable|string',
            ]);

            $proposal = $this->ai->generateProposal($validated);

            return response()->json([
                'success' => true,
                'proposal' => $proposal
            ]);

        } catch (\Exception $e) {
            Log::error('Proposal generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
