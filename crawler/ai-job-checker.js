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
  from: 'upwork-jobs@notifications.com',
  smtp: {
    host: 'smtp.gmail.com',
    port: 465,
    secure: true,
    user: 'habibahmed001@gmail.com',
    // User needs to set up App Password
    pass: 'YOUR_APP_PASSWORD_HERE' // Get from https://myaccount.google.com/apppasswords
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

    let emailBody = `🎯 Upwork Job Alert\n`;
    emailBody += `==================\n\n`;
    emailBody += `Found ${jobsWithAI.length} new jobs\n`;
    emailBody += `${highScoreJobs.length} high-score jobs (70+)\n\n`;

    for (const job of jobsWithAI) {
      const score = job.ai_score || 0;
      const rec = job.ai_recommendation || 'N/A';
      emailBody += `\n${'★'.repeat(Math.ceil(score / 20))} ${score}/100 - ${rec.toUpperCase()}\n`;
      emailBody += `📌 ${job.title}\n`;
      emailBody += `💰 ${job.budget || job.hourly_rate || 'N/A'}\n`;
      emailBody += `🔗 ${job.url}\n`;
      if (job.ai_reason) {
        emailBody += `💡 ${job.ai_reason}\n`;
      }
    }

    emailBody += `\n\n---\n`;
    emailBody += `Powered by Groq AI | Upwork Job Agent`;

    await transporter.sendMail({
      from: EMAIL_CONFIG.from,
      to: EMAIL_CONFIG.to,
      subject: `Upwork: ${jobsWithAI.length} New Jobs (${highScoreJobs.length} High Score)`,
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
 */
async function extractJobs(page) {
  const jobs = await page.evaluate(() => {
    const results = [];
    const selectors = [
      '[data-test="JobTile"]',
      'section[data-test="job-tile"]',
      'article.up-card'
    ];

    for (const selector of selectors) {
      const elements = document.querySelectorAll(selector);
      if (elements.length > 0) {
        elements.forEach((el) => {
          const linkEl = el.querySelector('a[href*="/job/"]') || el;
          const link = linkEl.href || '';

          if (link.includes('/job/')) {
            const titleMatch = link.match(/\/job\/(?:~|view\/)?([a-zA-Z0-9_-]+)/);

            if (titleMatch) {
              const titleEl = el.querySelector('[data-test="job-title"], h2, h3');
              const descEl = el.querySelector('[data-test="job-description"]');
              const budgetEl = el.querySelector('[data-test="budget"]');
              const hourlyEl = el.querySelector('[data-test="hourly-rate"]');
              const skillsEls = el.querySelectorAll('[data-test="skill"]');
              const proposalsEl = el.querySelector('[data-test="proposals"]');
              const countryEl = el.querySelector('[data-test="country"]');

              const skills = [];
              skillsEls.forEach(s => {
                const t = s.textContent?.trim();
                if (t) skills.push(t);
              });

              results.push({
                job_id: titleMatch[1],
                url: link.startsWith('http') ? link : 'https://www.upwork.com' + link,
                title: titleEl?.textContent?.trim() || 'Unknown',
                description: descEl?.textContent?.trim() || '',
                budget: budgetEl?.textContent?.trim() || null,
                hourly_rate: hourlyEl?.textContent?.trim() || null,
                skills: skills,
                proposals: proposalsEl?.textContent?.trim() || null,
                client_country: countryEl?.textContent?.trim() || null,
                fetched_at: new Date().toISOString()
              });
            }
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
      console.log(`✨ ${newJobs.length} NEW jobs! Evaluating with AI...`);

      // Evaluate each job with AI
      const jobsWithAI = [];
      for (const job of newJobs) {
        console.log(`  🤖 Evaluating: ${job.title.substring(0, 40)}...`);
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

      // Save known jobs
      saveKnownJobs(jobsWithAI);

      // Save all jobs to output
      const output = {
        timestamp: new Date().toISOString(),
        total_found: jobs.length,
        new_jobs: jobsWithAI.length,
        jobs: jobsWithAI
      };
      writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));

      // Send email notification
      await sendEmailNotification(jobsWithAI);

      // Sync to Laravel
      if (LARAVEL_CONFIG.enabled) {
        console.log('🔗 Syncing to Laravel backend...');
        for (const job of jobsWithAI) {
          await syncJobToLaravel(job);
        }
      }

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
