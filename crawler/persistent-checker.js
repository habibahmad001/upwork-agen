/**
 * Persistent Job Checker with Browser Automation
 *
 * Keeps a browser session open and periodically checks for new jobs.
 * This bypasses Cloudflare by maintaining a live browser session.
 *
 * Usage: node persistent-checker.js
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

const CONFIG_PATH = resolve('./config.json');
const STORAGE_PATH = resolve('./playwright/storage.json');
const KNOWN_JOBS_PATH = resolve('./known-jobs.json');
const OUTPUT_PATH = resolve('./jobs.json');

const CHECK_INTERVAL = 60 * 1000; // Check every 60 seconds

// Email configuration
const EMAIL_CONFIG = {
  enabled: true,
  to: 'habibahmed001@gmail.com'
};

console.log('🚀 Persistent Job Checker');
console.log('========================');
console.log('This keeps a browser open and checks for jobs every minute.');
console.log('');

// Load config
let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('Failed to load config:', err.message);
  process.exit(1);
}

// Load cookies
let cookies = [];
try {
  const storage = JSON.parse(readFileSync(STORAGE_PATH, 'utf-8'));
  cookies = storage.cookies || [];
  console.log(`✅ Loaded ${cookies.length} cookies`);
} catch (err) {
  console.error('Failed to load cookies:', err.message);
}

// Load known jobs
let knownJobs = [];
try {
  if (existsSync(KNOWN_JOBS_PATH)) {
    knownJobs = JSON.parse(readFileSync(KNOWN_JOBS_PATH, 'utf-8'));
  }
} catch (err) {
  console.log('No known jobs file, starting fresh');
}

/**
 * Extract jobs from page
 */
async function extractJobs(page) {
  const jobs = await page.evaluate(() => {
    const results = [];

    // Try multiple selectors
    const selectors = [
      '[data-test="JobTile"]',
      'section[data-test="job-tile"]',
      'article.up-card',
      'a[href*="/job/"]'
    ];

    for (const selector of selectors) {
      const elements = document.querySelectorAll(selector);
      if (elements.length > 0) {
        console.log(`Found ${elements.length} elements with selector: ${selector}`);

        elements.forEach((el) => {
          const linkEl = el.querySelector('a[href*="/job/"]') || el;
          const link = linkEl.href || el.href || '';
          const titleMatch = link.match(/\/job\/(?:~|view\/)?([a-zA-Z0-9_-]+)/);

          if (titleMatch) {
            const titleEl = el.querySelector('[data-test="job-title"], h2, h3, .job-title');
            const descEl = el.querySelector('[data-test="job-description"], .description');
            const budgetEl = el.querySelector('[data-test="budget"], .budget');
            const hourlyEl = el.querySelector('[data-test="hourly-rate"], .hourly-rate');
            const skillsEls = el.querySelectorAll('[data-test="skill"], .skill');

            const skills = [];
            skillsEls.forEach(s => {
              const skill = s.textContent?.trim();
              if (skill) skills.push(skill);
            });

            results.push({
              job_id: titleMatch[1],
              url: link.startsWith('http') ? link : 'https://www.upwork.com' + link,
              title: titleEl?.textContent?.trim() || 'Unknown',
              description: descEl?.textContent?.trim() || '',
              budget: budgetEl?.textContent?.trim() || null,
              hourly_rate: hourlyEl?.textContent?.trim() || null,
              skills: skills,
              fetched_at: new Date().toISOString()
            });
          }
        });

        if (results.length > 0) break;
      }
    }

    return results;
  });

  return jobs;
}

/**
 * Find new jobs
 */
function findNewJobs(currentJobs) {
  const knownIds = new Set(knownJobs.map(j => j.job_id));
  return currentJobs.filter(job => !knownIds.has(job.job_id));
}

/**
 * Save known jobs
 */
function saveKnownJobs(jobs) {
  const allJobs = [...knownJobs, ...jobs];
  const recentJobs = allJobs.slice(-1000);
  writeFileSync(KNOWN_JOBS_PATH, JSON.stringify(recentJobs, null, 2));
}

/**
 * Send notification (console for now, can be extended to email)
 */
function sendNotification(newJobs) {
  console.log(`📧 ${newJobs.length} new jobs found!`);
  console.log('================================');

  for (const job of newJobs) {
    console.log(`🆕 ${job.title}`);
    console.log(`   ${job.url}`);
    if (job.budget) console.log(`   💰 Budget: ${job.budget}`);
    if (job.hourly_rate) console.log(`   💵 Rate: ${job.hourly_rate}`);
    console.log('');
  }

  // TODO: Implement email sending
  // console.log(`📧 Email would be sent to: ${EMAIL_CONFIG.to}`);
}

/**
 * Main function
 */
async function start() {
  console.log('🌐 Launching browser...');

  const browser = await chromium.launch({
    headless: false, // Keep visible for Cloudflare
    args: [
      '--disable-blink-features=AutomationControlled',
      '--no-sandbox',
      '--disable-dev-shm-usage'
    ]
  });

  const context = await browser.newContext({
    userAgent: config.userAgent,
    viewport: config.viewport
  });

  // Add anti-detection
  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    window.chrome = { runtime: {} };
  });

  const page = await context.newPage();

  // Load cookies
  if (cookies.length > 0) {
    // Fix sameSite values
    const fixedCookies = cookies.map(c => ({
      ...c,
      sameSite: c.sameSite === 'no_restriction' ? 'None' :
               c.sameSite === 'unspecified' ? 'Lax' :
               c.sameSite === 'strict' ? 'Strict' : 'Lax'
    }));
    await context.addCookies(fixedCookies);
    console.log('✅ Cookies loaded');
  }

  // Navigate to jobs page
  console.log('🌐 Navigating to jobs page...');
  await page.goto(config.upwork.jobsUrl, {
    waitUntil: 'domcontentloaded',
    timeout: 60000
  });

  console.log('');
  console.log('⏸️  PAUSED:');
  console.log('1. Complete any Cloudflare challenge in the browser');
  console.log('2. Wait for the jobs page to fully load');
  console.log('3. Make sure you can see job listings');
  console.log('4. Press Enter here to start automatic checking...');
  console.log('');

  // Wait for user to confirm page is loaded
  await new Promise(resolve => {
    process.stdin.once('data', resolve);
  });

  console.log('');
  console.log('✅ Starting automatic checks...');
  console.log(`⏰ Checking every ${CHECK_INTERVAL / 1000} seconds`);
  console.log('Press Ctrl+C to stop\n');

  // First check
  await checkJobs(page);

  // Schedule recurring checks
  const intervalId = setInterval(() => {
    checkJobs(page).catch(err => {
      console.error('❌ Check failed:', err.message);
    });
  }, CHECK_INTERVAL);

  // Handle shutdown
  process.on('SIGINT', async () => {
    console.log('\n🛑 Stopping...');
    clearInterval(intervalId);
    await browser.close();
    console.log('✅ Done');
    process.exit(0);
  });
}

/**
 * Check for new jobs
 */
async function checkJobs(page) {
  const timestamp = new Date().toLocaleString();

  try {
    console.log(`\n🔍 [${timestamp}] Checking for jobs...`);

    // Refresh the page
    await page.reload({
      waitUntil: 'domcontentloaded',
      timeout: 30000
    });

    // Wait a bit for dynamic content
    await page.waitForTimeout(3000);

    // Extract jobs
    const jobs = await extractJobs(page);
    console.log(`📊 Found ${jobs.length} jobs on page`);

    // Find new jobs
    const newJobs = findNewJobs(jobs);

    if (newJobs.length > 0) {
      console.log(`✨ ${newJobs.length} NEW jobs!`);

      sendNotification(newJobs);

      // Save new jobs
      saveKnownJobs(newJobs);

      // Save all jobs
      const output = {
        timestamp: new Date().toISOString(),
        total_found: jobs.length,
        new_jobs: newJobs.length,
        jobs: jobs
      };
      writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));
      console.log(`💾 Saved to ${OUTPUT_PATH}`);

    } else {
      console.log('✅ No new jobs');
    }

  } catch (err) {
    console.error(`❌ Error: ${err.message}`);
  }
}

// Start the checker
start().catch(err => {
  console.error('❌ Failed to start:', err);
  process.exit(1);
});
