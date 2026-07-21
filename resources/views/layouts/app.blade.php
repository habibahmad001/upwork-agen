<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Upwork Job Agent')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #1f2937; color: white; padding: 1rem; }
        .sidebar h1 { font-size: 1.25rem; margin-bottom: 2rem; }
        .nav a { display: block; color: #d1d5db; text-decoration: none; padding: 0.75rem 1rem; margin-bottom: 0.25rem; border-radius: 0.375rem; }
        .nav a:hover, .nav a.active { background: #374151; color: white; }
        .main { flex: 1; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2rem; font-weight: bold; color: #1f2937; }
        .stat-label { color: #6b7280; font-size: 0.875rem; }
        .card { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
        .card-body { padding: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; color: #374151; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; font-size: 0.875rem; cursor: pointer; border: none; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .score { font-weight: bold; }
        .score-high { color: #059669; }
        .score-medium { color: #d97706; }
        .score-low { color: #dc2626; }
    </style>
</head>
<body>
    @auth
    <div class="layout">
        <aside class="sidebar">
            <h1>🔔 Job Agent</h1>
            <nav class="nav">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*') ? 'active' : '' }}">Jobs</a>
                <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">Notifications</a>
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">Settings</a>
                <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">Logs</a>
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width: 100%;">Logout</button>
                </form>
            </nav>
        </aside>
        <main class="main">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    @else
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f5f5;">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 400px;">
            <h1 style="text-align: center; margin-bottom: 1.5rem;">Upwork Job Agent</h1>
            @if($errors->any())
                <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="/login">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
        </div>
    </div>
    @endauth
</body>
</html>
