/**
 * Cookie Setup Script
 *
 * This script helps you easily set up cookies from EditThisCookie.
 *
 * Usage:
 * 1. Export cookies from EditThisCookie (copy to clipboard)
 * 2. Run this script: node setup-cookies.js
 * 3. Paste the cookies when prompted
 * 4. Press Enter twice to finish
 *
 * The cookies will be automatically formatted and saved to storage.json
 */

import { writeFileSync, readFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const STORAGE_PATH = resolve(__dirname, 'storage.json');

console.log('🍪 Cookie Setup Script');
console.log('====================\n');
console.log('Instructions:');
console.log('1. In Chrome, use EditThisCookie extension');
console.log('2. Click "Export" -> "Copy to clipboard"');
console.log('3. Paste the cookies below');
console.log('4. Press Enter twice when done\n');
console.log('Waiting for cookies...\n');

// Read cookies from stdin
let cookieData = '';

process.stdin.on('data', (chunk) => {
  cookieData += chunk.toString();
});

process.stdin.on('end', () => {
  try {
    // Try to parse the cookies
    let cookies;

    // Handle both JSON array and plain text formats
    const trimmed = cookieData.trim();
    if (trimmed.startsWith('[')) {
      // JSON array format
      cookies = JSON.parse(trimmed);
    } else {
      // Try to find JSON in the input
      const match = trimmed.match(/\[[\s\S]*\]/);
      if (match) {
        cookies = JSON.parse(match[0]);
      } else {
        throw new Error('Could not find valid JSON in input');
      }
    }

    if (!Array.isArray(cookies)) {
      throw new Error('Invalid cookie format: expected array');
    }

    console.log(`\n✅ Parsed ${cookies.length} cookies`);

    // Convert cookies to storage.json format
    const storage = {
      timestamp: new Date().toISOString(),
      url: 'https://www.upwork.com',
      cookies: cookies.map(cookie => {
        // Handle different cookie export formats
        const normalized = {
          name: cookie.name || cookie.key,
          value: cookie.value || cookie.val,
          domain: cookie.domain || cookie.hostOnly ? 'www.upwork.com' : '.upwork.com',
          path: cookie.path || '/',
          httpOnly: cookie.httpOnly || false,
          secure: cookie.secure !== undefined ? cookie.secure : true,
          sameSite: 'Lax'
        };

        // Set sameSite based on input
        if (cookie.sameSite) {
          const sameSite = cookie.sameSite.toString().toLowerCase();
          if (sameSite === 'no_restriction' || sameSite === 'none') {
            normalized.sameSite = 'None';
          } else if (sameSite === 'strict') {
            normalized.sameSite = 'Strict';
          } else if (sameSite === 'lax') {
            normalized.sameSite = 'Lax';
          } else {
            normalized.sameSite = 'Lax';
          }
        }

        // Add expiration if present
        if (cookie.expirationDate || cookie.expires) {
          normalized.expires = cookie.expirationDate || cookie.expires;
        }

        return normalized;
      })
    };

    // Save to storage.json
    writeFileSync(STORAGE_PATH, JSON.stringify(storage, null, 2));

    console.log(`✅ Saved ${storage.cookies.length} cookies to storage.json`);
    console.log('\n🎉 Setup complete! You can now run the crawler.');
    console.log('   Run: node ai-job-checker.js\n');

    process.exit(0);

  } catch (err) {
    console.error('\n❌ Error:', err.message);
    console.log('\nMake sure you copy the cookies from EditThisCookie extension.');
    console.log('Format should be JSON array starting with [\n');
    process.exit(1);
  }
});

// Handle timeout (no input for 5 minutes)
setTimeout(() => {
  console.log('\n⏱️  Timeout: No input received for 5 minutes');
  console.log('Please run the script again and paste your cookies.\n');
  process.exit(1);
}, 5 * 60 * 1000);
