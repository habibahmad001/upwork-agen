<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Job Notifications Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: white; text-align: center; margin-bottom: 20px; font-size: 24px; }

        /* Enable Notifications Banner */
        .enable-notifications {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .enable-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .enable-btn:hover { transform: scale(1.05); }
        .enable-btn.granted { background: #10b981; color: white; display: none; }

        /* Job Cards */
        .job-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Header with event type and time */
        .job-header {
            background: #f8fafc;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .event-type {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .event-type.new-job { background: #d1fae5; color: #065f46; }
        .event-type.test { background: #dbeafe; color: #1e40af; }
        .event-time { font-size: 12px; color: #6b7280; }

        /* Two Column Info Grid */
        .job-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
            padding: 16px 20px;
            font-size: 13px;
        }
        .info-label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
        .info-value { font-weight: 500; color: #1f2937; }
        .client-rating { color: #f59e0b; font-weight: 600; }
        .verified-badge { display: inline-flex; align-items: center; gap: 4px; color: #059669; }
        .skills-list { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .skill-tag { background: #f3f4f6; padding: 3px 8px; border-radius: 4px; font-size: 11px; color: #4b5563; }

        /* AI Section */
        .ai-section {
            background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);
            padding: 16px 20px;
            border-left: 4px solid #f59e0b;
        }
        .ai-summary { font-size: 13px; color: #92400e; line-height: 1.5; margin-bottom: 8px; }
        .ai-score { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #92400e; }
        .stars { color: #f59e0b; letter-spacing: 2px; }
        .score-value { font-size: 16px; }
        .recommendation { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px; }

        /* Title */
        .job-title {
            padding: 16px 20px 8px;
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.4;
        }

        /* Description */
        .job-description {
            padding: 0 20px;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
        }
        .description-text {
            max-height: 40px;
            overflow: hidden;
            position: relative;
        }
        .description-text.expanded { max-height: none; }
        .expand-btn {
            color: #667eea;
            background: none;
            border: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            padding: 4px 0;
            margin-left: -4px;
        }
        .expand-btn:hover { text-decoration: underline; }

        /* Link */
        .job-link {
            padding: 8px 20px;
        }
        .job-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        .job-link a:hover { text-decoration: underline; }

        /* Action Section */
        .job-actions {
            padding: 12px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }
        .proposal-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .proposal-btn:hover { background: #5a67d8; transform: translateY(-1px); }
        .proposal-btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }

        /* Generated Proposal Modal */
        .proposal-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .proposal-modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title { font-weight: 600; font-size: 16px; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        .proposal-text {
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
            font-family: 'Georgia', serif;
        }
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .copy-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .copy-btn.copied { background: #059669; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.8);
        }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; opacity: 0.5; }

        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Job Notifications Dashboard</h1>

        <div id="notificationPrompt" class="enable-notifications">
            <button id="enableBtn" class="enable-btn">Enable Desktop Notifications 🔔</button>
            <p style="color: white; margin-top: 10px; font-size: 13px; opacity: 0.9;">
                Get notified even when this tab is in the background
            </p>
        </div>

        <!-- Connection Status -->
        <div id="connectionStatus" class="job-card" style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-size: 13px; color: #6b7280;">Pusher Status:</span>
                <span id="pusherStatus" style="margin-left: 8px; font-weight: 600;">Connecting...</span>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <span style="font-size: 12px; color: #6b7280;">
                    <span id="channelInfo">Channel: jobs | Event: new-job</span>
                </span>
                <button onclick="testPusherConnection()" style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                    Test Connection
                </button>
            </div>
        </div>

        <div id="jobsContainer"></div>

        <div id="emptyState" class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p>Waiting for new jobs...</p>
            <p style="font-size: 12px; opacity: 0.7;">Keep this page open to receive instant notifications</p>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="proposal-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Quick Proposal</span>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="proposalContent" class="proposal-text">Generating proposal...</div>
            </div>
            <div class="modal-footer">
                <button class="copy-btn" onclick="copyProposal()" id="copyBtn">Copy Proposal</button>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        const CONFIG = {
            key: @js(config('services.pusher.app_key')),
            cluster: @js(config('services.pusher.cluster')),
            channel: @js(config('services.pusher.channel')),
            event: @js(config('services.pusher.event'))
        };

        let currentProposal = '';
        let currentJobData = {};
        let jobCounter = 0;

        // Initialize Pusher
        const pusher = new Pusher(CONFIG.key, {
            cluster: CONFIG.cluster,
            enabledTransports: ['ws', 'wss'],
        });

        const channel = pusher.subscribe(CONFIG.channel);

        // Connection status updates
        pusher.connection.bind('connected', () => {
            updateConnectionStatus(true);
            console.log('✅ Pusher connected');
        });

        pusher.connection.bind('disconnected', () => {
            updateConnectionStatus(false);
            console.log('❌ Pusher disconnected');
        });

        pusher.connection.bind('error', (err) => {
            updateConnectionStatus(false);
            console.error('❌ Pusher error:', err);
        });

        function updateConnectionStatus(connected) {
            const status = document.getElementById('pusherStatus');
            if (connected) {
                status.textContent = '✅ Connected';
                status.style.color = '#10b981';
                console.log('✅ Pusher Connected - Listening on channel:', CONFIG.channel);
            } else {
                status.textContent = '❌ Disconnected';
                status.style.color = '#ef4444';
            }
        }

        channel.bind_global((eventName, data) => {
            console.log('📩 Event received:', eventName, data);

            // Show ALL events (including test) for debugging
            if (data.type === 'test') {
                console.log('🧪 Test event received (not displayed)');
                return;
            }

            addJobCard(eventName, data);
        });

        // Notification Permission
        const enableBtn = document.getElementById('enableBtn');
        const notificationPrompt = document.getElementById('notificationPrompt');

        function checkNotificationPermission() {
            if (!('Notification' in window)) {
                enableBtn.textContent = 'Not Supported';
                enableBtn.disabled = true;
                return;
            }

            if (Notification.permission === 'granted') {
                enableBtn.classList.add('granted');
                setTimeout(() => notificationPrompt.style.display = 'none', 2000);
            }
        }

        enableBtn.addEventListener('click', () => {
            Notification.requestPermission().then(permission => {
                checkNotificationPermission();
                if (permission === 'granted') {
                    new Notification('Job Notifications', {
                        body: 'Desktop notifications enabled!',
                        icon: 'https://cdn-icons-png.flaticon.com/512/2666/2666501.png'
                    });
                }
            });
        });

        checkNotificationPermission();

        function showDesktopNotification(title, body) {
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: 'https://cdn-icons-png.flaticon.com/512/2666/2666501.png',
                    tag: 'job-notification'
                });
            }
        }

        // Add Job Card
        function addJobCard(eventName, data) {
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.remove();

            const container = document.getElementById('jobsContainer');
            const cardId = 'job-' + (++jobCounter);

            const stars = getStars(data.ai_score || 0);

            const card = document.createElement('div');
            card.className = 'job-card';
            card.id = cardId;
            card.innerHTML = `
                <div class="job-header">
                    <span class="event-type new-job">${eventName}</span>
                    <span class="event-time">${formatTime(data.fetched_at || data.timestamp)}</span>
                </div>

                <div class="job-info-grid">
                    <div>
                        <div class="info-label">Budget</div>
                        <div class="info-value">${data.hourly_rate || data.budget || 'Not specified'} ${data.hourly_rate ? '(Hourly)' : '(Fixed)'}</div>
                    </div>
                    <div>
                        <div class="info-label">Client Rating</div>
                        <div class="info-value client-rating">${data.client_rating ? '⭐ ' + data.client_rating + '/5' : 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Type</div>
                        <div class="info-value">${data.job_type || 'Not specified'}</div>
                    </div>
                    <div>
                        <div class="info-label">Posted</div>
                        <div class="info-value">${data.time_posted || 'Just now'}</div>
                    </div>
                    <div>
                        <div class="info-label">Payment</div>
                        <div class="info-value">${data.payment_verified ? '<span class="verified-badge">✅ Verified</span>' : '⚠️ Not verified'}</div>
                    </div>
                    <div>
                        <div class="info-label">Location</div>
                        <div class="info-value">${data.client_country || 'N/A'}</div>
                    </div>
                    <div>
                        <div class="info-label">Proposals</div>
                        <div class="info-value">${data.proposals || '0'}</div>
                    </div>
                    <div>
                        <div class="info-label">Skills</div>
                        <div class="info-value">
                            <div class="skills-list">
                                ${(data.skills || []).slice(0, 5).map(s => `<span class="skill-tag">${s}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ai-section">
                    <div class="ai-summary">${data.ai_reason || 'AI analysis not available'}</div>
                    <div class="ai-score">
                        <span class="stars">${stars}</span>
                        <span class="score-value">${data.ai_score || 0}/100</span>
                        <span class="recommendation">${data.ai_recommendation || 'CONSIDER'}</span>
                    </div>
                </div>

                <div class="job-title">${data.title || 'Untitled Job'}</div>

                <div class="job-description">
                    <div class="description-text" id="desc-${cardId}">
                        ${escapeHtml(data.description || 'No description available')}
                    </div>
                    <button class="expand-btn" onclick="toggleDescription('${cardId}')">Read more</button>
                </div>

                <div class="job-link">
                    <a href="${data.url || '#'}" target="_blank">🔗 View Job on Upwork →</a>
                </div>

                <div class="job-actions">
                    <button class="proposal-btn" onclick="generateProposal(this, '${cardId}', ${JSON.stringify(data).replace(/"/g, '&quot;')})">
                        Quick Proposal
                    </button>
                </div>
            `;

            container.insertBefore(card, container.firstChild);

            // Desktop notification
            showDesktopNotification(data.emoji + ' ' + (data.title || 'New Job'), `AI Score: ${data.ai_score}/100 - ${data.ai_recommendation}`);
        }

        function toggleDescription(cardId) {
            const desc = document.getElementById('desc-' + cardId);
            const btn = desc.nextElementSibling;
            desc.classList.toggle('expanded');
            btn.textContent = desc.classList.contains('expanded') ? 'Show less' : 'Read more';
        }

        function getStars(score) {
            const count = Math.ceil(score / 20);
            return '⭐'.repeat(Math.min(count, 5));
        }

        function formatTime(timestamp) {
            if (!timestamp) return 'Just now';
            const date = new Date(timestamp);
            return date.toLocaleTimeString();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Generate Proposal
        async function generateProposal(btn, cardId, jobData) {
            btn.disabled = true;
            btn.innerHTML = 'Generating... <span class="spinner"></span>';
            currentJobData = jobData;

            try {
                const proposal = await generateAIProposal();
                currentProposal = proposal;
                document.getElementById('proposalContent').textContent = proposal;
                document.getElementById('proposalModal').classList.add('active');
            } catch (error) {
                alert('Failed to generate proposal. Please try again.');
            }

            btn.disabled = false;
            btn.innerHTML = 'Quick Proposal';
        }

        async function generateAIProposal(prompt) {
            // Call Laravel backend for proposal generation
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
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = 'Copy Proposal';
                    btn.classList.remove('copied');
                }, 2000);
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

        // Test Pusher Connection
        function testPusherConnection() {
            console.log('🧪 Testing Pusher connection...');
            console.log('Channel:', CONFIG.channel);
            console.log('Event:', CONFIG.event);
            console.log('Key:', CONFIG.key);
            console.log('Cluster:', CONFIG.cluster);

            // Simulate receiving a test event
            const testData = {
                type: 'new-job',
                job_id: 'test-' + Date.now(),
                title: '🧪 Test Job - Pusher Connection Working!',
                description: 'This is a test to verify your Pusher connection is working correctly. If you see this, notifications are working!',
                budget: null,
                hourly_rate: '$50-100/hr',
                job_type: 'Hourly',
                url: 'https://www.upwork.com/test',
                client_country: 'US',
                payment_verified: true,
                proposals: '5-10',
                time_posted: 'Just now',
                skills: ['PHP', 'Laravel', 'JavaScript'],
                ai_score: 95,
                ai_recommendation: 'APPLY',
                ai_reason: 'This is a test notification to verify the Pusher dashboard is receiving events correctly.',
                timestamp: new Date().toISOString()
            };

            console.log('📤 Simulating event:', testData);
            addJobCard(CONFIG.event, testData);

            showDesktopNotification('✅ Test Successful', 'Pusher notifications are working! Check your dashboard for the test job card.');
        }
    </script>
</body>
</html>
