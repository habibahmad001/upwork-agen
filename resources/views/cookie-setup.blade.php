<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cookie Setup - Upwork Job Agent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success-checkmark {
            color: #10b981;
            font-size: 48px;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-gray-800 rounded-lg shadow-xl p-8">
            <h1 class="text-3xl font-bold text-blue-400 mb-2">🍪 Cookie Setup</h1>
            <p class="text-gray-400 mb-6">Paste your Upwork cookies from EditThisCookie and we'll handle the rest!</p>

            <!-- Instructions -->
            <div class="bg-gray-700 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-white mb-4">📋 Instructions</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-300">
                    <li>Install <a href="https://chrome.google.com/webstore/detail/editthiscookie/fngmhnnpilhplaeedifhccceomclgfbg" target="_blank" class="text-blue-400 hover:underline">EditThisCookie</a> extension</li>
                    <li>Go to <a href="https://www.upwork.com" target="_blank" class="text-blue-400 hover:underline">https://www.upwork.com</a> and log in</li>
                    <li>Visit the jobs page: <a href="https://www.upwork.com/nx/find-work/" target="_blank" class="text-blue-400 hover:underline">Find Work</a></li>
                    <li>Click EditThisCookie extension → Export → Copy to clipboard</li>
                    <li>Paste the cookies below</li>
                    <li>Click "Setup Cookies" button</li>
                </ol>
            </div>

            <!-- Cookie Input Form -->
            <form id="cookieForm" class="space-y-6">
                <div>
                    <label for="cookies" class="block text-sm font-medium text-gray-300 mb-2">
                        Paste Your Cookies Here
                    </label>
                    <textarea
                        id="cookies"
                        name="cookies"
                        rows="12"
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white font-mono text-sm"
                        placeholder='Paste cookies here... Should start with [ and end with ]'
                        required
                    ></textarea>
                    <p class="mt-2 text-sm text-gray-400">
                        Tip: Copy the entire JSON array from EditThisCookie export
                    </p>
                </div>

                <div class="flex gap-4">
                    <button
                        type="submit"
                        id="submitBtn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                    >
                        <span id="btnText">🚀 Setup Cookies</span>
                        <div id="btnSpinner" class="spinner hidden"></div>
                    </button>

                    <button
                        type="button"
                        onclick="clearForm()"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200"
                    >
                        Clear
                    </button>
                </div>
            </form>

            <!-- Result Section -->
            <div id="resultSection" class="hidden mt-8">
                <div id="resultContent"></div>
            </div>

            <!-- Cookie Status -->
            <div class="mt-8 bg-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📊 Current Cookie Status</h3>
                <div id="cookieStatus" class="flex items-center gap-2">
                    <div class="spinner"></div>
                    <span class="text-gray-400">Checking...</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-gray-500 text-sm">
            <p>Upwork Job Agent • Powered by Laravel + Groq AI</p>
        </div>
    </div>

    <script>
        // Clear form
        function clearForm() {
            document.getElementById('cookies').value = '';
            document.getElementById('resultSection').classList.add('hidden');
        }

        // Show loading state
        function setLoading(isLoading) {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            if (isLoading) {
                btn.disabled = true;
                btnText.textContent = 'Processing...';
                btnSpinner.classList.remove('hidden');
            } else {
                btn.disabled = false;
                btnText.textContent = '🚀 Setup Cookies';
                btnSpinner.classList.add('hidden');
            }
        }

        // Show result
        function showResult(success, message, details = '') {
            const resultSection = document.getElementById('resultSection');
            const resultContent = document.getElementById('resultContent');

            resultSection.classList.remove('hidden');

            if (success) {
                resultContent.innerHTML = `
                    <div class="bg-green-900 border border-green-700 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="success-checkmark">✓</span>
                            <h3 class="text-xl font-semibold text-green-400">Success!</h3>
                        </div>
                        <p class="text-green-300 mb-2">${message}</p>
                        ${details ? `<div class="mt-4 text-sm text-green-400">${details}</div>` : ''}
                        <div class="mt-6 p-4 bg-green-950 rounded-lg">
                            <p class="text-green-400 text-sm">Your crawler is now ready! Run it with:</p>
                            <code class="block mt-2 text-green-300 bg-green-900 px-3 py-2 rounded">cd crawler && npm run ai-checker</code>
                        </div>
                    </div>
                `;
            } else {
                resultContent.innerHTML = `
                    <div class="bg-red-900 border border-red-700 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-red-400 text-4xl">✗</span>
                            <h3 class="text-xl font-semibold text-red-400">Error</h3>
                        </div>
                        <p class="text-red-300">${message}</p>
                        ${details ? `<div class="mt-4 text-sm text-red-400">${details}</div>` : ''}
                    </div>
                `;
            }
        }

        // Check cookie status on page load
        async function checkCookieStatus() {
            try {
                const response = await fetch('/cookie-setup/status');
                const data = await response.json();

                const statusDiv = document.getElementById('cookieStatus');
                if (data.has_cookies && data.cookie_count > 0) {
                    const hoursAgo = Math.round((Date.now() - new Date(data.last_updated).getTime()) / (1000 * 60 * 60));
                    statusDiv.innerHTML = `
                        <span class="text-green-400 text-2xl">✓</span>
                        <div>
                            <p class="text-green-400 font-semibold">${data.cookie_count} cookies loaded</p>
                            <p class="text-gray-400 text-sm">Updated ${hoursAgo} hours ago</p>
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <span class="text-yellow-400 text-2xl">!</span>
                        <p class="text-yellow-400">No cookies set up. Please add your cookies above.</p>
                    `;
                }
            } catch (error) {
                document.getElementById('cookieStatus').innerHTML = `
                    <span class="text-red-400 text-2xl">✗</span>
                    <p class="text-red-400">Unable to check cookie status</p>
                `;
            }
        }

        // Handle form submission
        document.getElementById('cookieForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const cookies = document.getElementById('cookies').value.trim();

            if (!cookies) {
                showResult(false, 'Please paste your cookies first');
                return;
            }

            setLoading(true);

            try {
                const response = await fetch('/cookie-setup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ cookies: cookies })
                });

                const data = await response.json();

                if (data.success) {
                    showResult(
                        true,
                        data.message || 'Cookies have been set up successfully!',
                        `Processed ${data.cookie_count} cookies`
                    );
                    // Refresh status
                    await checkCookieStatus();
                    // Clear form
                    document.getElementById('cookies').value = '';
                } else {
                    showResult(
                        false,
                        data.message || 'Failed to process cookies',
                        data.error || ''
                    );
                }
            } catch (error) {
                showResult(
                    false,
                    'Network error. Please try again.',
                    error.message
                );
            } finally {
                setLoading(false);
            }
        });

        // Check status on page load
        checkCookieStatus();
    </script>
</body>
</html>
