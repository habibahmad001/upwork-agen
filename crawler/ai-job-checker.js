/**
 * Complete AI-Powered Job Checker
 *
 * Features:
 * - Crawls Upwork every 60 seconds
 * - Evaluates jobs with Groq AI
 * - Sends email notifications
 * - Syncs with Laravel backend
 *
 * Usage: node ai-job-checker.js
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';
import https from 'https';
import nodemailer from 'nodemailer';

// ==================== CONFIGURATION ====================

const CONFIG_PATH = resolve('./config.json');
const STORAGE_PATH = resolve('./playwright/storage.json');
const KNOWN_JOBS_PATH = resolve('./known-jobs.json');
const OUTPUT_PATH = resolve('./jobs.json');

// Groq AI Configuration
const GROQ_CONFIG = {
  apiKey: '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',
  baseUrl: 'https://api.groq.com/openai/v1',
  model: 'llama-3.3-70b-versatile'
};

// Email Configuration
const EMAIL_CONFIG = {
  enabled: true,
  to: 'habibahmed001@gmail.com',
  from: 'dontreplyback99@gmail.com',
  smtp: {
    host: 'smtp.gmail.com',
    port: 587,
    secure: false,
    user: 'dontreplyback99@gmail.com',
    pass: 'zerixamuabyxancj'
  }
};

// Laravel Backend Configuration
const LARAVEL_CONFIG = {
  enabled: true,
  baseUrl: 'http://127.0.0.1:8000',
  apiKey: 'your-api-key-here' // If your Laravel uses API tokens
};

const CHECK_INTERVAL = 60 * 1000; // 60 seconds

// ==================== INITIALIZATION ====================

console.log('🚀 AI-Powered Upwork Job Checker');
console.log('===================================\n');

// Load config
let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('❌ Failed to load config:', err.message);
  process.exit(1);
}

// Load cookies
let cookies = [];
try {
  const storage = JSON.parse(readFileSync(STORAGE_PATH, 'utf-8'));
  cookies = storage.cookies || [];
  console.log(`✅ Loaded ${cookies.length} cookies`);
} catch (err) {
  console.error('❌ Failed to load cookies. Run: node setup-cookies.js');
  process.exit(1);
}

// Load known jobs
let knownJobs = [];
try {
  if (existsSync(KNOWN_JOBS_PATH)) {
    knownJobs = JSON.parse(readFileSync(KNOWN_JOBS_PATH, 'utf-8'));
    console.log(`✅ Loaded ${knownJobs.length} known jobs`);
  }
} catch (err) {
  console.log('ℹ️  No known jobs file, starting fresh');
}

// ==================== GROQ AI EVALUATION ====================

/**
 * Evaluate job with Groq AI
 */
async function evaluateJobWithAI(job) {
  const prompt = `You are an expert freelance job evaluator. Analyze this Upwork job and provide a score (0-100) and recommendation.

Job Details:
- Title: ${job.title}
- Description: ${job.description?.substring(0, 500)}
- Budget: ${job.budget || job.hourly_rate || 'Not specified'}
- Skills: ${job.skills?.join(', ') || 'Not specified'}
- Client Country: ${job.client_country || 'Not specified'}

Evaluate based on:
1. Budget adequacy for the work
2. Client payment verification status
3. Skill match (if you're a developer)
4. Project clarity
5. Competition level (proposals count)

Respond in JSON format:
{
  "score": <0-100>,
  "recommendation": "apply|skip|consider",
  "reason": "<brief explanation>",
  "estimated_rate": <if hourly, estimated hourly rate in USD>,
  "confidence": <0-1>
}

Only respond with valid JSON, no other text.`;

  try {
    const response = await makeGroqRequest(prompt);
    return JSON.parse(response);
  } catch (err) {
    console.log(`⚠️  AI evaluation failed for job ${job.job_id}: ${err.message}`);
    return {
      score: 50,
      recommendation: 'consider',
      reason: 'AI evaluation failed',
      confidence: 0
    };
  }
}

/**
 * Make request to Groq API
 */
function makeGroqRequest(prompt) {
  return new Promise((resolve, reject) => {
    const data = JSON.stringify({
      model: GROQ_CONFIG.model,
      messages: [
        { role: 'system', content: 'You are a job evaluation expert. Always respond with valid JSON only.' },
        { role: 'user', content: prompt }
      ],
      temperature: 0.3,
      max_tokens: 500
    });

    const options = {
      hostname: 'api.groq.com',
      path: '/openai/v1/chat/completions',
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${GROQ_CONFIG.apiKey}`,
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(data)
      }
    };

    const req = https.request(options, (res) => {
      let responseData = '';

      res.on('data', (chunk) => {
        responseData += chunk;
      });

      res.on('end', () => {
        if (res.statusCode === 200) {
          try {
            const parsed = JSON.parse(responseData);
            const content = parsed.choices[0]?.message?.content || '{}';
            // Clean up the response (remove markdown code blocks if present)
            const cleaned = content.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
            resolve(cleaned);
          } catch (err) {
            reject(new Error('Failed to parse AI response'));
          }
        } else {
          reject(new Error(`Groq API error: ${res.statusCode}`));
        }
      });
    });

    req.on('error', reject);
    req.setTimeout(30000, () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });

    req.write(data);
    req.end();
  });
}

// ==================== EMAIL NOTIFICATIONS ====================

/**
 * Send email notification
 */
async function sendEmailNotification(jobsWithAI) {
  if (!EMAIL_CONFIG.enabled) return;

  console.log(`📧 Sending email notification for ${jobsWithAI.length} jobs...`);

  // Check if email password is configured
  if (EMAIL_CONFIG.smtp.pass === 'YOUR_APP_PASSWORD_HERE') {
    console.log('⚠️  Email not configured. Set up App Password:');
    console.log('   1. Go to https://myaccount.google.com/apppasswords');
    console.log('   2. Create an App Password');
    console.log('   3. Update EMAIL_CONFIG.smtp.pass in ai-job-checker.js');
    return;
  }

  try {
    const transporter = nodemailer.createTransport({
      host: EMAIL_CONFIG.smtp.host,
      port: EMAIL_CONFIG.smtp.port,
      secure: EMAIL_CONFIG.smtp.secure,
      auth: {
        user: EMAIL_CONFIG.smtp.user,
        pass: EMAIL_CONFIG.smtp.pass
      }
    });

    const highScoreJobs = jobsWithAI.filter(j => j.ai_score >= 70);
    const mediumScoreJobs = jobsWithAI.filter(j => j.ai_score >= 50 && j.ai_score < 70);

    let emailBody = `🎯 Upwork Job Alert - New Jobs Only!\n`;
    emailBody += `====================================\n\n`;
    emailBody += `📊 Summary:\n`;
    emailBody += `  • Total new jobs: ${jobsWithAI.length}\n`;
    emailBody += `  • High score (70+): ${highScoreJobs.length}\n`;
    emailBody += `  • Medium score (50-69): ${mediumScoreJobs.length}\n`;
    emailBody += `  • Low score (<50): ${jobsWithAI.length - highScoreJobs.length - mediumScoreJobs.length}\n\n`;

    // Sort by score descending
    const sortedJobs = jobsWithAI.sort((a, b) => (b.ai_score || 0) - (a.ai_score || 0));

    for (const job of sortedJobs) {
      const score = job.ai_score || 0;
      const rec = job.ai_recommendation || 'N/A';
      const stars = Math.ceil(score / 20);

      emailBody += `\n${'⭐'.repeat(stars)} ${score}/100 - ${rec.toUpperCase()}\n`;
      emailBody += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
      emailBody += `📌 ${job.title}\n`;

      // Time posted (how old the job is)
      if (job.time_posted) {
        emailBody += `⏰ Posted: ${job.time_posted} ago\n`;
      }

      // Budget/Rate
      if (job.budget) {
        emailBody += `💰 Budget: ${job.budget}\n`;
      } else if (job.hourly_rate) {
        emailBody += `💰 Rate: ${job.hourly_rate}\n`;
      }

      // Job type
      if (job.job_type) {
        emailBody += `📝 Type: ${job.job_type}\n`;
      }

      // Payment verification
      if (job.payment_verified) {
        emailBody += `✅ Payment Verified\n`;
      }

      // Client country
      if (job.client_country) {
        emailBody += `🌍 Client: ${job.client_country}\n`;
      }

      // Proposals
      if (job.proposals) {
        emailBody += `📊 Proposals: ${job.proposals}\n`;
      }

      // Skills
      if (job.skills && job.skills.length > 0) {
        emailBody += `🔧 Skills: ${job.skills.slice(0, 5).join(', ') + (job.skills.length > 5 ? '...' : '')}\n`;
      }

      emailBody += `🔗 ${job.url}\n`;

      // AI reason
      if (job.ai_reason) {
        emailBody += `💡 AI: ${job.ai_reason}\n`;
      }

      emailBody += `\n`;
    }

    emailBody += `\n─────────────────────────────\n`;
    emailBody += `🤖 Powered by Groq AI | Upwork Job Agent\n`;
    emailBody += `📅 ${new Date().toLocaleString()}\n`;

    await transporter.sendMail({
      from: EMAIL_CONFIG.from,
      to: EMAIL_CONFIG.to,
      subject: `🎯 Upwork: ${jobsWithAI.length} New Jobs (${highScoreJobs.length} High Score)`,
      text: emailBody
    });

    console.log('✅ Email sent successfully!');

  } catch (err) {
    console.error('❌ Email failed:', err.message);
  }
}

// ==================== LARAVEL BACKEND SYNC ====================

/**
 * Send job data to Laravel backend
 */
async function syncJobToLaravel(job) {
  if (!LARAVEL_CONFIG.enabled) return;

  try {
    const jobData = JSON.stringify({
      job_id: job.job_id,
      title: job.title,
      description: job.description,
      url: job.url,
      budget: job.budget,
      hourly_rate: job.hourly_rate,
      skills: job.skills,
      client_country: job.client_country,
      ai_score: job.ai_score,
      ai_recommendation: job.ai_recommendation,
      ai_reason: job.ai_reason,
      fetched_at: job.fetched_at
    });

    const options = {
      hostname: '127.0.0.1',
      port: 8000,
      path: '/api/jobs',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(jobData),
        'Accept': 'application/json'
      }
    };

    return new Promise((resolve) => {
      const req = require('http').request(options, (res) => {
        let data = '';
        res.on('data', (chunk) => { data += chunk; });
        res.on('end', () => {
          if (res.statusCode === 200 || res.statusCode === 201) {
            console.log(`✅ Synced job ${job.job_id} to Laravel`);
          } else {
            console.log(`⚠️  Laravel sync failed: ${res.statusCode}`);
          }
          resolve();
        });
      });

      req.on('error', (err) => {
        console.log(`⚠️  Laravel connection error: ${err.message}`);
        resolve();
      });

      req.setTimeout(5000, () => {
        req.destroy();
        console.log('⚠️  Laravel request timeout');
        resolve();
      });

      req.write(jobData);
      req.end();
    });
  } catch (err) {
    console.log(`⚠️  Laravel sync error: ${err.message}`);
  }
}

// ==================== JOB EXTRACTION ====================

/**
 * Extract jobs from page
 * Updated selectors for current Upwork HTML structure (2024)
 */
async function extractJobs(page) {
  const jobs = await page.evaluate(() => {
    const results = [];

    // Current Upwork job tile selector (2024)
    const jobTiles = document.querySelectorAll('section.air3-card-section.air3-card-hover');

    jobTiles.forEach((tile) => {
      try {
        // Extract job link and ID
        const linkEl = tile.querySelector('a[href*="/jobs/"]');
        if (!linkEl) return;

        const href = linkEl.getAttribute('href');
        const link = href.startsWith('http') ? href : 'https://www.upwork.com' + href;

        // Extract job_id from URL pattern: /jobs/Job-Name_~022079845648021037082/
        const jobIdMatch = link.match(/\/jobs\/[^_]*_~(\d+)/);
        if (!jobIdMatch) return;

        const job_id = jobIdMatch[1];

        // Extract title
        const titleEl = tile.querySelector('h3.job-tile-title a');
        const title = titleEl?.textContent?.trim() || 'Unknown';

        // Extract description
        const descEl = tile.querySelector('[data-test="job-description-text"]');
        const description = descEl?.textContent?.trim() || '';

        // Extract job type and budget info
        const jobTypeEl = tile.querySelector('[data-test="job-type"]');
        const jobType = jobTypeEl?.textContent?.trim() || '';

        // Extract budget for fixed-price jobs
        const budgetEl = tile.querySelector('[data-test="budget"]');
        const budget = budgetEl?.textContent?.trim() || null;

        // Extract hourly rate info
        let hourly_rate = null;
        if (jobType.includes('Hourly')) {
          hourly_rate = jobType.replace('Hourly:', '').trim();
        }

        // Extract skills
        const skills = [];
        const skillEls = tile.querySelectorAll('[data-test="attr-item"]');
        skillEls.forEach(skillEl => {
          const skill = skillEl?.textContent?.trim();
          if (skill) skills.push(skill);
        });

        // Extract proposals
        const proposalsEl = tile.querySelector('[data-test="proposals-tier"]');
        const proposals = proposalsEl?.textContent?.trim() || null;

        // Extract payment verification status
        const paymentVerifiedEl = tile.querySelector('[data-test="payment-verification-status"]');
        const paymentVerified = paymentVerifiedEl?.textContent?.trim()?.includes('verified') || false;

        // Extract client country
        const countryEl = tile.querySelector('[data-test="client-country"]');
        const client_country = countryEl?.textContent?.trim()?.replace(/\s+/g, ' ').trim() || null;

        // Extract posted time
        const postedEl = tile.querySelector('[data-test="posted-on"]');
        const time_posted = postedEl?.textContent?.trim() || null;

        // Extract client spend
        const spentEl = tile.querySelector('[data-test="formatted-amount"]');
        const spent = spentEl?.textContent?.trim() || null;

        results.push({
          job_id,
          url: link,
          title,
          description,
          budget,
          hourly_rate,
          job_type: jobType,
          skills,
          proposals,
          payment_verified: paymentVerified,
          client_country,
          time_posted,
          spent,
          fetched_at: new Date().toISOString()
        });

      } catch (err) {
        // Skip this job tile if extraction fails
        console.error('Error extracting job:', err.message);
      }
    });

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
 * Save known jobs - stores ALL jobs from the page to prevent duplicates
 * This ensures previously seen jobs won't be processed again
 */
function saveKnownJobs(allCurrentJobs) {
  // Merge existing known jobs with current page jobs
  const knownIds = new Set(knownJobs.map(j => j.job_id));
  const newJobsFromPage = allCurrentJobs.filter(j => !knownIds.has(j.job_id));

  // Combine all jobs
  const mergedJobs = [...knownJobs, ...allCurrentJobs];

  // Keep only the most recent 1000 jobs to prevent file from growing too large
  const recentJobs = mergedJobs.slice(-1000);

  writeFileSync(KNOWN_JOBS_PATH, JSON.stringify(recentJobs, null, 2));

  // Update in-memory known jobs for next iteration
  knownJobs.length = 0;
  knownJobs.push(...recentJobs);

  return { total: allCurrentJobs.length, new: newJobsFromPage.length };
}

// ==================== MAIN CHECKER ====================

async function start() {
  console.log('🌐 Launching browser...');
  console.log(`📍 URL: ${config.upwork.jobsUrl}`);
  console.log(`⏰ Check interval: ${CHECK_INTERVAL / 1000} seconds`);
  console.log(`🤖 AI: Groq (${GROQ_CONFIG.model})`);
  console.log(`📧 Email: ${EMAIL_CONFIG.enabled ? 'Enabled' : 'Disabled'}`);
  console.log(`🔗 Laravel: ${LARAVEL_CONFIG.enabled ? 'Enabled' : 'Disabled'}`);
  console.log('');

  const browser = await chromium.launch({
    headless: false,
    args: ['--disable-blink-features=AutomationControlled', '--no-sandbox']
  });

  const context = await browser.newContext({
    userAgent: config.userAgent,
    viewport: config.viewport
  });

  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    window.chrome = { runtime: {} };
  });

  const page = await context.newPage();

  // Load cookies
  if (cookies.length > 0) {
    const fixedCookies = cookies.map(c => ({
      ...c,
      sameSite: (c.sameSite || '').toString().toLowerCase()
        .replace('no_restriction', 'None')
        .replace('unspecified', 'Lax')
        .replace('strict', 'Strict')
        .replace('lax', 'Lax')
        .replace('none', 'None')
    }));
    await context.addCookies(fixedCookies);
  }

  // Navigate
  console.log('🌐 Navigating to jobs page...');
  await page.goto(config.upwork.jobsUrl, {
    waitUntil: 'domcontentloaded',
    timeout: 60000
  });

  console.log('\n⏸️  PAUSED:');
  console.log('1. Complete any Cloudflare challenge in the browser');
  console.log('2. Wait for job listings to load');
  console.log('3. Press Enter here to start AI-powered checking...\n');

  await new Promise(resolve => {
    process.stdin.once('data', resolve);
  });

  console.log('\n✅ Starting AI-powered job checker...\n');

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
 * Check for new jobs with AI evaluation
 */
async function checkJobs(page) {
  const timestamp = new Date().toLocaleString();

  try {
    console.log(`\n🔍 [${timestamp}] Checking for jobs...`);

    await page.reload({ waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForTimeout(3000);

    const jobs = await extractJobs(page);
    console.log(`📊 Found ${jobs.length} jobs on page`);

    const newJobs = findNewJobs(jobs);

    if (newJobs.length > 0) {
      console.log(`✨ ${newJobs.length} NEW jobs detected!`);

      // Display new jobs with time posted
      console.log('\n📋 New Jobs:');
      for (const job of newJobs) {
        const timeAgo = job.time_posted || 'Unknown time';
        console.log(`  ⏰ ${timeAgo} | ${job.title.substring(0, 50)}...`);
        console.log(`     💰 ${job.budget || job.hourly_rate || 'N/A'} | 🌍 ${job.client_country || 'N/A'}`);
      }

      console.log(`\n🤖 Evaluating ${newJobs.length} new jobs with AI...`);

      // Evaluate each job with AI
      const jobsWithAI = [];
      for (const job of newJobs) {
        console.log(`  🔄 Analyzing: ${job.title.substring(0, 40)}...`);
        const aiResult = await evaluateJobWithAI(job);

        jobsWithAI.push({
          ...job,
          ai_score: aiResult.score,
          ai_recommendation: aiResult.recommendation,
          ai_reason: aiResult.reason,
          ai_confidence: aiResult.confidence
        });

        // Rate limiting for Groq API
        await new Promise(r => setTimeout(r, 500));
      }

      // Save ALL jobs from page to prevent re-processing
      saveKnownJobs(jobs);

      // Save new jobs with AI evaluation to output
      const output = {
        timestamp: new Date().toISOString(),
        total_found: jobs.length,
        new_jobs: jobsWithAI.length,
        jobs: jobsWithAI
      };
      writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));

      // Send email notification for new jobs only
      await sendEmailNotification(jobsWithAI);

      // Sync to Laravel
      if (LARAVEL_CONFIG.enabled) {
        console.log('🔗 Syncing to Laravel backend...');
        for (const job of jobsWithAI) {
          await syncJobToLaravel(job);
        }
      }

      console.log(`\n✅ Processed ${jobsWithAI.length} new jobs`);
      console.log(`💾 Saved to ${OUTPUT_PATH}`);

    } else {
      console.log('✅ No new jobs - all jobs on page already tracked');
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
