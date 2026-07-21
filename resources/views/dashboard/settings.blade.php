@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="header">
        <h2>Settings</h2>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" style="max-width: 800px;">
        @csrf

        <!-- Crawler Settings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">Crawler Settings</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Enabled</label>
                        <select name="crawler[enabled]" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                            <option value="1" {{ $settings['crawler']['enabled'] ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$settings['crawler']['enabled'] ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Interval (seconds)</label>
                        <input type="number" name="crawler[interval]" value="{{ $settings['crawler']['interval'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Timeout (seconds)</label>
                        <input type="number" name="crawler[timeout]" value="{{ $settings['crawler']['timeout'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Max Jobs</label>
                        <input type="number" name="crawler[max_jobs]" value="{{ $settings['crawler']['max_jobs'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Settings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">AI Settings</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Provider</label>
                        <select name="ai[provider]" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                            <option value="mock" {{ $settings['ai']['provider'] === 'mock' ? 'selected' : '' }}>Mock (Keyword Matching)</option>
                            <option value="openai" {{ $settings['ai']['provider'] === 'openai' ? 'selected' : '' }}>OpenAI</option>
                            <option value="groq" {{ $settings['ai']['provider'] === 'groq' ? 'selected' : '' }}>Groq</option>
                            <option value="ollama" {{ $settings['ai']['provider'] === 'ollama' ? 'selected' : '' }}>Ollama</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Model</label>
                        <input type="text" name="ai[model]" value="{{ $settings['ai']['model'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Threshold (0-100)</label>
                        <input type="number" name="ai[threshold]" value="{{ $settings['ai']['threshold'] }}" step="0.1" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">OpenAI API Key</label>
                        <input type="password" name="ai[openai_key]" value="{{ $settings['ai']['openai_key'] ?? '' }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Groq API Key</label>
                        <input type="password" name="ai[groq_key]" value="{{ $settings['ai']['groq_key'] ?? '' }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">Notification Settings</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Enabled</label>
                        <select name="notification[enabled]" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                            <option value="1" {{ $settings['notification']['enabled'] ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$settings['notification']['enabled'] ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Notification Method</label>
                        <select name="notification[method]" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                            <option value="email" {{ ($settings['notification']['method'] ?? 'email') === 'email' ? 'selected' : '' }}>Email</option>
                            <option value="whatsapp" {{ ($settings['notification']['method'] ?? 'email') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="both" {{ ($settings['notification']['method'] ?? 'email') === 'both' ? 'selected' : '' }}>Both</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email Address</label>
                        <input type="email" name="notification[email_address]" value="{{ $settings['notification']['email_address'] ?? config('mail.notification_recipient') }}" placeholder="your@email.com" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Rate Limit (per minute)</label>
                        <input type="number" name="notification[rate_limit]" value="{{ $settings['notification']['rate_limit'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <h4 style="margin: 0 0 1rem 0; font-size: 0.875rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">WhatsApp Configuration (optional)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">WhatsApp Phone Number</label>
                            <input type="text" name="notification[phone_number]" value="{{ $settings['notification']['phone_number'] }}" placeholder="+1234567890" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Phone ID (Meta)</label>
                            <input type="text" name="notification[phone_id]" value="{{ $settings['notification']['phone_id'] ?? '' }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Access Token</label>
                            <input type="password" name="notification[access_token]" value="{{ $settings['notification']['access_token'] ?? '' }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Settings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">Filter Settings</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Min Budget ($)</label>
                        <input type="number" name="filter[budget_min]" value="{{ $settings['filter']['budget_min'] }}" step="0.01" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Min Hourly Rate ($)</label>
                        <input type="number" name="filter[hourly_min]" value="{{ $settings['filter']['hourly_min'] }}" step="0.01" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Require Payment Verified</label>
                        <select name="filter[require_payment_verified]" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                            <option value="1" {{ $settings['filter']['require_payment_verified'] ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$settings['filter']['require_payment_verified'] ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">System Settings</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Log Retention (days)</label>
                        <input type="number" name="system[log_retention_days]" value="{{ $settings['system']['log_retention_days'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Job Retention (hours)</label>
                        <input type="number" name="system[job_retention_hours]" value="{{ $settings['system']['job_retention_hours'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Cleanup Interval (minutes)</label>
                        <input type="number" name="system[cleanup_interval]" value="{{ $settings['system']['cleanup_interval'] }}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <form method="POST" action="{{ route('settings.reset') }}" style="display: inline;">
                @csrf
                <input type="hidden" name="confirm" value="RESET">
                <button type="submit" class="btn btn-danger">Reset to Defaults</button>
            </form>
        </div>
    </form>
@endsection
