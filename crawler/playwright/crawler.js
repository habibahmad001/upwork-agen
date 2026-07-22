/**
 * Upwork Job Crawler
 *
 * This script uses Playwright to scrape job listings from Upwork.
 * It requires an active user session (storage.json) from a previous login.
 *
 * Usage: node crawler.js [--session-id=UUID]
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

// Configuration
const CONFIG_PATH = resolve(__dirname, '../config.json');
const STORAGE_PATH = resolve(__dirname, 'storage.json');
const OUTPUT_PATH = resolve(__dirname, 'jobs.json');

// Use a custom profile directory (not real Chrome to avoid locks)
// But we'll copy more browser fingerprint data to avoid detection
const PROFILE_DIR = resolve(__dirname, '.chrome-profile');

// Chrome/Edge paths on Windows
const BROWSER_PATHS = [
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Users\\' + (process.env.USERNAME || '') + '\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
];

// Load configuration
let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('Failed to load config:', err.message);
  process.exit(1);
}

// Get session ID from environment or argument
const sessionId = process.env.SESSION_ID || process.argv[2] || generateSessionId();

/**
 * Generate a random session ID.
 */
function generateSessionId() {
  return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Load storage file (cookies and local storage).
 */
function loadStorage() {
  if (!existsSync(STORAGE_PATH)) {
    console.error('❌ Storage file not found:', STORAGE_PATH);
    console.error('Please run login first: node login.js');
    return null;
  }

  try {
    const storage = readFileSync(STORAGE_PATH, 'utf-8');
    return JSON.parse(storage);
  } catch (err) {
    console.error('❌ Failed to load storage:', err.message);
    return null;
  }
}

/**
 * Save jobs to JSON file.
 */
function saveJobs(jobs) {
  const output = {
    session_id: sessionId,
    timestamp: new Date().toISOString(),
    jobs_found: jobs.length,
    jobs: jobs,
  };

  writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));
  console.log(`💾 Saved ${jobs.length} jobs to ${OUTPUT_PATH}`);
}

/**
 * Extract job data from a job card element.
 */
function extractJobData(card, url) {
  try {
    // Title and link
    const titleEl = card.querySelector('[data-test="job-title"]');
    const title = titleEl?.textContent?.trim() || 'Unknown';
    const link = titleEl?.querySelector('a')?.href || url;

    // Job ID from URL
    const jobIdMatch = link.match(/\/job\/(~|view\/)?([a-zA-Z0-9_-]+)/);
    const jobId = jobIdMatch ? jobIdMatch[2] : null;

    // Description
    const descEl = card.querySelector('[data-test="job-description"]');
    const description = descEl?.textContent?.trim() || '';

    // Budget
    const budgetEl = card.querySelector('[data-test="budget"]');
    const budget = budgetEl?.textContent?.trim() || '';

    // Hourly range
    const hourlyEl = card.querySelector('[data-test="hourly-rate"]');
    const hourlyRate = hourlyEl?.textContent?.trim() || '';

    // Parse hourly min/max
    let hourlyMin = null;
    let hourlyMax = null;
    if (hourlyRate) {
      const match = hourlyRate.match(/\$?(\d+(?:\.\d+)?)\s*-\s*\$?(\d+(?:\.\d+)?)/);
      if (match) {
        hourlyMin = parseFloat(match[1]);
        hourlyMax = parseFloat(match[2]);
      }
    }

    // Client info
    const clientCountry = card.querySelector('[data-test="country"]')?.textContent?.trim() || null;
    const paymentVerified = !!card.querySelector('[data-test="payment-verified"]');
    const spentEl = card.querySelector('[data-test="total-spent"]');
    const spent = spentEl ? parseSpent(spentEl.textContent) : null;

    // Hire rate
    const hireRateEl = card.querySelector('[data-test="hire-rate"]');
    const hireRate = hireRateEl?.textContent?.trim() || null;

    // Client rating
    const ratingEl = card.querySelector('[data-test="client-rating"]');
    const clientRating = ratingEl ? parseFloat(ratingEl.textContent.match(/\d+\.?\d*/)?.[0]) : null;

    // Proposals
    const proposalsEl = card.querySelector('[data-test="proposals"]');
    const proposals = proposalsEl ? parseInt(proposalsEl.textContent.match(/\d+/)?.[0] || '0') : null;

    // Experience level
    const expEl = card.querySelector('[data-test="experience-level"]');
    const experienceLevel = expEl?.textContent?.trim() || null;

    // Project length
    const lengthEl = card.querySelector('[data-test="project-length"]');
    const projectLength = lengthEl?.textContent?.trim() || null;

    // Time posted
    const timeEl = card.querySelector('[data-test="posted-on"]');
    const timePosted = timeEl?.textContent?.trim() || null;

    // Skills
    const skills = [];
    const skillElements = card.querySelectorAll('[data-test="skill"]');
    skillElements.forEach((el) => {
      const skill = el.textContent?.trim();
      if (skill) skills.push(skill);
    });

    return {
      job_id: jobId,
      title,
      description,
      budget: budget || null,
      hourly_min: hourlyMin,
      hourly_max: hourlyMax,
      client_country: clientCountry,
      payment_verified: paymentVerified,
      spent,
      hire_rate: hireRate,
      client_rating: clientRating,
      proposals,
      experience_level: experienceLevel,
      project_length: projectLength,
      time_posted: timePosted,
      url: link,
      skills,
    };
  } catch (err) {
    console.error('❌ Error extracting job data:', err.message);
    return null;
  }
}

/**
 * Parse spent value (e.g., "$10k+" to number).
 */
function parseSpent(text) {
  if (!text) return null;

  const match = text.match(/\$?(\d+(?:\.\d+)?)\s*k?\+?/i);
  if (!match) return null;

  let value = parseFloat(match[1]);

  // Handle "k" suffix (thousands)
  if (text.toLowerCase().includes('k')) {
    value *= 1000;
  }

  return value;
}

/**
 * Main crawler function.
 */
async function crawl() {
  console.log('🚀 Starting Upwork crawler...');
  console.log('📋 Session ID:', sessionId);

  // Load storage (cookies)
  const storage = loadStorage();
  if (!storage) {
    console.error('❌ Cannot continue without valid storage. Please login first.');
    process.exit(1);
  }

  // Chrome/Edge paths on Windows
  const BROWSER_PATHS = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Users\\' + (process.env.USERNAME || '') + '\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  ];

  // Launch browser - try Chrome paths directly using persistent context
  let context = null;
  let page = null;
  let launched = false;

  for (const browserPath of BROWSER_PATHS) {
    if (launched) break;

    try {
      const fs = await import('fs');
      if (fs.existsSync(browserPath)) {
        console.log(`🔍 Using browser: ${browserPath}`);

        // Use launchPersistentContext for custom userDataDir
        context = await chromium.launchPersistentContext(PROFILE_DIR, {
          headless: config.headless ?? true,
          executablePath: browserPath,
          args: [
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
          ],
          userAgent: config.userAgent,
          viewport: config.viewport,
        });

        console.log(`✅ Browser launched`);
        launched = true;

        // Get the first page
        const pages = context.pages();
        page = pages[0] || await context.newPage();

        // Add anti-detection scripts
        await context.addInitScript(() => {
          Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
          Object.defineProperty(navigator, 'plugins', {
            get: () => [
              { name: 'Chrome PDF Plugin', filename: 'internal-pdf-viewer' },
              { name: 'Chrome PDF Viewer', filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai' },
              { name: 'Native Client', filename: 'internal-nacl-plugin' }
            ]
          });
          Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
          window.chrome = { runtime: {}, loadTimes: function() {}, csi: function() {}, app: {} };
        });

        // Load cookies from storage (add to existing ones)
        await context.addCookies(storage.cookies || []);
      }
    } catch (err) {
      console.log(`⚠️  Failed to launch ${browserPath}`);
    }
  }

  // Fallback to bundled browser if needed
  if (!launched) {
    console.log('⚠️  No system browser found, trying bundled browser...');
    try {
      context = await chromium.launchPersistentContext(PROFILE_DIR, {
        headless: config.headless ?? true,
        userAgent: config.userAgent,
        viewport: config.viewport,
      });
      console.log('✅ Using bundled browser');
      launched = true;

      // Add anti-detection
      await context.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        window.chrome = { runtime: {} };
      });

      const pages = context.pages();
      page = pages[0] || await context.newPage();

      await context.addCookies(storage.cookies || []);
    } catch (err) {
      console.error('❌ Could not launch any browser');
      process.exit(1);
    }
  }

  page = await context.newPage();

  // Navigate to jobs page
  console.log('🌐 Navigating to jobs page...');
  await page.goto(config.upwork.jobsUrl, {
    waitUntil: 'domcontentloaded',
    timeout: config.timeout,
  });

  // Wait for Cloudflare challenge to pass (if present)
  console.log('⏳ Waiting for page to load...');

  // Check if we're on Cloudflare challenge page
  try {
    const title = await page.title();
    console.log('📄 Page title:', title);

    if (title.includes('Just a moment') || title.includes('Checking')) {
      console.log('⏳ Cloudflare challenge detected, waiting...');

      // Wait for Cloudflare to redirect
      await page.waitForURL(url => !url.includes('challenge') && !url.includes('checking'), {
        timeout: 30000
      });

      console.log('✅ Cloudflare challenge passed!');

      // Wait a bit more for the actual page to load
      await page.waitForTimeout(3000);
    } else {
      // Wait for normal page load
      await page.waitForTimeout(3000);
    }
  } catch (err) {
    console.log('⚠️  Cloudflare wait timeout, proceeding anyway...');
  }

  // Wait for job cards to load
  console.log('⏳ Waiting for job cards...');

  // Debug: take screenshot and log URL
  console.log('📍 Current URL:', page.url());

  // Wait a bit for dynamic content
  await page.waitForTimeout(3000);

  // Try multiple possible selectors
  const possibleSelectors = [
    '[data-test="job-tile-list"]',
    '[data-test="JobTile"]',
    '.job-tile',
    '[data-test="job-item"]',
    'section[aria-label*="job" i]',
    '.up-job-card'
  ];

  let jobList = null;
  let foundSelector = null;

  for (const selector of possibleSelectors) {
    try {
      console.log(`🔍 Trying selector: ${selector}`);
      await page.waitForSelector(selector, { timeout: 5000 });
      jobList = selector;
      foundSelector = selector;
      console.log(`✅ Found jobs with selector: ${selector}`);
      break;
    } catch (e) {
      console.log(`❌ Selector not found: ${selector}`);
    }
  }

  if (!jobList) {
    // Take screenshot for debugging
    const screenshotPath = resolve(__dirname, 'debug-screenshot.png');
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.error('❌ Job cards not found with any selector. Screenshot saved to:', screenshotPath);
    console.error('📍 Current URL:', page.url());

    // Debug: log page title and content
    const title = await page.title();
    console.error('📄 Page title:', title);

    // Check if we're on login page
    if (page.url().includes('login') || page.url().includes('account-security')) {
      console.error('⚠️  You are not logged in! Please run: node login.js');
    }

    throw new Error('Job cards not found');
  }

  // Scroll to load all jobs (simple version - no infinite scroll for now)
  console.log('📜 Scrolling to load jobs...');
  await autoScroll(page);

  // Extract job data - try multiple selectors
  console.log('🔍 Extracting job data...');
  const parsedJobs = [];

  const jobSelectors = [
    // Updated for 2024 Upwork HTML structure
    { list: '[data-test="job-tile-list"]', item: 'section.air3-card-section.air3-card-hover' },
    { list: '[data-test="job-tile-list"]', item: 'section[data-test="job-tile"]' },
    // Fallback to generic section selector
    { list: 'body', item: 'section.air3-card-section.air3-card-hover' }
  ];

  let jobCards = [];

  for (const { list, item } of jobSelectors) {
    try {
      const listEl = await page.$(list);
      if (listEl) {
        jobCards = await page.$$(item);
        if (jobCards.length > 0) {
          console.log(`✅ Found ${jobCards.length} job cards with selector: ${list} > ${item}`);
          break;
        }
      }
    } catch (e) {
      // Try next selector
    }
  }

  console.log(`📊 Total job cards found: ${jobCards.length}`);

  for (const card of jobCards) {
    const jobData = await page.evaluate((el) => {
      // Extract job link and ID
      const linkEl = el.querySelector('a[href*="/jobs/"]');
      if (!linkEl) return null;

      const href = linkEl.getAttribute('href');
      const link = href.startsWith('http') ? href : 'https://www.upwork.com' + href;

      // Extract job_id from URL pattern: /jobs/Job-Name_~022079845648021037082/
      const jobIdMatch = link.match(/\/jobs\/[^_]*_~(\d+)/);
      if (!jobIdMatch) return null;
      const jobId = jobIdMatch[1];

      // Title
      const titleEl = el.querySelector('h3.job-tile-title a, h3.my-0 a');
      const title = titleEl?.textContent?.trim() || 'Unknown';

      // Description
      const descEl = el.querySelector('[data-test="job-description-text"]');
      const description = descEl?.textContent?.trim() || '';

      // Job type (Fixed-price or Hourly)
      const jobTypeEl = el.querySelector('[data-test="job-type"]');
      const jobType = jobTypeEl?.textContent?.trim() || '';

      // Budget for fixed-price
      const budgetEl = el.querySelector('[data-test="budget"]');
      const budget = budgetEl?.textContent?.trim() || null;

      // Hourly rate
      let hourlyRate = null;
      let hourlyMin = null;
      let hourlyMax = null;
      if (jobType.includes('Hourly')) {
        hourlyRate = jobType.replace('Hourly:', '').trim();
        // Parse hourly range
        const hourlyMatch = hourlyRate.match(/\$?(\d+(?:\.\d+)?)\s*-\s*\$?(\d+(?:\.\d+)?)/);
        if (hourlyMatch) {
          hourlyMin = parseFloat(hourlyMatch[1]);
          hourlyMax = parseFloat(hourlyMatch[2]);
        }
      }

      // Payment verification
      const paymentVerifiedEl = el.querySelector('[data-test="payment-verification-status"]');
      const paymentVerified = paymentVerifiedEl?.textContent?.trim()?.includes('verified') || false;

      // Client country
      const countryEl = el.querySelector('[data-test="client-country"]');
      const clientCountry = countryEl?.textContent?.trim()?.replace(/\s+/g, ' ').trim() || '';

      // Proposals
      const proposalsEl = el.querySelector('[data-test="proposals-tier"]');
      const proposals = proposalsEl?.textContent?.trim() || '';

      // Skills
      const skills = [];
      const skillElements = el.querySelectorAll('[data-test="attr-item"]');
      skillElements.forEach((s) => {
        const skill = s.textContent?.trim();
        if (skill) skills.push(skill);
      });

      // Time posted
      const timeEl = el.querySelector('[data-test="posted-on"]');
      const timePosted = timeEl?.textContent?.trim() || '';

      // Client spent
      const spentEl = el.querySelector('[data-test="formatted-amount"]');
      const spent = spentEl?.textContent?.trim() || '';

      // Contractor tier (Expert, Intermediate, etc.)
      const tierEl = el.querySelector('[data-test="contractor-tier"]');
      const contractorTier = tierEl?.textContent?.trim() || '';

      // Duration (for hourly jobs)
      const durationEl = el.querySelector('[data-test="duration"]');
      const duration = durationEl?.textContent?.trim() || '';

      return {
        job_id: jobId,
        title,
        description,
        budget: budget || null,
        hourly_rate: hourlyRate || null,
        hourly_min: hourlyMin || null,
        hourly_max: hourlyMax || null,
        client_country: clientCountry || null,
        payment_verified: paymentVerified,
        proposals,
        skills,
        time_posted: timePosted,
        url: link,
        spent,
        contractor_tier: contractorTier,
        duration,
      };
    }, card);

    if (jobData.job_id) {
      parsedJobs.push(jobData);
    }
  }

  console.log(`✅ Found ${parsedJobs.length} jobs`);

  // Save results
  saveJobs(parsedJobs);

  await context.close();

  console.log('✨ Crawler completed successfully');

  return {
    session_id: sessionId,
    jobs_found: parsedJobs.length,
    timestamp: new Date().toISOString(),
  };
}

/**
 * Auto-scroll to load all content.
 */
async function autoScroll(page) {
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let totalHeight = 0;
      const distance = 100;
      const timer = setInterval(() => {
        const scrollHeight = document.body.scrollHeight;
        window.scrollBy(0, distance);
        totalHeight += distance;

        // Stop after scrolling a bit (not infinite for now)
        if (totalHeight >= scrollHeight || totalHeight >= 5000) {
          clearInterval(timer);
          resolve();
        }
      }, 100);
    });
  });
}

// Run the crawler
crawl()
  .then((result) => {
    console.log('📊 Results:', JSON.stringify(result, null, 2));
    process.exit(0);
  })
  .catch((err) => {
    console.error('❌ Crawler failed:', err);
    process.exit(1);
  });
