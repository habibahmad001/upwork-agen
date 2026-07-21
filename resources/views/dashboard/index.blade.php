@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="header">
        <h2>Dashboard</h2>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['jobs_total']) }}</div>
            <div class="stat-label">Total Jobs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['jobs_today']) }}</div>
            <div class="stat-label">Jobs Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['jobs_notified']) }}</div>
            <div class="stat-label">Notified</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['avg_score'], 1) }}</div>
            <div class="stat-label">Avg Score</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['crawler_sessions_active'] }}</div>
            <div class="stat-label">Active Crawlers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size: 1.25rem;">{{ $stats['last_crawl'] }}</div>
            <div class="stat-label">Last Crawl</div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">Recent Jobs</div>
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Score</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentJobs as $job)
                    <tr>
                        <td>
                            <a href="{{ route('jobs.show', $job) }}" style="color: #3b82f6; text-decoration: none;">
                                {{ Str::limit($job->title, 60) }}
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
                        <td>{{ $job->budget_range }}</td>
                        <td>
                            <span class="badge badge-{{ $job->status === 'notified' ? 'green' : ($job->status === 'scored' ? 'blue' : 'gray') }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td style="color: #6b7280; font-size: 0.875rem;">{{ $job->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <div class="card-header">Recent Crawler Activity</div>
            <div class="card-body" style="padding: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Jobs</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCrawls as $crawl)
                        <tr>
                            <td>
                                <span class="badge badge-{{ $crawl->status === 'success' ? 'green' : 'red' }}">
                                    {{ $crawl->status }}
                                </span>
                            </td>
                            <td>{{ $crawl->jobs_found ?? 0 }}</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">{{ $crawl->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Recent Errors</div>
            <div class="card-body" style="padding: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Source</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentErrors as $error)
                        <tr>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($error->message, 50) }}</td>
                            <td><span class="badge badge-gray">{{ $error->source }}</span></td>
                            <td style="color: #6b7280; font-size: 0.875rem;">{{ $error->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
