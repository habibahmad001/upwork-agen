@extends('layouts.app')

@section('title', 'Notification Details')

@section('content')
    <div class="header">
        <h2>Notification Details</h2>
        <a href="{{ route('notifications.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div>
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">Message</div>
                <div class="card-body">
                    <div style="white-space: pre-wrap; font-family: monospace; font-size: 0.875rem; background: #f9fafb; padding: 1rem; border-radius: 0.375rem;">{{ $notification->message_content }}</div>
                </div>
            </div>

            @if($notification->error_message)
            <div class="card">
                <div class="card-header">Error Details</div>
                <div class="card-body">
                    <div style="color: #dc2626;">{{ $notification->error_message }}</div>
                </div>
            </div>
            @endif
        </div>

        <div>
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">Status</div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-{{ $notification->status === 'sent' ? 'green' : ($notification->status === 'failed' ? 'red' : 'gray') }}" style="font-size: 1rem;">
                            {{ ucfirst($notification->status) }}
                        </span>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Retries</div>
                        <div>{{ $notification->retry_count }}</div>
                    </div>
                    @if($notification->sent_at)
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Sent At</div>
                        <div>{{ $notification->sent_at }}</div>
                    </div>
                    @endif
                    @if($notification->last_retry_at)
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">Last Retry</div>
                        <div>{{ $notification->last_retry_at }}</div>
                    </div>
                    @endif
                    @if($notification->whatsapp_message_id)
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #6b7280; font-size: 0.875rem;">WhatsApp Message ID</div>
                        <div style="font-family: monospace; font-size: 0.75rem;">{{ $notification->whatsapp_message_id }}</div>
                    </div>
                    @endif
                    @if($notification->status === 'failed')
                    <form method="POST" action="{{ route('notifications.retry', $notification) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Retry Now</button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Job</div>
                <div class="card-body">
                    <div style="margin-bottom: 0.5rem; font-weight: 500;">{{ $notification->job->title }}</div>
                    <div style="margin-bottom: 0.5rem; color: #6b7280; font-size: 0.875rem;">{{ $notification->job->budget_range }}</div>
                    <a href="{{ route('jobs.show', $notification->job) }}" class="btn btn-sm btn-secondary" style="width: 100%;">View Job</a>
                </div>
            </div>
        </div>
    </div>
@endsection
