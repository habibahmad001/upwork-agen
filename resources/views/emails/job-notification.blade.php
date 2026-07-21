<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upwork Job Match</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #14a800 0%, #0d7a00 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .score-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
            font-size: 18px;
        }
        .score-high { background: #ffd700 !important; color: #333 !important; }
        .score-medium { background: #87ceeb !important; color: #333 !important; }
        .score-low { background: #ffcccb !important; color: #333 !important; }
        .content {
            padding: 30px;
        }
        .job-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            color: #14a800;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .info-item {
            font-size: 14px;
        }
        .info-label {
            color: #666;
            font-size: 12px;
        }
        .info-value {
            font-weight: 500;
            color: #333;
        }
        .reasoning {
            background: #f0f7ff;
            border-left: 4px solid #14a800;
            padding: 15px;
            border-radius: 0 5px 5px 0;
        }
        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .skill-tag {
            background: #14a800;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
        }
        .red-flags {
            color: #d32f2f;
        }
        .red-flag {
            background: #ffebee;
            color: #c62828;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
        }
        .recommendation {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0 5px 5px 0;
            font-style: italic;
        }
        .btn {
            display: inline-block;
            background: #14a800;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .divider {
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 New Upwork Job Match</h1>
            <div class="score-badge {{ $aiScore->score >= 80 ? 'score-high' : ($aiScore->score >= 60 ? 'score-medium' : 'score-low') }}">
                Score: {{ $aiScore->score }}/100
            </div>
        </div>

        <div class="content">
            <div class="job-title">{{ $job->title }}</div>

            @if($job->budget_range)
            <div class="section">
                <div class="section-title">💰 Budget</div>
                <div>{{ $job->budget_range }}</div>
            </div>
            @endif

            <div class="section">
                <div class="section-title">👤 Client Information</div>
                <div class="info-grid">
                    @if($job->client_country)
                    <div class="info-item">
                        <div class="info-label">Country</div>
                        <div class="info-value">{{ $job->client_country }}</div>
                    </div>
                    @endif
                    @if($job->payment_verified)
                    <div class="info-item">
                        <div class="info-label">Payment</div>
                        <div class="info-value">✓ Verified</div>
                    </div>
                    @endif
                    @if($job->client_rating)
                    <div class="info-item">
                        <div class="info-label">Rating</div>
                        <div class="info-value">⭐ {{ number_format($job->client_rating, 1) }}/5</div>
                    </div>
                    @endif
                    @if($job->client_hires)
                    <div class="info-item">
                        <div class="info-label">Hires</div>
                        <div class="info-value">{{ $job->client_hires }}</div>
                    </div>
                    @endif
                </div>
            </div>

            @if($job->proposals || $job->experience_level || $job->project_length)
            <div class="section">
                <div class="section-title">📋 Job Details</div>
                <div class="info-grid">
                    @if($job->proposals)
                    <div class="info-item">
                        <div class="info-label">Proposals</div>
                        <div class="info-value">{{ $job->proposals }}</div>
                    </div>
                    @endif
                    @if($job->experience_level)
                    <div class="info-item">
                        <div class="info-label">Experience</div>
                        <div class="info-value">{{ $job->experience_level }}</div>
                    </div>
                    @endif
                    @if($job->project_length)
                    <div class="info-item">
                        <div class="info-label">Duration</div>
                        <div class="info-value">{{ $job->project_length }}</div>
                    </div>
                    @endif
                    @if($job->time_posted)
                    <div class="info-item">
                        <div class="info-label">Posted</div>
                        <div class="info-value">{{ $job->time_posted }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="divider"></div>

            @if($aiScore->reasoning)
            <div class="section">
                <div class="section-title">💡 Why This Matches</div>
                <div class="reasoning">{{ nl2br(e($aiScore->reasoning)) }}</div>
            </div>
            @endif

            @if(!empty($aiScore->technologies))
            <div class="section">
                <div class="section-title">🛠️ Matched Skills</div>
                <div class="skills">
                    @foreach($aiScore->technologies as $tech)
                    <span class="skill-tag">{{ e($tech) }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($aiScore->red_flags))
            <div class="section">
                <div class="section-title">⚠️ Flags</div>
                <div class="skills">
                    @foreach($aiScore->red_flags as $flag)
                    <span class="red-flag">{{ e($flag) }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($aiScore->recommendation)
            <div class="section">
                <div class="recommendation">
                    "{{ e($aiScore->recommendation) }}"
                </div>
            </div>
            @endif

            @if($job->url)
            <div style="text-align: center;">
                <a href="{{ $job->url }}" class="btn" target="_blank">View Job on Upwork →</a>
            </div>
            @endif
        </div>

        <div class="footer">
            <p>Generated by Upwork Job Agent</p>
            <p>{{ now()->format('M j, Y - g:i A') }}</p>
            <p style="margin-top: 10px; font-size: 11px; color: #999;">
                You received this because {{ config('mail.from.address') }} is configured to receive job notifications.
            </p>
        </div>
    </div>
</body>
</html>
