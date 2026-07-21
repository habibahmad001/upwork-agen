/**
 * Manual Cookie Import Script
 *
 * This script allows you to manually import cookies from your browser.
 * This bypasses CAPTCHA and bot detection issues.
 *
 * Instructions:
 * 1. Log in to Upwork.com in your normal Chrome browser
 * 2. Press F12 to open DevTools
 * 3. Go to Application → Cookies → https://www.upwork.com
 * 4. Copy all cookies (or use the "Export Cookies" extension)
 * 5. Run this script and paste the cookies
 */

import { writeFileSync } from 'fs';
import { resolve } from 'path';
import * as readline from 'readline';

const STORAGE_PATH = resolve('./storage.json');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

console.log('🍪 Manual Cookie Import for Upwork');
console.log('');
console.log('Instructions:');
console.log('1. Open https://www.upwork.com in your normal Chrome browser');
console.log('2. Log in to your account');
console.log('3. Press F12 → Application → Cookies → https://www.upwork.com');
console.log('4. For each cookie, note the Name, Value, Domain, Path');
console.log('5. Below, enter cookies in JSON format or paste a simplified format');
console.log('');
console.log('Simple format (one per line):');
console.log('  name=value;domain=.upwork.com;path=/');
console.log('');
console.log('Or paste full JSON array of cookies.');
console.log('');
console.log('Type DONE when finished, or press Ctrl+C to exit.');
console.log('');

let cookies = [];
let cookieCount = 0;

function askForCookie() {
  rl.question(`Cookie ${cookieCount + 1} (or type DONE): `, (input) => {
    if (input.trim().toUpperCase() === 'DONE' || input.trim() === '') {
      finishImport();
      return;
    }

    try {
      // Try parsing as JSON first
      if (input.startsWith('[') || input.startsWith('{')) {
        const parsed = JSON.parse(input);
        if (Array.isArray(parsed)) {
          cookies = cookies.concat(parsed);
          cookieCount += parsed.length;
          console.log(`✅ Added ${parsed.length} cookies from JSON`);
        } else {
          cookies.push(parseCookie(parsed));
          cookieCount++;
          console.log('✅ Cookie added');
        }
      } else {
        // Parse simple format: name=value;domain=...;path=...
        const cookie = parseSimpleFormat(input);
        if (cookie) {
          cookies.push(cookie);
          cookieCount++;
          console.log(`✅ Added cookie: ${cookie.name}`);
        }
      }
    } catch (err) {
      console.log('⚠️  Invalid format, try again');
    }

    askForCookie();
  });
}

function parseSimpleFormat(input) {
  const parts = input.split(';').map(p => p.trim());
  const cookie = {};

  for (const part of parts) {
    const [key, value] = part.split('=');
    if (!value) {
      // First part is name=value
      const [name, val] = part.split('=');
      if (name && val !== undefined) {
        cookie.name = name;
        cookie.value = val;
      }
    } else {
      switch(key.toLowerCase()) {
        case 'domain': cookie.domain = value; break;
        case 'path': cookie.path = value; break;
      }
    }
  }

  if (cookie.name && cookie.value) {
    return {
      name: cookie.name,
      value: cookie.value,
      domain: cookie.domain || '.upwork.com',
      path: cookie.path || '/',
      expires: -1,
      httpOnly: true,
      secure: true,
      sameSite: 'Lax'
    };
  }
  return null;
}

function parseCookie(cookie) {
  return {
    name: cookie.name || cookie.key,
    value: cookie.value,
    domain: cookie.domain || '.upwork.com',
    path: cookie.path || '/',
    expires: cookie.expires || -1,
    httpOnly: cookie.httpOnly || cookie.HttpOnly !== undefined,
    secure: cookie.secure !== false,
    sameSite: cookie.sameSite || 'Lax'
  };
}

function finishImport() {
  if (cookies.length === 0) {
    console.log('⚠️  No cookies imported. Exiting.');
    rl.close();
    process.exit(1);
  }

  const storage = {
    timestamp: new Date().toISOString(),
    url: 'https://www.upwork.com',
    cookies: cookies,
    localStorage: {}
  };

  writeFileSync(STORAGE_PATH, JSON.stringify(storage, null, 2));
  console.log('');
  console.log(`✅ Saved ${cookies.length} cookies to ${STORAGE_PATH}`);
  console.log('💡 You can now run: node crawler.js');
  rl.close();
  process.exit(0);
}

console.log('📝 Enter cookies one by one (name=value;domain=.upwork.com;path=/)');
console.log('   or paste JSON array and type DONE');
console.log('');
askForCookie();
