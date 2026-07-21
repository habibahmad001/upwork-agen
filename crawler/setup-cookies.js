/**
 * Simple Cookie Import Script
 *
 * Instructions:
 * 1. Open https://www.upwork.com in your browser (logged in)
 * 2. Click the EditThisCookie extension
 * 3. Click "Export" → "JSON" (or copy all cookies)
 * 4. Run: node setup-cookies.js
 * 5. Paste the JSON and press Enter
 * 6. Type DONE and press Enter
 */

import { writeFileSync, readFileSync } from 'fs';
import { resolve } from 'path';
import * as readline from 'readline';

const STORAGE_PATH = resolve('./playwright/storage.json');

console.log('🍪 Cookie Import Tool');
console.log('====================');
console.log('');
console.log('1. Open https://www.upwork.com in your browser (logged in)');
console.log('2. Click EditThisCookie extension');
console.log('3. Click "Export" → "JSON"');
console.log('4. Paste below (or paste cookie array directly)');
console.log('5. Type DONE when finished');
console.log('');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

let cookiesText = '';
let cookies = [];

function processLine(line) {
  if (line.trim().toUpperCase() === 'DONE') {
    saveCookies();
    return;
  }

  // Try to parse as JSON
  try {
    const parsed = JSON.parse(line);
    if (Array.isArray(parsed)) {
      cookies = cookies.concat(parsed);
      console.log(`✅ Added ${parsed.length} cookies (total: ${cookies.length})`);
    } else if (parsed.name && parsed.value) {
      cookies.push(parsed);
      console.log(`✅ Added cookie: ${parsed.name}`);
    }
  } catch (err) {
    // Try simple format: name=value
    const parts = line.split('=');
    if (parts.length === 2) {
      cookies.push({
        name: parts[0].trim(),
        value: parts[1].trim(),
        domain: '.upwork.com',
        path: '/',
        secure: true,
        sameSite: 'lax'
      });
      console.log(`✅ Added simple cookie: ${parts[0].trim()}`);
    }
  }

  rl.prompt();
}

function saveCookies() {
  if (cookies.length === 0) {
    console.log('❌ No cookies to save');
    rl.close();
    process.exit(1);
  }

  // Convert to Playwright format
  const playwrightCookies = cookies.map(cookie => ({
    name: cookie.name || cookie.key,
    value: cookie.value || cookie.val || '',
    domain: cookie.domain || '.upwork.com',
    path: cookie.path || '/',
    expires: cookie.expirationDate || cookie.expires || -1,
    httpOnly: cookie.httpOnly !== false,
    secure: cookie.secure !== false,
    sameSite: (cookie.sameSite || 'lax')
      .toString()
      .toLowerCase()
      .replace('no_restriction', 'None')
      .replace('unspecified', 'Lax')
      .replace('strict', 'Strict')
      .replace('none', 'None')
      .replace('lax', 'Lax')
  }));

  const storage = {
    timestamp: new Date().toISOString(),
    url: 'https://www.upwork.com',
    cookies: playwrightCookies,
    localStorage: {}
  };

  writeFileSync(STORAGE_PATH, JSON.stringify(storage, null, 2));
  console.log('');
  console.log(`✅ Saved ${playwrightCookies.length} cookies to ${STORAGE_PATH}`);
  console.log('💡 You can now run: node persistent-checker.js');
  rl.close();
  process.exit(0);
}

rl.prompt();
rl.on('line', processLine);

// Handle Ctrl+C
rl.on('SIGINT', () => {
  if (cookies.length > 0) {
    console.log('\n💾 Saving cookies before exit...');
    saveCookies();
  } else {
    console.log('\n👋 No cookies to save');
    rl.close();
    process.exit(0);
  }
});
