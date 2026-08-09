/**
 * Q&A Job Checker - For /jobs-proposal-qa page
 *
 * Features:
 * - Crawls Upwork every 60 seconds
 * - Evaluates jobs with Groq AI (full analysis)
 * - Extracts client questions for Q&A proposals
 * - Sends Pusher notifications
 *
 * Usage: node qa-job-checker.js
 */

import 'dotenv/config';
import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';
import https from 'https';
import nodemailer from 'nodemailer';

// ==================== CONFIGURATION ====================

const CONFIG_PATH = resolve('./config.json');
const STORAGE_PATH = resolve('./playwright/storage.json');
const KNOWN_JOBS_PATH = resolve('./known-jobs-qa.json');
const OUTPUT_PATH = resolve('./jobs-qa.json');

// Groq AI Configuration
const GROQ_CONFIG = {
  apiKey: process.env.GROQ_API_KEY || '',
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
  apiKey: 'your-api-key-here'
};

const CHECK_INTERVAL = 60 * 1000; // 60 seconds

// ==================== INITIALIZATION ====================

console.log('🚀 Q&A Job Checker (for /jobs-proposal-qa)');
console.log('=============================================\n');

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
 * Evaluate job with Groq AI (full analysis)
 */
async function evaluateJobWithAI(job) {
  const prompt = `You are an expert freelance job evaluator. Analyze this Upwork job and provide a comprehensive score.

Job Details:
- Title: ${job.title}
- Description: ${job.description?.substring(0, 800)}
- Budget: ${job.budget || job.hourly_rate || 'Not specified'}
- Skills: ${job.skills?.join(', ') || 'Not specified'}
- Client Country: ${job.client_country || 'Not specified'}
- Payment Verified: ${job.payment_verified ? 'Yes' : 'No'}
- Proposals: ${job.proposals || 'Unknown'}

Evaluate based on:
1. Budget adequacy for the work
2. Client payment verification status
3. Skill match (for web development)
4. Project clarity
5. Competition level (proposals count)

Respond in JSON format:
{
  "score": <0-100>,
  "recommendation": "apply|skip|consider",
  "reason": "<detailed explanation of why this job is good or bad>",
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
 * Extract client questions from job description
 */
function extractClientQuestions(description) {
  const questions = [];

  // Common patterns for client questions in job descriptions
  const questionPatterns = [
    /(?:question|queries?|ask|answer|respond|reply)(?:s|:)?\s*["']?([^"'\n]+?)["']?\s*(?:\.|$)/gi,
    /describe your (.+?)\./gi,
    /include a (.+?)\./gi,
    /what is your (.+?)\./gi,
    /how do you (.+?)\./gi,
    /explain your (.+?)\./gi
  ];

  for (const pattern of questionPatterns) {
    let match;
    while ((match = pattern.exec(description)) !== null) {
      const question = match[1] || match[0];
      if (question.length > 10 && question.length < 200) {
        // Clean up the question
        const cleaned = question
          .replace(/^(question|queries?|ask|answer|respond|reply)\s*(is|:)?\s*/i, '')
          .replace(/[."']$/, '')
          .trim();
        if (cleaned && !questions.includes(cleaned)) {
          questions.push(cleaned);
        }
      }
    }
  }

  // If no specific questions found, add default ones
  if (questions.length === 0) {
    questions.push(
      'Describe your recent experience with similar projects',
      'What is your approach to testing and quality assurance?',
      'How do you handle project communication and updates?'
    );
  }

  return questions.slice(0, 5); // Max 5 questions
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

  if (EMAIL_CONFIG.smtp.pass === 'YOUR_APP_PASSWORD_HERE') {
    console.log('⚠️  Email not configured. Set up App Password:');
    console.log('   1. Go to https://myaccount.google.com/apppasswords');
    console.log('   2. Create an App Password');
    console.log('   3. Update EMAIL_CONFIG.smtp.pass in qa-job-checker.js');
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

    let emailBody = `🎯 Upwork Job Alert - Q&A Ready Jobs!\n`;
    emailBody += `========================================\n\n`;
    emailBody += `📊 Summary:\n`;
    emailBody += `  • Total new jobs: ${jobsWithAI.length}\n`;
    emailBody += `  • High score (70+): ${highScoreJobs.length}\n\n`;

    const sortedJobs = jobsWithAI.sort((a, b) => (b.ai_score || 0) - (a.ai_score || 0));

    for (const job of sortedJobs) {
      const score = job.ai_score || 0;
      const rec = job.ai_recommendation || 'N/A';
      const stars = Math.ceil(score / 20);

      emailBody += `\n${'⭐'.repeat(stars)} ${score}/100 - ${rec.toUpperCase()}\n`;
      emailBody += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
      emailBody += `📌 ${job.title}\n`;
      emailBody += `💰 ${job.budget || job.hourly_rate || 'N/A'} | 🌍 ${job.client_country || 'N/A'}\n`;
      emailBody += `🔗 ${job.url}\n`;

      if (job.client_questions && job.client_questions.length > 0) {
        emailBody += `📝 Client Questions (${job.client_questions.length}):\n`;
        job.client_questions.forEach((q, i) => {
          emailBody += `   ${i + 1}. ${q}\n`;
        });
      }

      emailBody += `\n`;
    }

    emailBody += `\n─────────────────────────────\n`;
    emailBody += `🤖 Powered by Groq AI | Q&A Job Checker\n`;
    emailBody += `📅 ${new Date().toLocaleString()}\n`;

    await transporter.sendMail({
      from: EMAIL_CONFIG.from,
      to: EMAIL_CONFIG.to,
      subject: `🎯 Upwork: ${jobsWithAI.length} New Jobs with Q&A`,
      text: emailBody
    });

    console.log('✅ Email sent successfully!');

  } catch (err) {
    console.error('❌ Email failed:', err.message);
  }
}

// ==================== PUSHER NOTIFICATIONS ====================

/**
 * Send Pusher notification
 */
async function sendPusherNotification(jobsWithAI) {
  const { default: Pusher } = await import('pusher');

  const pusher = new Pusher({
    appId: '1695089',
    key: '4d2cd7d38e091601e28c',
    secret: '35d959b307a0e508a7b9',
    cluster: 'ap2',
    useTLS: true
  });

  const channel = 'jobs';
  const event = 'new-job';

  try {
    for (const job of jobsWithAI) {
      const notificationData = {
        type: 'new-job',
        job_id: job.job_id,
        title: job.title,
        description: job.description?.substring(0, 200) || '',
        budget: job.budget || job.hourly_rate || 'Not specified',
        url: job.url,
        client_country: job.client_country || 'Unknown',
        payment_verified: job.payment_verified || false,
        proposals: job.proposals || '0',
        time_posted: job.time_posted || 'Just now',
        skills: job.skills || [],
        ai_score: job.ai_score || 0,
        recommendation: job.ai_recommendation || 'consider',
        reasoning: job.ai_reason || '',
        client_questions: job.client_questions || [],
        emoji: getScoreEmoji(job.ai_score || 0),
        timestamp: new Date().toISOString()
      };

      await pusher.trigger(channel, event, notificationData);
      console.log(`📤 Pusher notification sent for: ${job.title.substring(0, 30)}...`);
    }

    console.log(`✅ Sent ${jobsWithAI.length} Pusher notifications`);
  } catch (err) {
    console.error('❌ Pusher notification failed:', err.message);
  }
}

/**
 * Get emoji based on AI score
 */
function getScoreEmoji(score) {
  if (score >= 90) return '🔥';
  if (score >= 80) return '✨';
  if (score >= 70) return '👍';
  if (score >= 50) return '🤔';
  return '⚠️';
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
      client_questions: job.client_questions,
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
      const req = http.request(options, (res) => {
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
    const jobTiles = document.querySelectorAll('section.air3-card-section.air3-card-hover');

    jobTiles.forEach((tile) => {
      try {
        const linkEl = tile.querySelector('a[href*="/jobs/"]');
        if (!linkEl) return;

        const href = linkEl.getAttribute('href');
        const link = href.startsWith('http') ? href : 'https://www.upwork.com' + href;

        const jobIdMatch = link.match(/\/jobs\/[^_]*_~(\d+)/);
        if (!jobIdMatch) return;

        const job_id = jobIdMatch[1];
        const titleEl = tile.querySelector('h3.job-tile-title a');
        const title = titleEl?.textContent?.trim() || 'Unknown';

        const descEl = tile.querySelector('[data-test="job-description-text"]');
        const description = descEl?.textContent?.trim() || '';

        const jobTypeEl = tile.querySelector('[data-test="job-type"]');
        const jobType = jobTypeEl?.textContent?.trim() || '';

        const budgetEl = tile.querySelector('[data-test="budget"]');
        const budget = budgetEl?.textContent?.trim() || null;

        let hourly_rate = null;
        if (jobType.includes('Hourly')) {
          hourly_rate = jobType.replace('Hourly:', '').trim();
        }

        const skills = [];
        const skillEls = tile.querySelectorAll('[data-test="attr-item"]');
        skillEls.forEach(skillEl => {
          const skill = skillEl?.textContent?.trim();
          if (skill) skills.push(skill);
        });

        const proposalsEl = tile.querySelector('[data-test="proposals-tier"]');
        const proposals = proposalsEl?.textContent?.trim() || null;

        const paymentVerifiedEl = tile.querySelector('[data-test="payment-verification-status"]');
        const paymentVerified = paymentVerifiedEl?.textContent?.trim()?.includes('verified') || false;

        const countryEl = tile.querySelector('[data-test="client-country"]');
        const client_country = countryEl?.textContent?.trim()?.replace(/\s+/g, ' ').trim() || null;

        const postedEl = tile.querySelector('[data-test="posted-on"]');
        const time_posted = postedEl?.textContent?.trim() || null;

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
 * Save known jobs
 */
function saveKnownJobs(allCurrentJobs) {
  const knownIds = new Set(knownJobs.map(j => j.job_id));
  const newJobsFromPage = allCurrentJobs.filter(j => !knownIds.has(j.job_id));

  const mergedJobs = [...knownJobs, ...allCurrentJobs];
  const recentJobs = mergedJobs.slice(-1000);

  writeFileSync(KNOWN_JOBS_PATH, JSON.stringify(recentJobs, null, 2));

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
  console.log(`📡 Pusher: Enabled`);
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

  console.log('🌐 Navigating to jobs page...');
  await page.goto(config.upwork.jobsUrl, {
    waitUntil: 'domcontentloaded',
    timeout: 60000
  });

  const autoStart = process.env.AUTO_START === 'true' || process.argv.includes('--auto-start');

  if (autoStart) {
    console.log('\n🤖 Auto-start mode: Waiting 30 seconds for page to load...');
    await new Promise(resolve => setTimeout(resolve, 30000));
    console.log('✅ Starting Q&A job checker automatically...\n');
  } else {
    console.log('\n⏸️  PAUSED:');
    console.log('1. Complete any Cloudflare challenge in the browser');
    console.log('2. Wait for job listings to load');
    console.log('3. Press Enter here to start...\n');

    await new Promise(resolve => {
      process.stdin.once('data', resolve);
    });

    console.log('\n✅ Starting Q&A job checker...\n');
  }

  await checkJobs(page);

  const intervalId = setInterval(() => {
    checkJobs(page).catch(err => {
      console.error('❌ Check failed:', err.message);
    });
  }, CHECK_INTERVAL);

  process.on('SIGINT', async () => {
    console.log('\n🛑 Stopping...');
    clearInterval(intervalId);
    await browser.close();
    console.log('✅ Done');
    process.exit(0);
  });
}

/**
 * Check for new jobs with AI evaluation and Q&A extraction
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

      console.log('\n📋 New Jobs:');
      for (const job of newJobs) {
        const timeAgo = job.time_posted || 'Unknown time';
        console.log(`  ⏰ ${timeAgo} | ${job.title.substring(0, 50)}...`);
        console.log(`     💰 ${job.budget || job.hourly_rate || 'N/A'} | 🌍 ${job.client_country || 'N/A'}`);
      }

      console.log(`\n🤖 Evaluating ${newJobs.length} new jobs with AI...`);

      const jobsWithAI = [];
      for (const job of newJobs) {
        console.log(`  🔄 Analyzing: ${job.title.substring(0, 40)}...`);
        const aiResult = await evaluateJobWithAI(job);

        // Extract client questions for Q&A
        const clientQuestions = extractClientQuestions(job.description || '');

        jobsWithAI.push({
          ...job,
          ai_score: aiResult.score,
          ai_recommendation: aiResult.recommendation,
          ai_reason: aiResult.reason,
          ai_confidence: aiResult.confidence,
          client_questions: clientQuestions
        });

        // Rate limiting for Groq API
        await new Promise(r => setTimeout(r, 500));
      }

      saveKnownJobs(jobs);

      const output = {
        timestamp: new Date().toISOString(),
        total_found: jobs.length,
        new_jobs: jobsWithAI.length,
        jobs: jobsWithAI
      };
      writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));

      await sendEmailNotification(jobsWithAI);
      await sendPusherNotification(jobsWithAI);

      if (LARAVEL_CONFIG.enabled) {
        console.log('🔗 Syncing to Laravel backend...');
        for (const job of jobsWithAI) {
          await syncJobToLaravel(job);
        }
      }

      console.log(`\n✅ Processed ${jobsWithAI.length} new jobs`);
      console.log(`💾 Saved to ${OUTPUT_PATH}`);
      console.log(`📝 Client questions extracted for Q&A proposals`);

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
