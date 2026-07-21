@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="header">
        <h2>Notifications</h2>
        <div>
            <form method="GET" action="{{ route('notifications.index') }}" style="display: flex; gap: 0.5rem;">
                <select name="status" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('notifications.index') }}" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <div class="stats" style="margin-bottom: 1rem;">
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem;">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #6b7280;">{{ number_format($stats['pending']) }}</div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #059669;">{{ number_format($stats['sent']) }}</div>
            <div class="stat-label">Sent</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #dc2626;">{{ number_format($stats['failed']) }}</div>
            <div class="stat-label">Failed</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th style="width: 80px;">Score</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 60px;">Method</th>
                        <th style="width: 120px;">Destination</th>
                        <th style="width: 80px;">Retries</th>
                        <th style="width: 100px;">Sent At</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                    <tr>
                        <td>
                            <a href="{{ route('jobs.show', $notification->job) }}" style="color: #3b82f6; text-decoration: none;">
                                {{ Str::limit($notification->job->title, 60) }}
                            </a>
                        </td>
                        <td>
                            @if($notification->aiScore)
                                <span class="score @if($notification->aiScore->score >= 80) score-high @elseif($notification->aiScore->score >= 50) score-medium @else score-low @endif">
                                    {{ number_format($notification->aiScore->score, 1) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $notification->status === 'sent' ? 'green' : ($notification->status === 'failed' ? 'red' : 'gray') }}">
                                {{ ucfirst($notification->status) }}
                            </span>
                        </td>
                        <td style="font-size: 0.75rem;">
                            @if($notification->method === 'email') 📧
                            @elseif($notification->method === 'whatsapp') 📱
                            @else 📧📱
                            @endif
                        </td>
                        <td style="font-size: 0.875rem;">{{ maskDestination($notification->method ?? 'email', $notification->destination ?? $notification->phone_number) }}</td>
                        <td>{{ $notification->retry_count }}</td>
                        <td style="font-size: 0.875rem;">{{ $notification->sent_at?->diffForHumans() ?? '-' }}</td>
                        <td>
                            <a href="{{ route('notifications.show', $notification) }}" class="btn btn-sm btn-primary">View</a>
                            @if($notification->status === 'failed')
                            <form method="POST" action="{{ route('notifications.retry', $notification) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-secondary">Retry</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())
        <div style="padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; gap: 0.5rem;">
            @if($notifications->onFirstPage())
                <span class="btn btn-secondary" disabled>Previous</span>
            @else
                <a href="{{ $notifications->previousPageUrl() }}" class="btn btn-secondary">Previous</a>
            @endif
            <span style="padding: 0.5rem 1rem;">{{ $notifications->currentPage() }} / {{ $notifications->lastPage() }}</span>
            @if($notifications->hasMorePages())
                <a href="{{ $notifications->nextPageUrl() }}" class="btn btn-secondary">Next</a>
            @else
                <span class="btn btn-secondary" disabled>Next</span>
            @endif
        </div>
        @endif
    </div>

    @php
    function maskDestination($method, $destination) {
        if ($method === 'whatsapp' || empty($method)) {
            // Mask phone number
            if (strlen($destination) < 4) return '****';
            return substr($destination, 0, -4) . '****';
        } else {
            // Mask email
            $parts = explode('@', $destination);
            if (count($parts) !== 2) return '***@***';
            $name = $parts[0];
            $domain = $parts[1];
            if (strlen($name) <= 2) {
                $nameMask = str_repeat('*', strlen($name));
            } else {
                $nameMask = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
            }
            return $nameMask . '@' . $domain;
        }
    }
    @endphp
@endsection
