/**
 * Upwork Login Script
 *
 * This script handles the initial login to Upwork and saves the session.
 * The session is saved to storage.json for use by the crawler.
 *
 * Usage: node login.js [--headless]
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import * as readline from 'readline';

const __dirname = dirname(fileURLToPath(import.meta.url));

const CONFIG_PATH = resolve(__dirname, '../config.json');
const STORAGE_PATH = resolve(__dirname, 'storage.json');

// Use project directory for browser profile to avoid C: drive space issues
const PROFILE_DIR = resolve(__dirname, '.chrome-profile');

// Chrome/Edge paths on Windows
const BROWSER_PATHS = [
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Users\\' + (process.env.USERNAME || '') + '\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
];

/**
 * Wait for user to press Enter.
 */
function waitForEnter() {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });

  return new Promise(resolve => {
    rl.question('', () => {
      rl.close();
      resolve();
    });
  });
}

// Load configuration
let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('Failed to load config:', err.message);
  process.exit(1);
}

// Check if headless mode is requested
const headless = process.argv.includes('--headless');

/**
 * Main login function.
 */
async function login() {
  console.log('🔐 Starting Upwork login...');

  if (headless) {
    console.log('⚠️  Headless mode: You must provide credentials via environment variables');
  }

  // Launch browser - try Chrome paths directly using persistent context
  let context = null;
  let page = null;
  let launched = false;

  for (const browserPath of BROWSER_PATHS) {
    if (launched) break;

    try {
      const fs = await import('fs');
      if (fs.existsSync(browserPath)) {
        console.log(`🔍 Found browser at: ${browserPath}`);

        // Use launchPersistentContext for custom userDataDir
        context = await chromium.launchPersistentContext(PROFILE_DIR, {
          headless: false,
          executablePath: browserPath,
          args: [
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
          ],
          userAgent: config.userAgent,
          viewport: config.viewport,
          locale: 'en-US',
          timezoneId: 'America/New_York',
        });

        const browserName = browserPath.includes('Chrome') ? 'Chrome' : 'Edge';
        console.log(`✅ Using system ${browserName} browser (visible mode)`);
        console.log(`📁 Profile directory: ${PROFILE_DIR}`);
        launched = true;

        // Get the first page (should be about:blank or new tab page)
        const pages = context.pages();
        page = pages[0] || await context.newPage();

        // Add script to bypass bot detection
        await context.addInitScript(() => {
          // Hide webdriver
          Object.defineProperty(navigator, 'webdriver', { get: () => undefined });

          // Fake plugins
          Object.defineProperty(navigator, 'plugins', {
            get: () => [
              { name: 'Chrome PDF Plugin', filename: 'internal-pdf-viewer' },
              { name: 'Chrome PDF Viewer', filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai' },
              { name: 'Native Client', filename: 'internal-nacl-plugin' }
            ]
          });

          // Fake languages
          Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });

          // Chrome runtime object
          window.chrome = {
            runtime: {},
            loadTimes: function() {},
            csi: function() {},
            app: {}
          };

          // Permissions API
          const originalQuery = window.navigator.permissions.query;
          window.navigator.permissions.query = (parameters) => (
            parameters.name === 'notifications' ?
              Promise.resolve({ state: 'granted' }) :
              originalQuery(parameters)
          );

          // WebGL fingerprint
          const getParameter = WebGLRenderingContext.prototype.getParameter;
          WebGLRenderingContext.prototype.getParameter = function(parameter) {
            if (parameter === 37445) {
              return 'Intel Inc.';
            }
            if (parameter === 37446) {
              return 'Intel Iris OpenGL Engine';
            }
            return getParameter.call(this, parameter);
          };
        });
      }
    } catch (err) {
      console.log(`⚠️  Failed to launch ${browserPath}: ${err.message}`);
    }
  }

  // Fallback to bundled browser if needed
  if (!launched) {
    console.log('⚠️  No system browser found, trying bundled browser...');
    try {
      context = await chromium.launchPersistentContext(PROFILE_DIR, {
        headless: false,
        userAgent: config.userAgent,
        viewport: config.viewport,
      });
      console.log('✅ Using bundled browser');
      launched = true;

      const pages = context.pages();
      page = pages[0] || await context.newPage();
    } catch (err) {
      console.error('❌ Could not launch any browser. Please install Chrome or run: npx playwright install chromium');
      process.exit(1);
    }
  }

  // Navigate to login page with delay to appear more human
  console.log('🌐 Navigating to login page...');
  await page.goto(config.upwork.loginUrl, {
    waitUntil: 'load',
    timeout: 60000,
  });

  // Wait a bit before proceeding
  await page.waitForTimeout(2000);

  if (!headless) {
    console.log('⏳ Please complete the login process in the browser window...');
    console.log('💡 The session will be saved automatically after successful login');
    console.log('⏱️  Waiting for you to complete login (up to 5 minutes)...');

    // Wait for navigation to various success URLs (Upwork may redirect differently)
    try {
      await Promise.race([
        page.waitForURL('**/nx/find-work/**', { timeout: 300000 }),
        page.waitForURL('**/ab/account-security/login/**', { timeout: 300000 }),
        page.waitForURL('**/ab/proposals/**', { timeout: 300000 }),
        page.waitForURL('**/home/**', { timeout: 300000 }),
      ]);

      // Check if we're on a logged-in page
      const currentUrl = page.url();
      console.log('📍 Current URL:', currentUrl);

      if (currentUrl.includes('/find-work/') || currentUrl.includes('/home/') || currentUrl.includes('/ab/proposals/')) {
        console.log('✅ Login successful!');
      } else {
        console.log('⚠️  Please wait for full redirect to jobs/home page...');
        // Wait a bit more for redirect
        await page.waitForTimeout(5000);
      }
    } catch (err) {
      console.log('⚠️  Automatic URL detection failed, but continuing...');
      console.log('💡 If you see the Upwork dashboard in the browser, your session is valid');
    }

    // Final check - ask user to confirm if on dashboard
    console.log('\n⏸️  PAUSED: Please verify:');
    console.log('1. You are logged into Upwork');
    console.log('2. You can see the dashboard or jobs page');
    console.log('3. No CAPTCHA or verification required');
    console.log('\nIf everything looks good, press Enter to save your session...');
    await waitForEnter();
    console.log('✅ Confirmed! Saving session...');

  } else {
    // Headless login with credentials
    const email = process.env.UPWORK_EMAIL;
    const password = process.env.UPWORK_PASSWORD;

    if (!email || !password) {
      console.error('❌ UPWORK_EMAIL and UPWORK_PASSWORD environment variables are required for headless login');
      process.exit(1);
    }

    // Fill login form
    await page.fill('input[name="login[username]"]', email);
    await page.click('button[type="submit"]');

    await page.waitForSelector('input[name="login[password]"]', { timeout: 10000 });
    await page.fill('input[name="login[password]"]', password);
    await page.click('button[type="submit"]');

    // Wait for navigation
    await page.waitForURL('**/nx/find-work/**', { timeout: 30000 });
    console.log('✅ Login successful!');
  }

  // Save cookies and storage state
  console.log('📝 Saving browser session...');
  const cookies = await context.cookies();
  const localStorage = await page.evaluate(() => {
    const storage = {};
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      storage[key] = localStorage.getItem(key);
    }
    return storage;
  });

  const storage = {
    timestamp: new Date().toISOString(),
    url: page.url(),
    cookies,
    localStorage,
  };

  writeFileSync(STORAGE_PATH, JSON.stringify(storage, null, 2));
  console.log(`💾 Session saved to ${STORAGE_PATH}`);
  console.log(`📊 Saved ${cookies.length} cookies and ${Object.keys(localStorage).length} localStorage items`);

  await context.close();

  console.log('✨ Login completed successfully!');
  console.log('💡 You can now run: node crawler.js');
  return 0;
}

// Run the login
login()
  .then(() => {
    process.exit(0);
  })
  .catch((err) => {
    console.error('❌ Login failed:', err);
    process.exit(1);
  });
