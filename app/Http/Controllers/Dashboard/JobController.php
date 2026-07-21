<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobController extends Controller
{
    /**
     * Display all jobs.
     */
    public function index(Request $request): View
    {
        $query = Job::with('aiScore', 'skills')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by score range
        if ($request->has('min_score')) {
            $query->whereHas('aiScore', function ($q) use ($request) {
                $q->where('score', '>=', $request->min_score);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $jobs = $query->paginate(50);

        $stats = [
            'total' => Job::count(),
            'new' => Job::where('status', 'new')->count(),
            'scoring' => Job::where('status', 'scoring')->count(),
            'scored' => Job::where('status', 'scored')->count(),
            'notified' => Job::where('status', 'notified')->count(),
            'skipped' => Job::where('status', 'skipped')->count(),
        ];

        return view('dashboard.jobs', compact('jobs', 'stats'));
    }

    /**
     * Display a single job.
     */
    public function show(Job $job): View
    {
        $job->load('aiScore', 'skills', 'notifications');

        return view('dashboard.jobs-show', compact('job'));
    }

    /**
     * Archive a job.
     */
    public function archive(Job $job): RedirectResponse
    {
        $job->delete();

        return redirect()
            ->route('jobs.show', $job)
            ->with('success', 'Job archived successfully.');
    }

    /**
     * Restore an archived job.
     */
    public function restore($id): RedirectResponse
    {
        $job = Job::onlyTrashed()->findOrFail($id);
        $job->restore();

        return redirect()
            ->route('jobs.show', $job->id)
            ->with('success', 'Job restored successfully.');
    }
}
