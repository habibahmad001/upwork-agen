/**
 * Simple Job Extractor - No Browser Automation
 *
 * This script processes HTML content from Upwork jobs page.
 *
 * Instructions:
 * 1. Open Upwork jobs page in your browser: https://www.upwork.com/nx/find-work/best-matches
 * 2. Open browser DevTools (F12)
 * 3. Run this command in Console:
 *    copy(document.body.innerHTML)
 * 4. Run this script: node extract-jobs.js
 * 5. Paste the HTML when prompted
 */

import { writeFileSync, readFileSync } from 'fs';
import { resolve } from 'path';
import * as readline from 'readline';

const OUTPUT_PATH = resolve('./jobs.json');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

console.log('🍽️  Simple Job Extractor');
console.log('');
console.log('Instructions:');
console.log('1. Open https://www.upwork.com/nx/find-work/best-matches in your browser');
console.log('2. Press F12 to open DevTools');
console.log('3. In Console, run: copy(document.body.innerHTML)');
console.log('4. Paste the HTML below (press Enter when done)');
console.log('');

let htmlContent = '';

rl.on('line', (line) => {
  htmlContent += line + '\n';
});

rl.on('close', () => {
  try {
    extractJobs(htmlContent);
  } catch (err) {
    console.error('❌ Error:', err.message);
    process.exit(1);
  }
});

function extractJobs(html) {
  console.log('🔍 Extracting jobs...');

  // Create a simple DOM parser
  const jobs = [];

  // Find job cards using regex (simple but effective approach)
  const jobCardMatches = html.match(/<article[^>]*data-test="JobTile"[^>]*>[\s\S]*?<\/article>/gi) ||
                          html.match(/<section[^>]*data-test="job-tile"[^>]*>[\s\S]*?<\/section>/gi) ||
                          html.match(/<div[^>]*class="[^"]*job-card[^"]*"[^>]*>[\s\S]*?<\/div>/gi);

  if (!jobCardMatches) {
    console.log('⚠️  No job cards found. Trying alternative extraction...');

    // Try to find job links as fallback
    const linkMatches = html.match(/href="\/nx\/find-work[^"]*[^>]*>/gi);
    if (linkMatches) {
      console.log(`Found ${linkMatches.length} potential job links`);
    }

    console.log('💡 Try pasting just the job list section, or check if the page loaded correctly.');
    process.exit(1);
  }

  console.log(`Found ${jobCardMatches.length} job cards`);

  for (const cardHtml of jobCardMatches) {
    try {
      const job = extractJobFromCard(cardHtml);
      if (job && job.title && job.title !== 'Unknown') {
        jobs.push(job);
      }
    } catch (err) {
      // Skip this card
    }
  }

  console.log(`✅ Extracted ${jobs.length} valid jobs`);

  // Save results
  const output = {
    session_id: 'manual_' + Date.now(),
    timestamp: new Date().toISOString(),
    jobs_found: jobs.length,
    jobs: jobs,
  };

  writeFileSync(OUTPUT_PATH, JSON.stringify(output, null, 2));
  console.log(`💾 Saved to ${OUTPUT_PATH}`);

  // Show sample
  if (jobs.length > 0) {
    console.log('');
    console.log('📋 Sample job:');
    console.log(JSON.stringify(jobs[0], null, 2));
  }

  process.exit(0);
}

function extractJobFromCard(html) {
  const job = {
    job_id: null,
    title: 'Unknown',
    description: '',
    budget: null,
    hourly_rate: null,
    url: '',
    skills: [],
  };

  // Extract title and link
  const titleMatch = html.match(/<a[^>]*href="([^"]*\/job\/[^"]*)"[^>]*>([^<]+)<\/a>/i);
  if (titleMatch) {
    job.url = titleMatch[1].startsWith('http') ? titleMatch[1] : 'https://www.upwork.com' + titleMatch[1];
    job.title = titleMatch[2].trim();
  }

  // Extract job ID from URL
  const idMatch = job.url.match(/\/job\/(?:~|view\/)?([a-zA-Z0-9_-]+)/);
  if (idMatch) {
    job.job_id = idMatch[1];
  }

  // Extract description
  const descMatch = html.match(/data-test="job-description"[^>]*>([^<]+)</i);
  if (descMatch) {
    job.description = descMatch[1].trim();
  }

  // Extract budget
  const budgetMatch = html.match(/data-test="budget"[^>]*>([^<]+)</i);
  if (budgetMatch) {
    job.budget = budgetMatch[1].trim();
  }

  // Extract hourly rate
  const hourlyMatch = html.match(/data-test="hourly-rate"[^>]*>([^<]+)</i);
  if (hourlyMatch) {
    job.hourly_rate = hourlyMatch[1].trim();
  }

  // Extract skills
  const skillMatches = html.match(/data-test="skill"[^>]*>([^<]+)</gi);
  if (skillMatches) {
    job.skills = skillMatches.map(m => m.replace(/data-test="skill"[^>]*>/i, '').trim());
  }

  // Extract proposals
  const proposalsMatch = html.match(/data-test="proposals"[^>]*>([^<]+)</i);
  if (proposalsMatch) {
    job.proposals = proposalsMatch[1].trim();
  }

  // Extract client country
  const countryMatch = html.match(/data-test="country"[^>]*>([^<]+)</i);
  if (countryMatch) {
    job.client_country = countryMatch[1].trim();
  }

  return job;
}

// Windows workaround
if (process.platform === 'win32') {
  rl.on('SIGINT', () => {
    rl.close();
  });
}
