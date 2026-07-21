/**
 * Quick Cookie Import from Clipboard
 *
 * Paste your EditThisCookie export when prompted.
 */

import { writeFileSync, readFileSync } from 'fs';
import { resolve } from 'path';
import * as readline from 'readline';

const STORAGE_PATH = resolve('./storage.json');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

console.log('🍪 Paste your EditThisCookie JSON export below');
console.log('Press Enter when done (Ctrl+D or Ctrl+Z on Windows)\n');

let cookiesText = '';

rl.on('line', (line) => {
  cookiesText += line;
});

rl.on('close', () => {
  try {
    const cookies = JSON.parse(cookiesText);

    // Convert EditThisCookie format to Playwright format
    const playwrightCookies = cookies.map(cookie => ({
      name: cookie.name,
      value: cookie.value,
      domain: cookie.domain,
      path: cookie.path || '/',
      expires: cookie.expirationDate || -1,
      httpOnly: cookie.httpOnly || false,
      secure: cookie.secure || false,
      sameSite: (cookie.sameSite || 'lax').toLowerCase().replace('no_restriction', 'lax').replace('unspecified', 'lax')
    }));

    const storage = {
      timestamp: new Date().toISOString(),
      url: 'https://www.upwork.com',
      cookies: playwrightCookies,
      localStorage: {}
    };

    writeFileSync(STORAGE_PATH, JSON.stringify(storage, null, 2));
    console.log(`\n✅ Saved ${playwrightCookies.length} cookies to ${STORAGE_PATH}`);
    console.log('💡 You can now run: node crawler.js');
    process.exit(0);
  } catch (err) {
    console.error('\n❌ Error parsing cookies:', err.message);
    process.exit(1);
  }
});

// Windows workaround for Ctrl+Z
if (process.platform === 'win32') {
  rl.on('SIGINT', () => {
    rl.close();
  });
}
