<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CookieSetupController extends Controller
{
    /**
     * Storage path for cookies
     */
    protected string $storagePath;

    /**
     * Groq API configuration
     */
    protected string $groqApiKey;
    protected string $groqApiUrl;

    public function __construct()
    {
        $this->storagePath = base_path('crawler/playwright/storage.json');
        $this->groqApiKey = config('services.groq.api_key', env('GROQ_API_KEY'));
        $this->groqApiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }

    /**
     * Show the cookie setup page
     */
    public function index()
    {
        return view('cookie-setup');
    }

    /**
     * Get current cookie status
     */
    public function status()
    {
        try {
            if (!File::exists($this->storagePath)) {
                return response()->json([
                    'has_cookies' => false,
                    'cookie_count' => 0,
                    'last_updated' => null
                ]);
            }

            $content = File::get($this->storagePath);
            $data = json_decode($content, true);

            if (!isset($data['cookies']) || !is_array($data['cookies'])) {
                return response()->json([
                    'has_cookies' => false,
                    'cookie_count' => 0,
                    'last_updated' => null
                ]);
            }

            return response()->json([
                'has_cookies' => true,
                'cookie_count' => count($data['cookies']),
                'last_updated' => $data['timestamp'] ?? now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'has_cookies' => false,
                'cookie_count' => 0,
                'last_updated' => null
            ]);
        }
    }

    /**
     * Process and save cookies
     */
    public function store(Request $request)
    {
        try {
            $cookiesInput = $request->input('cookies');

            // Validate input
            if (empty($cookiesInput)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No cookies provided. Please paste your cookies from EditThisCookie.'
                ], 400);
            }

            // Try to parse as JSON first
            $cookies = $this->parseCookies($cookiesInput);

            if (empty($cookies)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not parse cookies. Make sure you copy the entire JSON array from EditThisCookie.',
                    'error' => 'Invalid JSON format'
                ], 400);
            }

            // If we have fewer than 5 cookies, try using AI to help parse
            if (count($cookies) < 5 && $this->groqApiKey) {
                $cookies = $this->parseCookiesWithAI($cookiesInput);
            }

            // Normalize cookies for Playwright format
            $normalizedCookies = $this->normalizeCookies($cookies);

            if (empty($normalizedCookies)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to normalize cookies. Please check the format.',
                    'error' => 'Normalization failed'
                ], 400);
            }

            // Create storage.json format
            $storage = [
                'timestamp' => now()->toIso8601String(),
                'url' => 'https://www.upwork.com',
                'cookies' => $normalizedCookies,
                'localStorage' => []
            ];

            // Ensure directory exists
            $directory = dirname($this->storagePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Save to storage.json
            File::put($this->storagePath, json_encode($storage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'message' => 'Cookies have been set up successfully!',
                'cookie_count' => count($normalizedCookies)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing cookies.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse cookies from input string
     */
    protected function parseCookies(string $input): array
    {
        $trimmed = trim($input);

        // Try direct JSON parse first
        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to extract JSON from mixed content
        if (preg_match('/\[.*\]/s', $trimmed, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Use Groq AI to help parse malformed cookies
     */
    protected function parseCookiesWithAI(string $input): array
    {
        try {
            $response = Http::timeout(30)->post($this->groqApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a cookie parser. Extract cookie data from the input and return ONLY a valid JSON array of cookies. Each cookie should have: name, value, domain, path, httpOnly, secure, sameSite, and expires (if present). Respond with ONLY the JSON array, no other text.'
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Parse these cookies and return a JSON array:\n' . substr($input, 0, 10000)
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 8000
                ]
            ]);

            if ($response->successful()) {
                $aiContent = $response->json('choices.0.message.content', '');

                // Clean up any markdown code blocks
                $cleaned = str_replace(['```json', '```'], '', $aiContent);
                $cleaned = trim($cleaned);

                $decoded = json_decode($cleaned, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Exception $e) {
            // Silently fail and return empty array
        }

        return [];
    }

    /**
     * Normalize cookies to Playwright format
     */
    protected function normalizeCookies(array $cookies): array
    {
        $normalized = [];

        foreach ($cookies as $cookie) {
            // Handle both array and object formats
            if (is_array($cookie)) {
                $cookie = (object) $cookie;
            }

            // Get cookie name and value
            $name = $cookie->name ?? $cookie->key ?? null;
            $value = $cookie->value ?? $cookie->val ?? '';

            if (empty($name)) {
                continue;
            }

            // Get domain
            $domain = $cookie->domain ?? '.upwork.com';
            if ($cookie->hostOnly ?? false) {
                $domain = 'www.upwork.com';
            }

            // Get sameSite value
            $sameSite = 'Lax';
            if (!empty($cookie->sameSite)) {
                $sameSiteStr = strtolower(is_string($cookie->sameSite) ? $cookie->sameSite : 'lax');
                $sameSiteMap = [
                    'no_restriction' => 'None',
                    'none' => 'None',
                    'strict' => 'Strict',
                    'lax' => 'Lax',
                    'unspecified' => 'Lax'
                ];
                $sameSite = $sameSiteMap[$sameSiteStr] ?? 'Lax';
            }

            // Build normalized cookie
            $normalizedCookie = [
                'name' => $name,
                'value' => $value,
                'domain' => $domain,
                'path' => $cookie->path ?? '/',
                'httpOnly' => !empty($cookie->httpOnly),
                'secure' => !empty($cookie->secure) ?? true,
                'sameSite' => $sameSite
            ];

            // Add expiration if present
            if (!empty($cookie->expirationDate)) {
                $normalizedCookie['expires'] = $cookie->expirationDate;
            } elseif (!empty($cookie->expires)) {
                $normalizedCookie['expires'] = $cookie->expires;
            }

            $normalized[] = $normalizedCookie;
        }

        return $normalized;
    }
}
