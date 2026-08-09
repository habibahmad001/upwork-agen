<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jobs Detail Listing</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: white; text-align: center; margin-bottom: 20px; font-size: 28px; }

        .job-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .job-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .job-id {
            font-size: 11px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #dbeafe;
            color: #1e40af;
        }
        .posted-time { font-size: 13px; color: #6b7280; font-weight: 500; }

        .price-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            font-size: 18px;
        }
        .price-amount { font-size: 22px; }
        .price-type { font-size: 13px; opacity: 0.95; text-transform: uppercase; letter-spacing: 0.5px; }

        .job-title {
            padding: 20px 24px 12px;
            font-size: 20px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1.4;
        }

        .job-description {
            padding: 0 24px;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.7;
        }
        .description-text {
            max-height: 60px;
            overflow: hidden;
            position: relative;
            transition: max-height 0.3s;
        }
        .description-text.expanded { max-height: none; }
        .expand-btn {
            color: #6366f1;
            background: none;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 0;
        }
        .expand-btn:hover { text-decoration: underline; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px 24px;
            padding: 16px 24px;
            font-size: 13px;
            background: #f9fafb;
            border-radius: 12px;
            margin: 16px 24px;
        }
        .info-label { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 700; }
        .info-value { font-weight: 600; color: #1f2937; }
        .verified-badge { display: inline-flex; align-items: center; gap: 4px; color: #059669; font-weight: 600; }
        .proposals-badge { display: inline-flex; align-items: center; gap: 4px; color: #6366f1; font-weight: 600; }

        .client-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 20px 24px;
            border-top: 1px solid #f59e0b;
            border-bottom: 1px solid #f59e0b;
        }
        .client-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .client-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f59e0b;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .client-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 20px;
        }
        .client-stat {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .client-stat-value { font-weight: 700; color: #78350f; }
        .client-stat-label { font-size: 12px; color: #92400e; }

        .skills-section {
            padding: 12px 24px;
            border-top: 1px solid #f3f4f6;
        }
        .skills-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 700; }
        .skills-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-tag {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #4338ca;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .ai-section {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            padding: 16px 24px;
            border-left: 4px solid #0284c7;
        }
        .ai-summary { font-size: 13px; color: #0c4a6e; line-height: 1.5; margin-bottom: 8px; }
        .ai-score { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #0c4a6e; }
        .stars { color: #f59e0b; letter-spacing: 2px; }
        .score-value { font-size: 18px; }
        .recommendation {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-left: 8px;
            font-weight: 700;
        }
        .recommendation.apply { background: #10b981; color: white; }
        .recommendation.skip { background: #ef4444; color: white; }
        .recommendation.consider { background: #f59e0b; color: white; }

        .action-bar {
            padding: 16px 24px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .job-link a {
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .job-link a:hover { text-decoration: underline; }
        .proposal-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .proposal-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.9);
        }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.6; }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .refresh-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .refresh-btn:hover { background: rgba(255,255,255,0.3); }

        .stats-bar {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-item h3 { font-size: 28px; font-weight: 800; color: white; margin-bottom: 4px; }
        .stat-item p { font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px; }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1f2937;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 2000;
            display: none;
        }
        .toast.show { display: block; animation: slideDown 0.3s ease; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Jobs Detail Listing</h1>

        <div class="stats-bar">
            <div class="stat-item">
                <h3 id="totalJobs">0</h3>
                <p>Total Jobs</p>
            </div>
            <div class="stat-item">
                <h3 id="todayJobs">0</h3>
                <p>Posted Today</p>
            </div>
            <div class="stat-item">
                <h3 id="avgScore">0</h3>
                <p>Avg AI Score</p>
            </div>
            <div class="stat-item">
                <button class="refresh-btn" onclick="refreshJobs()">🔄 Refresh Jobs</button>
            </div>
        </div>

        <div id="jobsContainer"></div>

        <div id="emptyState" class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p style="font-size: 18px; margin-bottom: 8px;">No jobs loaded yet</p>
            <p style="font-size: 14px; opacity: 0.8;">Click "Refresh Jobs" to load the latest jobs from the server</p>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="proposal-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">📝 Quick Proposal</span>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="proposalContent" class="proposal-text">Generating proposal...</div>
            </div>
            <div class="modal-footer">
                <button class="copy-btn" onclick="copyProposal()" id="copyBtn">📋 Copy Proposal</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <style>
        .proposal-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .proposal-modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 650px;
            width: 90%;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }
        .modal-title { font-weight: 700; font-size: 18px; color: #1f2937; }
        .close-btn { background: none; border: none; font-size: 28px; cursor: pointer; color: #6b7280; }
        .close-btn:hover { color: #1f2937; }
        .modal-body { padding: 24px; overflow-y: auto; flex: 1; }
        .proposal-text {
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.8;
            color: #374151;
            font-family: 'Georgia', serif;
        }
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f9fafb;
        }
        .copy-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .copy-btn:hover { background: #059669; }
    </style>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        const CONFIG = {
            apiUrl: '/api/jobs-listing',
            pusher: {
                key: @js(config('services.pusher.app_key')),
                cluster: @js(config('services.pusher.cluster')),
                channel: @js(config('services.pusher.channel')),
                event: @js(config('services.pusher.event'))
            }
        };

        let allJobs = [];
        let jobCounter = 0;
        let pusher = null;
        let channel = null;

        // Load jobs on page load
        document.addEventListener('DOMContentLoaded', () => {
            refreshJobs();
            initPusher();
        });

        // Initialize Pusher
        function initPusher() {
            try {
                pusher = new Pusher(CONFIG.pusher.key, {
                    cluster: CONFIG.pusher.cluster,
                    enabledTransports: ['ws', 'wss'],
                });

                channel = pusher.subscribe(CONFIG.pusher.channel);

                channel.bind_global((eventName, data) => {
                    if (eventName === CONFIG.pusher.event && data.type !== 'test') {
                        allJobs.unshift(data);
                        prependNewJob(data);
                        updateStats(allJobs);
                        showToast(`🔔 New job: ${data.title?.substring(0, 40)}...`);

                        // Desktop notification
                        const emoji = data.emoji || '📌';
                        showDesktopNotification(emoji + ' ' + (data.title || 'New Job'), `AI Score: ${data.ai_score}/100 - ${data.ai_recommendation}`);
                    }
                });

                pusher.connection.bind('connected', () => {
                    console.log('✅ Pusher connected');
                });

                console.log('✅ Pusher initialized');
            } catch (err) {
                console.error('❌ Pusher init error:', err);
            }
        }

        async function refreshJobs() {
            const btn = document.querySelector('.refresh-btn');
            btn.disabled = true;
            btn.innerHTML = '🔄 Loading... <span class="spinner"></span>';

            try {
                const response = await fetch(CONFIG.apiUrl);
                const data = await response.json();
                allJobs = data.jobs || [];

                displayJobs(allJobs);
                updateStats(allJobs);
                showToast(`✅ Loaded ${allJobs.length} jobs`);
            } catch (error) {
                console.error('Error fetching jobs:', error);
                showToast('❌ Failed to load jobs');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔄 Refresh Jobs';
            }
        }

        function displayJobs(jobs) {
            const container = document.getElementById('jobsContainer');
            const emptyState = document.getElementById('emptyState');

            container.innerHTML = '';

            if (jobs.length === 0) {
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';

            const sortedJobs = [...jobs].sort((a, b) =>
                new Date(b.fetched_at || 0) - new Date(a.fetched_at || 0)
            );

            sortedJobs.forEach(job => {
                container.appendChild(createJobCard(job));
            });
        }

        function prependNewJob(job) {
            const container = document.getElementById('jobsContainer');
            const emptyState = document.getElementById('emptyState');

            if (emptyState.style.display !== 'none') {
                emptyState.style.display = 'none';
            }

            const jobCard = createJobCard(job);
            jobCard.style.animation = 'slideDown 0.5s ease';
            container.insertBefore(jobCard, container.firstChild);
        }

        function createJobCard(job) {
            const card = document.createElement('div');
            card.className = 'job-card';
            card.id = 'job-' + (++jobCounter);

            const stars = getStars(job.ai_score || 0);
            const recommendationClass = (job.ai_recommendation || 'consider').toLowerCase();

            card.innerHTML = `
                <div class="job-header">
                    <span class="job-id">ID: ${job.job_id || 'N/A'}</span>
                    <span class="posted-time">🕐 ${formatTime(job.fetched_at)}</span>
                </div>

                <div class="job-title">${escapeHtml(job.title || 'Untitled Job')}</div>

                <div class="job-description">
                    <div class="description-text" id="desc-${jobCounter}">
                        ${escapeHtml(job.description || 'No description available')}
                    </div>
                    <button class="expand-btn" onclick="toggleDescription('${jobCounter}')">Read more ↓</button>
                </div>

                <div style="padding: 16px 24px;">
                    <div class="price-badge">
                        <span class="price-amount">${formatPrice(job.budget, job.hourly_rate)}</span>
                        <span class="price-type">${job.job_type || 'Fixed-Price'}</span>
                    </div>
                </div>

                <div class="info-grid">
                    <div>
                        <div class="info-label">Client Rating</div>
                        <div class="info-value">${job.client_rating ? '⭐ ' + job.client_rating + '/5' : 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Proposals</div>
                        <div class="info-value proposals-badge">📊 ${job.proposals || '0'}</div>
                    </div>
                    <div>
                        <div class="info-label">Payment</div>
                        <div class="info-value">
                            ${job.payment_verified ? '<span class="verified-badge">✅ Verified</span>' : '⚠️ Not verified'}
                        </div>
                    </div>
                    <div>
                        <div class="info-label">Location</div>
                        <div class="info-value">🌍 ${job.client_country || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Time Posted</div>
                        <div class="info-value">⏰ ${job.time_posted || 'Just now'}</div>
                    </div>
                    <div>
                        <div class="info-label">Total Spent</div>
                        <div class="info-value">💰 ${job.client_spent || job.spent || 'N/A'}</div>
                    </div>
                </div>

                ${job.client_rating || job.client_jobs_posted || job.client_hires || job.client_spent ? `
                <div class="client-section">
                    <div class="client-header">
                        <span class="client-badge">👤 About the Client</span>
                    </div>
                    <div class="client-info-grid">
                        ${job.client_rating ? `<div class="client-stat"><span class="client-stat-value">⭐ ${job.client_rating}/5</span></div>` : ''}
                        ${job.client_jobs_posted ? `<div class="client-stat"><span class="client-stat-value">💼 ${job.client_jobs_posted}</span></div>` : ''}
                        ${job.client_hires ? `<div class="client-stat"><span class="client-stat-value">👥 ${job.client_hires} hires</span></div>` : ''}
                        ${job.client_spent || job.spent ? `<div class="client-stat"><span class="client-stat-value">💰 ${job.client_spent || job.spent}</span></div>` : ''}
                    </div>
                </div>
                ` : ''}

                ${job.skills && job.skills.length > 0 ? `
                <div class="skills-section">
                    <div class="skills-label">Required Skills</div>
                    <div class="skills-list">
                        ${job.skills.slice(0, 8).map(skill => `<span class="skill-tag">${escapeHtml(skill)}</span>`).join('')}
                    </div>
                </div>
                ` : ''}

                <div class="ai-section">
                    <div class="ai-summary">${escapeHtml(job.ai_reason || 'AI analysis not available')}</div>
                    <div class="ai-score">
                        <span class="stars">${stars}</span>
                        <span class="score-value">${job.ai_score || 0}/100</span>
                        <span class="recommendation ${recommendationClass}">${(job.ai_recommendation || 'CONSIDER').toUpperCase()}</span>
                    </div>
                </div>

                <div class="action-bar">
                    <div class="job-link">
                        <a href="${job.url || '#'}" target="_blank">🔗 View on Upwork →</a>
                    </div>
                    <button class="proposal-btn" onclick="generateProposal(this, ${JSON.stringify(job).replace(/"/g, '&quot;')})">
                        📝 Quick Proposal
                    </button>
                </div>
            `;

            return card;
        }

        function updateStats(jobs) {
            document.getElementById('totalJobs').textContent = jobs.length;

            const today = new Date().toDateString();
            const todayCount = jobs.filter(j =>
                new Date(j.fetched_at).toDateString() === today
            ).length;
            document.getElementById('todayJobs').textContent = todayCount;

            const avgScore = jobs.length > 0
                ? Math.round(jobs.reduce((sum, j) => sum + (j.ai_score || 0), 0) / jobs.length)
                : 0;
            document.getElementById('avgScore').textContent = avgScore;
        }

        function toggleDescription(cardId) {
            const desc = document.getElementById('desc-' + cardId);
            const btn = desc.nextElementSibling;
            desc.classList.toggle('expanded');
            btn.textContent = desc.classList.contains('expanded') ? 'Show less ↑' : 'Read more ↓';
        }

        function formatPrice(budget, hourlyRate) {
            if (!budget && !hourlyRate) return 'N/A';
            if (budget) {
                const numeric = budget.replace(/[^0-9.]/g, '');
                const parsed = parseFloat(numeric);
                if (!isNaN(parsed)) {
                    return '$' + parsed.toFixed(2);
                }
                return budget;
            }
            return hourlyRate || 'N/A';
        }

        function getStars(score) {
            const count = Math.ceil(score / 20);
            return '⭐'.repeat(Math.min(count, 5));
        }

        function formatTime(timestamp) {
            if (!timestamp) return 'Just now';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            return Math.floor(diff / 86400) + ' days ago';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function showDesktopNotification(title, body) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: 'https://cdn-icons-png.flaticon.com/512/2666/2666501.png',
                    tag: 'job-notification'
                });
            } else if ('Notification' in window && Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification(title, {
                            body: body,
                            icon: 'https://cdn-icons-png.flaticon.com/512/2666/2666501.png',
                            tag: 'job-notification'
                        });
                    }
                });
            }
        }

        // Proposal generation
        let currentProposal = '';
        let currentJobData = {};

        async function generateProposal(btn, jobData) {
            btn.disabled = true;
            btn.innerHTML = 'Generating... <span class="spinner"></span>';
            currentJobData = jobData;

            try {
                const proposal = await generateAIProposal();
                currentProposal = proposal;
                document.getElementById('proposalContent').textContent = proposal;
                document.getElementById('proposalModal').classList.add('active');
            } catch (error) {
                showToast('❌ Failed to generate proposal');
            }

            btn.disabled = false;
            btn.innerHTML = '📝 Quick Proposal';
        }

        async function generateAIProposal() {
            const response = await fetch('/api/proposal/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    title: currentJobData.title || '',
                    description: currentJobData.description || '',
                    budget: currentJobData.budget || currentJobData.hourly_rate || '',
                    skills: currentJobData.skills || [],
                    hourly_rate: currentJobData.hourly_rate || '',
                    job_type: currentJobData.job_type || ''
                })
            });

            if (!response.ok) {
                throw new Error('Failed to generate proposal');
            }

            const result = await response.json();
            return result.proposal || 'Failed to generate proposal';
        }

        function closeModal() {
            document.getElementById('proposalModal').classList.remove('active');
        }

        function copyProposal() {
            navigator.clipboard.writeText(currentProposal).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.textContent = '✅ Copied!';
                setTimeout(() => {
                    btn.textContent = '📋 Copy Proposal';
                }, 2000);
                showToast('✅ Proposal copied to clipboard!');
            });
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Close modal on backdrop click
        document.getElementById('proposalModal').addEventListener('click', (e) => {
            if (e.target.id === 'proposalModal') closeModal();
        });
    </script>
</body>
</html>
