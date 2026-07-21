@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
    <div class="header">
        <h2>Jobs</h2>
        <div>
            <form method="GET" action="{{ route('jobs.index') }}" style="display: flex; gap: 0.5rem; align-items: center;">
                <select name="status" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="">All Status</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="scoring" {{ request('status') === 'scoring' ? 'selected' : '' }}>Scoring</option>
                    <option value="scored" {{ request('status') === 'scored' ? 'selected' : '' }}>Scored</option>
                    <option value="notified" {{ request('status') === 'notified' ? 'selected' : '' }}>Notified</option>
                    <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                </select>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <div class="stats" style="margin-bottom: 1rem;">
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem;">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #6b7280;">{{ number_format($stats['new']) }}</div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #3b82f6;">{{ number_format($stats['scored']) }}</div>
            <div class="stat-label">Scored</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #059669;">{{ number_format($stats['notified']) }}</div>
            <div class="stat-label">Notified</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #dc2626;">{{ number_format($stats['skipped']) }}</div>
            <div class="stat-label">Skipped</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th style="width: 80px;">Score</th>
                        <th style="width: 120px;">Budget</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px;">Skills</th>
                        <th style="width: 100px;">Created</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>
                            <a href="{{ route('jobs.show', $job) }}" style="color: #3b82f6; text-decoration: none; font-weight: 500;">
                                {{ Str::limit($job->title, 70) }}
                            </a>
                        </td>
                        <td>
                            @if($job->aiScore)
                                <span class="score @if($job->aiScore->score >= 80) score-high @elseif($job->aiScore->score >= 50) score-medium @else score-low @endif">
                                    {{ number_format($job->aiScore->score, 1) }}
                                </span>
                            @else
                                <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                        <td style="font-size: 0.875rem;">{{ $job->budget_range }}</td>
                        <td>
                            <span class="badge badge-{{ $job->status === 'notified' ? 'green' : ($job->status === 'scored' ? 'blue' : ($job->status === 'skipped' ? 'red' : 'gray')) }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td style="font-size: 0.75rem; color: #6b7280;">
                            {{ implode(', ', array_slice($job->skills_list, 0, 3)) }}{{ $job->skills->count() > 3 ? '...' : '' }}
                        </td>
                        <td style="font-size: 0.875rem; color: #6b7280;">{{ $job->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($jobs->hasPages())
        <div style="padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; gap: 0.5rem;">
            @if($jobs->onFirstPage())
                <span class="btn btn-secondary" disabled>Previous</span>
            @else
                <a href="{{ $jobs->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
            @endif
            <span style="padding: 0.5rem 1rem;">{{ $jobs->currentPage() }} / {{ $jobs->lastPage() }}</span>
            @if($jobs->hasMorePages())
                <a href="{{ $jobs->nextPageUrl() }}" class="btn btn-secondary">Next</a>
            @else
                <span class="btn btn-secondary" disabled>Next</span>
            @endif
        </div>
        @endif
    </div>
@endsection
