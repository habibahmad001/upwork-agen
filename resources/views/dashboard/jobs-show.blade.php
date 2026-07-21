@extends('layouts.app')

@section('title', $job->title)

@section('content')
    <div class="header">
        <h2>{{ $job->title }}</h2>
        <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Back to Jobs</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div>
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">Job Details</div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Budget</div>
                            <div style="font-weight: 500;">{{ $job->budget_range }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Client Country</div>
                            <div style="font-weight: 500;">{{ $job->client_country ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Payment Verified</div>
                            <div>{{ $job->payment_verified ? '✅ Yes' : '❌ No' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Client Rating</div>
                            <div>{{ $job->client_rating ? number_format($job->client_rating, 1) . '/5' : 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Proposals</div>
                            <div>{{ $job->proposals ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Experience</div>
                            <div>{{ $job->experience_level ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Project Length</div>
                            <div>{{ $job->project_length ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem;">Status</div>
                            <div>
                                <span class="badge badge-{{ $job->status === 'notified' ? 'green' : ($job->status === 'scored' ? 'blue' : 'gray') }}">
                                    {{ ucfirst($job->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if($job->url)
                    <div style="margin-top: 1rem;">
                        <a href="{{ $job->url }}" target="_blank" class="btn btn-primary">View on Upwork</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Description</div>
                <div class="card-body">
                    <div style="white-space: pre-wrap; line-height: 1.6;">{{ $job->description }}</div>
                </div>
            </div>
        </div>

        <div>
            @if($job->aiScore)
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">AI Score</div>
                <div class="card-body">
                    <div style="font-size: 3rem; font-weight: bold; text-align: center; margin-bottom: 1rem;" class="score-{{ $job->aiScore->score >= 80 ? 'high' : ($job->aiScore->score >= 50 ? 'medium' : 'low') }}">
                        {{ number_format($job->aiScore->score, 1) }}
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Reasoning</div>
                        <div>{{ $job->aiScore->reasoning }}</div>
                    </div>
                    @if($job->aiScore->technologies)
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Matched Skills</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.5rem;">
                            @foreach($job->aiScore->technologies as $tech)
                            <span class="badge badge-blue">{{ $tech }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($job->aiScore->red_flags)
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Red Flags</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.5rem;">
                            @foreach($job->aiScore->red_flags as $flag)
                            <span class="badge badge-red">{{ $flag }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Recommendation</div>
                        <div>{{ $job->aiScore->recommendation }}</div>
                    </div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">
                        Model: {{ $job->aiScore->model_version }} | Threshold: {{ $job->aiScore->threshold_used }}
                    </div>
                </div>
            </div>
            @endif

            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">Skills</div>
                <div class="card-body">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @foreach($job->skills as $skill)
                        <span class="badge badge-gray">{{ $skill->skill }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Notifications</div>
                <div class="card-body">
                    @if($job->notifications->count() > 0)
                        @foreach($job->notifications as $notification)
                        <div style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="badge badge-{{ $notification->status === 'sent' ? 'green' : ($notification->status === 'failed' ? 'red' : 'gray') }}">
                                    {{ ucfirst($notification->status) }}
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280;">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div style="color: #9ca3af;">No notifications sent</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
