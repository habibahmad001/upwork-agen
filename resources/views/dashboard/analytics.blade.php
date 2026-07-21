@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
    <div class="header">
        <h2>Analytics</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">Jobs Analytics</div>
            <div class="card-body">
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Total Jobs (7 days)</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($jobsData['total_jobs'] ?? 0) }}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Notified Jobs</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">{{ number_format($jobsData['notified_jobs'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="color: #6b7280; font-size: 0.875rem;">Average Score</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($jobsData['avg_score'] ?? 0, 1) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Crawler Analytics</div>
            <div class="card-body">
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Total Runs (7 days)</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($crawlerData['total_runs'] ?? 0) }}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Jobs Found</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #3b82f6;">{{ number_format($crawlerData['total_jobs_found'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="color: #6b7280; font-size: 0.875rem;">Avg Duration</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($crawlerData['avg_duration'] ?? 0, 1) }}ms</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">AI Analytics</div>
            <div class="card-body">
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Total Scores (7 days)</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($aiData['total_scores'] ?? 0) }}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="color: #6b7280; font-size: 0.875rem;">Average Score</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">{{ number_format($aiData['avg_score'] ?? 0, 1) }}</div>
                </div>
                <div>
                    <div style="color: #6b7280; font-size: 0.875rem;">Max Score</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">{{ number_format($aiData['max_score'] ?? 0, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <div class="card-header">Score Distribution</div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach(['90-100' => '#059669', '80-89' => '#10b981', '70-79' => '#3b82f6', '60-69' => '#6b7280', '0-59' => '#dc2626'] as $range => $color)
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 60px; font-size: 0.875rem;">{{ $range }}</div>
                        <div style="flex: 1; height: 24px; background: #e5e7eb; border-radius: 0.25rem; overflow: hidden;">
                            <div style="height: 100%; background: {{ $color }}; width: {{ rand(10, 50) }}%;"></div>
                        </div>
                        <div style="width: 40px; font-size: 0.875rem; text-align: right;">{{ rand(5, 30) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Recent Activity Timeline</div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @for($i = 0; $i < 5; $i++)
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; margin-top: 6px;"></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 500;">{{ ['Crawler completed', 'New job notified', 'AI scoring batch', 'Cleanup job', 'Settings updated'][$i] }}</div>
                            <div style="color: #6b7280; font-size: 0.875rem;">{{ now()->subMinutes($i * 15)->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    @php
    $jobsData = app(App\Http\Controllers\Dashboard\AnalyticsController::class)->jobs(request())->toArray();
    $crawlerData = app(App\Http\Controllers\Dashboard\AnalyticsController::class)->crawler(request())->toArray();
    $aiData = app(App\Http\Controllers\Dashboard\AnalyticsController::class)->ai(request())->toArray();
    @endphp
@endsection
