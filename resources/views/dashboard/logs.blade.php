@extends('layouts.app')

@section('title', 'Logs')

@section('content')
    <div class="header">
        <h2>System Logs</h2>
        <div>
            <form method="GET" action="{{ route('logs.index') }}" style="display: flex; gap: 0.5rem;">
                <select name="type" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="">All Types</option>
                    <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ request('type') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="error" {{ request('type') === 'error' ? 'selected' : '' }}>Error</option>
                    <option value="debug" {{ request('type') === 'debug' ? 'selected' : '' }}>Debug</option>
                </select>
                <input type="number" name="limit" value="{{ request('limit', 100) }}" placeholder="Limit" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; width: 100px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('logs.index') }}" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <div class="stats" style="margin-bottom: 1rem;">
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem;">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #3b82f6;">{{ number_format($stats['info']) }}</div>
            <div class="stat-label">Info</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #d97706;">{{ number_format($stats['warning']) }}</div>
            <div class="stat-label">Warning</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #dc2626;">{{ number_format($stats['error']) }}</div>
            <div class="stat-label">Error</div>
        </div>
        <div class="stat-card" style="padding: 1rem;">
            <div class="stat-value" style="font-size: 1.25rem; color: #6b7280;">{{ number_format($stats['debug']) }}</div>
            <div class="stat-label">Debug</div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
        <form method="POST" action="{{ route('logs.clear') }}" style="display: inline;">
            @csrf
            <input type="hidden" name="days" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Clear logs older than 1 day?')">Clear Old Logs</button>
        </form>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Type</th>
                        <th>Message</th>
                        <th style="width: 100px;">Source</th>
                        <th style="width: 150px;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>
                            <span class="badge badge-{{ $log->type === 'error' ? 'red' : ($log->type === 'warning' ? 'yellow' : ($log->type === 'debug' ? 'gray' : 'blue')) }}">
                                {{ ucfirst($log->type) }}
                            </span>
                        </td>
                        <td style="max-width: 500px;">
                            {{ Str::limit($log->message, 200) }}
                            @if($log->context)
                            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">{{ json_encode($log->context) }}</div>
                            @endif
                        </td>
                        <td><span class="badge badge-gray">{{ $log->source }}</span></td>
                        <td style="font-size: 0.875rem; color: #6b7280;">{{ $log->created_at }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
