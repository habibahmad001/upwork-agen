/**
 * Manual Crawl - Interactive Job Extraction
 *
 * This script opens a browser window where you manually navigate to the jobs page.
 * Once you see the jobs, press Enter and it will extract the data.
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import * as readline from 'readline';

const __dirname = dirname(fileURLToPath(import.meta.url));

const CONFIG_PATH = resolve(__dirname, '../config.json');
const STORAGE_PATH = resolve(__dirname, 'storage.json');
const OUTPUT_PATH = resolve(__dirname, 'jobs.json');

const PROFILE_DIR = resolve(__dirname, '.chrome-profile');

const BROWSER_PATHS = [
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Users\\' + (process.env.USERNAME || '') + '\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
];

let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('Failed to load config:', err.message);
  process.exit(1);
}

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

async function crawl() {
  console.log('🚀 Starting Manual Crawl...');
  console.log('');
  console.log('Instructions:');
  console.log('1. A browser window will open');
  console.log('2. Navigate to: https://www.upwork.com/nx/find-work/best-matches');
  console.log('3. Wait for the page to fully load and show job listings');
  console.log('4. Come back here and press Enter');
  console.log('');

  let context = null;
  let page = null;
  let launched = false;

  for (const browserPath of BROWSER_PATHS) {
    if (launched) break;

    try {
      const fs = await import('fs');
      if (fs.existsSync(browserPath)) {
        console.log(`🔍 Using browser: ${browserPath}`);

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
        });

        console.log('✅ Browser launched');
        launched = true;

        // Load cookies
        if (existsSync(STORAGE_PATH)) {
          const storage = JSON.parse(readFileSync(STORAGE_PATH, 'utf-8'));
          if (storage.cookies && storage.cookies.length > 0) {
            await context.addCookies(storage.cookies);
            console.log('✅ Cookies loaded');
          }
        }

        const pages = context.pages();
        page = pages[0] || await context.newPage();

        // Navigate to jobs page
        console.log('🌐 Navigating to jobs page...');
        try {
          await page.goto(config.upwork.jobsUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
          });
          console.log('✅ Page loaded');
        } catch (err) {
          console.log('⚠️  Auto-navigation failed:', err.message);
          console.log('💡 Please navigate manually in the browser');
        }

        break;
      }
    } catch (err) {
      console.log(`⚠️  Failed: ${err.message}`);
    }
  }

  if (!launched) {
    console.error('❌ Could not launch browser');
    process.exit(1);
  }

  // Wait a bit for page to stabilize
  await page.waitForTimeout(3000);

  // Debug: take screenshot
  const screenshotPath = resolve(__dirname, 'browser-state.png');
  await page.screenshot({ path: screenshotPath, fullPage: true });
  console.log('📸 Screenshot saved to:', screenshotPath);

  console.log('');
  console.log('⏸️  PAUSED:');
  console.log('1. Check the browser window');
  console.log('2. If blank, manually navigate to: https://www.upwork.com/nx/find-work/best-matches');
  console.log('3. Complete any login/verification if needed');
  console.log('4. Wait for job listings to appear');
  console.log('5. Press Enter here to extract jobs...');
  await waitForEnter();
  console.log('');

  // Extract job data
  console.log('🔍 Extracting job data...');

  const jobs = await page.evaluate(() => {
    // Try multiple selectors
    const selectors = [
      '[data-test="job-tile-list"] > *',
      '[data-test="JobTile"]',
      '.job-tile',
      '[data-test="job-item"]',
      'section.up-card',
      'article.up-card',
    ];

    let cards = [];

    for (const selector of selectors) {
      const found = Array.from(document.querySelectorAll(selector));
      if (found.length > 0) {
        console.log('Found jobs with selector:', selector);
        cards = found;
        break;
      }
    }

    return cards.map((card) => {
      // Title and link
      const titleEl = card.querySelector('[data-test="job-title"], h2, h3, .job-title');
      const title = titleEl?.textContent?.trim() || 'Unknown';
      const link = titleEl?.querySelector('a')?.href || card.querySelector('a')?.href || '';

      // Job ID
      const jobIdMatch = link.match(/\/job\/(~|view\/)?([a-zA-Z0-9_-]+)/);
      const jobId = jobIdMatch ? jobIdMatch[2] : null;

      // Description
      const descEl = card.querySelector('[data-test="job-description"], .job-description, .description');
      const description = descEl?.textContent?.trim() || '';

      // Budget
      const budgetEl = card.querySelector('[data-test="budget"], .budget, [data-test="budget-text"]');
      const budget = budgetEl?.textContent?.trim() || '';

      // Hourly rate
      const hourlyEl = card.querySelector('[data-test="hourly-rate"], .hourly-rate');
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
      const clientCountry = card.querySelector('[data-test="country"], .country')?.textContent?.trim() || null;
      const paymentVerified = !!card.querySelector('[data-test="payment-verified"], .payment-verified');
      const spentEl = card.querySelector('[data-test="total-spent"], .total-spent');
      const spent = spentEl ? spentEl.textContent.trim() : null;

      // Hire rate
      const hireRateEl = card.querySelector('[data-test="hire-rate"], .hire-rate');
      const hireRate = hireRateEl?.textContent?.trim() || null;

      // Rating
      const ratingEl = card.querySelector('[data-test="client-rating"], .rating, .stars');
      const ratingText = ratingEl?.textContent?.trim() || '';
      const clientRating = ratingText ? parseFloat(ratingText.match(/\d+\.?\d*/)?.[0]) : null;

      // Proposals
      const proposalsEl = card.querySelector('[data-test="proposals"], .proposals, .proposal-count');
      const proposalsText = proposalsEl?.textContent?.trim() || '';
      const proposals = proposalsText ? parseInt(proposalsText.match(/\d+/)?.[0] || '0') : null;

      // Skills
      const skills = [];
      const skillElements = card.querySelectorAll('[data-test="skill"], .skill, [data-automation-id="skill"]');
      skillElements.forEach((el) => {
        const skill = el.textContent?.trim();
        if (skill) skills.push(skill);
      });

      // Time posted
      const timeEl = card.querySelector('[data-test="posted-on"], .posted-time, .time-posted');
      const timePosted = timeEl?.textContent?.trim() || '';

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
        skills,
        time_posted: timePosted,
        url: link,
      };
    });
  });

  console.log(`✅ Found ${jobs.length} jobs`);

  // Filter out jobs without IDs
  const validJobs = jobs.filter(j => j.job_id && j.title !== 'Unknown');
  console.log(`📊 Valid jobs: ${validJobs.length}`);

  // Save results
  const output = {
    session_id: 'manual_' + Date.now(),
    timestamp: new Date().toISOString(),
    jobs_found: validJobs.length,
    jobs: validJobs,
  };

  writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));
  console.log(`💾 Saved to ${OUTPUT_PATH}`);

  await context.close();

  console.log('✨ Done!');
  process.exit(0);
}

crawl().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});
