/**
 * Automated Job Checker
 *
 * Runs periodically to check for new jobs and sends email notifications.
 * Usage: node job-checker.js
 * Schedule: Run every minute via cron/node-cron
 */

import https from 'https';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

// Configuration
const CONFIG_PATH = resolve('./config.json');
const STORAGE_PATH = resolve('./playwright/storage.json');
const KNOWN_JOBS_PATH = resolve('./known-jobs.json');
const OUTPUT_PATH = resolve('./jobs.json');

// Email configuration (update these)
const EMAIL_CONFIG = {
  enabled: true,
  to: 'habibahmed001@gmail.com',
  from: 'upwork-jobs@example.com',
  // For Gmail, use App Password: https://support.google.com/accounts/answer/185833
  smtp: {
    host: 'smtp.gmail.com',
    port: 465,
    secure: true,
    user: 'habibahmed001@gmail.com', // Your Gmail
    pass: 'your-app-password' // Generate at https://myaccount.google.com/apppasswords
  }
};

// Load configuration
let config;
try {
  config = JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
} catch (err) {
  console.error('Failed to load config:', err.message);
  process.exit(1);
}

// Load cookies for authentication
let cookies = [];
try {
  const storage = JSON.parse(readFileSync(STORAGE_PATH, 'utf-8'));
  cookies = storage.cookies || [];
  console.log(`✅ Loaded ${cookies.length} cookies`);
} catch (err) {
  console.error('Failed to load cookies:', err.message);
  console.error('Please run: node crawler\\playwright\\login.js');
  process.exit(1);
}

// Load known jobs (to detect new ones)
let knownJobs = [];
try {
  if (existsSync(KNOWN_JOBS_PATH)) {
    knownJobs = JSON.parse(readFileSync(KNOWN_JOBS_PATH, 'utf-8'));
  }
} catch (err) {
  console.log('No known jobs file, starting fresh');
}

/**
 * Convert Playwright cookies to Cookie header format
 */
function cookiesToHeader(cookies) {
  return cookies
    .filter(c => c.domain.includes('upwork') && c.name && c.value)
    .map(c => `${c.name}=${c.value}`)
    .join('; ');
}

/**
 * Make HTTPS request with cookies
 */
function fetchJobs() {
  return new Promise((resolve, reject) => {
    const url = new URL(config.upwork.jobsUrl);

    // More realistic headers
    const headers = {
      'User-Agent': config.userAgent,
      'Cookie': cookiesToHeader(cookies),
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
      'Accept-Language': 'en-US,en;q=0.9',
      'Accept-Encoding': 'gzip, deflate, br',
      'Cache-Control': 'no-cache',
      'Pragma': 'no-cache',
      'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
      'Sec-Ch-Ua-Mobile': '?0',
      'Sec-Ch-Ua-Platform': '"Windows"',
      'Sec-Fetch-Dest': 'document',
      'Sec-Fetch-Mode': 'navigate',
      'Sec-Fetch-Site': 'none',
      'Sec-Fetch-User': '?1',
      'Upgrade-Insecure-Requests': '1',
      'Referer': 'https://www.upwork.com/',
      'Origin': 'https://www.upwork.com'
    };

    const options = {
      hostname: url.hostname,
      port: 443,
      path: url.pathname + url.search,
      method: 'GET',
      headers: headers,
      // Add these to handle TLS properly
      servername: url.hostname
    };

    console.log('🌐 Fetching jobs from:', config.upwork.jobsUrl);

    const req = https.request(options, (res) => {
      let data = '';

      res.on('data', (chunk) => {
        data += chunk;
      });

      res.on('end', () => {
        if (res.statusCode === 200) {
          resolve(data);
        } else if (res.statusCode === 302 || res.statusCode === 301) {
          // Redirect - likely to login
          console.log('⚠️  Redirected to:', res.headers.location);
          reject(new Error('Authentication failed - cookies may be expired'));
        } else {
          console.log('❌ Status code:', res.statusCode);
          console.log('Headers:', JSON.stringify(res.headers, null, 2));
          reject(new Error(`HTTP ${res.statusCode}`));
        }
      });
    });

    req.on('error', reject);
    req.setTimeout(30000, () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });

    req.end();
  });
}

/**
 * Parse jobs from HTML
 */
function parseJobs(html) {
  const jobs = [];

  // Find job cards - try multiple patterns
  const patterns = [
    /<article[^>]*data-test="JobTile"[^>]*>([\s\S]*?)<\/article>/gi,
    /<section[^>]*data-test="job-tile"[^>]*>([\s\S]*?)<\/section>/gi,
    /<a[^>]*href="\/nx\/find-work[^"]*"[^>]*>([\s\S]*?)<\/a>/gi
  ];

  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(html)) !== null) {
      const cardHtml = match[1] || match[0];

      try {
        const job = extractJobFromCard(cardHtml, html);
        if (job && job.job_id && job.title !== 'Unknown') {
          // Avoid duplicates
          if (!jobs.find(j => j.job_id === job.job_id)) {
            jobs.push(job);
          }
        }
      } catch (err) {
        // Skip invalid cards
      }
    }
  }

  return jobs;
}

/**
 * Extract job data from card HTML
 */
function extractJobFromCard(cardHtml, fullHtml) {
  const job = {
    job_id: null,
    title: 'Unknown',
    description: '',
    budget: null,
    hourly_rate: null,
    url: '',
    skills: [],
    client_country: null,
    payment_verified: false,
    proposals: null,
    time_posted: null,
    fetched_at: new Date().toISOString()
  };

  // Extract title and link
  const titleMatch = cardHtml.match(/<a[^>]*href="([^"]*\/job\/(?:~|view\/)?([a-zA-Z0-9_-]+))"[^>]*>([^<]+)</i);
  if (titleMatch) {
    job.url = titleMatch[1].startsWith('http') ? titleMatch[1] : 'https://www.upwork.com' + titleMatch[1];
    job.job_id = titleMatch[2];
    job.title = titleMatch[3].replace(/<[^>]*>/g, '').trim();
  }

  // Alternative: find job link and title separately
  if (!job.job_id) {
    const linkMatch = cardHtml.match(/href="\/job\/(~|view\/)?([a-zA-Z0-9_-]+)"/i);
    if (linkMatch) {
      job.job_id = linkMatch[2];
      job.url = 'https://www.upwork.com/job/' + linkMatch[2];
    }

    const titleTextMatch = cardHtml.match(/data-test="job-title"[^>]*>([^<]+)</i);
    if (titleTextMatch) {
      job.title = titleTextMatch[1].trim();
    }
  }

  // Extract description
  const descMatch = cardHtml.match(/data-test="job-description"[^>]*>([^<]+)</i);
  if (descMatch) {
    job.description = descMatch[1].trim();
  }

  // Extract budget
  const budgetMatch = cardHtml.match(/data-test="budget"[^>]*>([^<]+)</i);
  if (budgetMatch) {
    job.budget = budgetMatch[1].trim();
  }

  // Extract hourly rate
  const hourlyMatch = cardHtml.match(/data-test="hourly-rate"[^>]*>([^<]+)</i);
  if (hourlyMatch) {
    job.hourly_rate = hourlyMatch[1].trim();
  }

  // Extract skills
  const skillMatches = cardHtml.match(/data-test="skill"[^>]*>([^<]+)</gi);
  if (skillMatches) {
    job.skills = skillMatches.map(m => m.replace(/data-test="skill"[^>]*>/i, '').trim());
  }

  // Extract proposals
  const proposalsMatch = cardHtml.match(/data-test="proposals"[^>]*>([^<]+)</i);
  if (proposalsMatch) {
    job.proposals = proposalsMatch[1].trim();
  }

  // Extract client country
  const countryMatch = cardHtml.match(/data-test="country"[^>]*>([^<]+)</i);
  if (countryMatch) {
    job.client_country = countryMatch[1].trim();
  }

  // Extract payment verified
  job.payment_verified = cardHtml.includes('data-test="payment-verified"');

  // Extract time posted
  const timeMatch = cardHtml.match(/data-test="posted-on"[^>]*>([^<]+)</i);
  if (timeMatch) {
    job.time_posted = timeMatch[1].trim();
  }

  return job;
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
  // Keep only last 1000 jobs to avoid file growing too large
  const recentJobs = allJobs.slice(-1000);
  writeFileSync(KNOWN_JOBS_PATH, JSON.stringify(recentJobs, null, 2));
}

/**
 * Send email notification (stub - implement with nodemailer)
 */
function sendEmailNotification(newJobs) {
  console.log(`📧 Would send email for ${newJobs.length} new jobs to ${EMAIL_CONFIG.to}`);

  // TODO: Implement actual email sending
  // Install: npm install nodemailer
  //
  // const nodemailer = require('nodemailer');
  // const transporter = nodemailer.createTransport({
  //   host: EMAIL_CONFIG.smtp.host,
  //   port: EMAIL_CONFIG.smtp.port,
  //   secure: EMAIL_CONFIG.smtp.secure,
  //   auth: {
  //     user: EMAIL_CONFIG.smtp.user,
  //     pass: EMAIL_CONFIG.smtp.pass
  //   }
  // });
  //
  // const jobList = newJobs.map(j => `- ${j.title}: ${j.url}`).join('\n');
  // await transporter.sendMail({
  //   from: EMAIL_CONFIG.from,
  //   to: EMAIL_CONFIG.to,
  //   subject: `Upwork: ${newJobs.length} New Jobs Found`,
  //   text: `Found ${newJobs.length} new jobs:\n\n${jobList}`
  // });

  // For now, just log to console
  for (const job of newJobs) {
    console.log(`🆕 ${job.title}`);
    console.log(`   ${job.url}`);
    console.log(`   Budget: ${job.budget || job.hourly_rate || 'N/A'}`);
    console.log('');
  }
}

/**
 * Main function
 */
async function main() {
  console.log('🚀 Starting job checker...');
  console.log(`⏰ Time: ${new Date().toLocaleString()}`);

  try {
    // Fetch jobs
    const html = await fetchJobs();

    // Parse jobs
    const jobs = parseJobs(html);
    console.log(`📊 Found ${jobs.length} jobs on page`);

    // Find new jobs
    const newJobs = findNewJobs(jobs);

    if (newJobs.length > 0) {
      console.log(`✨ ${newJobs.length} new jobs found!`);

      // Send email notification
      if (EMAIL_CONFIG.enabled) {
        sendEmailNotification(newJobs);
      }

      // Save new jobs to known list
      saveKnownJobs(newJobs);

      // Save all jobs to output file
      const output = {
        timestamp: new Date().toISOString(),
        total_found: jobs.length,
        new_jobs: newJobs.length,
        jobs: jobs
      };
      writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));

      console.log(`💾 Saved jobs to ${OUTPUT_PATH}`);
    } else {
      console.log('✅ No new jobs');
    }

    console.log('✨ Check complete!\n');

  } catch (err) {
    console.error('❌ Error:', err.message);

    if (err.message.includes('Authentication failed')) {
      console.log('💡 Please refresh your cookies:');
      console.log('   1. Open Upwork in your browser');
      console.log('   2. Export cookies using EditThisCookie extension');
      console.log('   3. Run the cookie import script');
    }

    process.exit(1);
  }
}

// Run the checker
main();
