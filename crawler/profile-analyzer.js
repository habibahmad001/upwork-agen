/**
 * Upwork Profile Analyzer with AI Recommendations
 *
 * Features:
 * - Crawls your Upwork profile page
 * - Extracts profile data
 * - Provides AI-powered recommendations
 * - Saves analysis to profile-analysis.json
 *
 * Usage: node profile-analyzer.js
 */

import 'dotenv/config';
import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';
import https from 'https';

// ==================== CONFIGURATION ====================

const CONFIG_PATH = resolve('./config.json');
const STORAGE_PATH = resolve('./playwright/storage.json');
const OUTPUT_PATH = resolve('./profile-analysis.json');

// Profile URL (can be overridden via command line)
const PROFILE_URL = process.argv[2] || 'https://www.upwork.com/freelancers/~01c6354d1b59f0d1f4';

// Groq AI Configuration
const GROQ_CONFIG = {
  apiKey: process.env.GROQ_API_KEY || '',
  baseUrl: 'https://api.groq.com/openai/v1',
  model: 'openai/gpt-oss-120b'
};

// ==================== INITIALIZATION ====================

console.log('🚀 Upwork Profile Analyzer');
console.log('==========================\n');

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

console.log(`📍 Profile: ${PROFILE_URL}`);
console.log(`🤖 AI: Groq (${GROQ_CONFIG.model})\n`);

// ==================== GROQ AI EVALUATION ====================

/**
 * Make request to Groq API
 */
function makeGroqRequest(prompt, systemMessage = 'You are an expert Upwork profile consultant and career advisor.') {
  return new Promise((resolve, reject) => {
    const data = JSON.stringify({
      model: GROQ_CONFIG.model,
      messages: [
        { role: 'system', content: systemMessage },
        { role: 'user', content: prompt }
      ],
      temperature: 0.4,
      max_tokens: 2000
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
      res.on('data', (chunk) => { responseData += chunk; });
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
    req.setTimeout(60000, () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });

    req.write(data);
    req.end();
  });
}

/**
 * Analyze profile with AI
 */
async function analyzeProfileWithAI(profileData) {
  const prompt = `You are an expert Upwork profile consultant. Analyze this freelancer profile and provide actionable recommendations.

PROFILE DATA:
${JSON.stringify(profileData, null, 2)}

Provide a comprehensive analysis in JSON format:
{
  "overallScore": <0-100>,
  "strengths": ["strength1", "strength2", ...],
  "weaknesses": ["weakness1", "weakness2", ...],
  "skillRecommendations": {
    "criticalMissing": ["skill1", "skill2"],
    "recommendedAdditions": ["skill1", "skill2"],
    "trendingSkills": ["skill1", "skill2"],
    "outdatedToRemove": ["skill1"]
  },
  "titleImprovements": {
    "currentRating": <0-100>,
    "suggestedTitles": ["title1", "title2", "title3"],
    "issues": ["issue1", "issue2"]
  },
  "overviewImprovements": {
    "currentRating": <0-100>,
    "strengths": ["strength1"],
    "issues": ["issue1"],
    "rewriteSuggestion": "<improved overview text>"
  },
  "portfolioRecommendations": {
    "currentRating": <0-100>,
    "gaps": ["gap1"],
    "recommendedProjects": ["project idea1"],
    "presentationTips": ["tip1"]
  },
  "strategy": {
    "targetClients": ["client type1", "client type2"],
    "uniqueValue": "<what makes them unique>",
    "recommendedNiche": "<specific niche>",
    "pricingStrategy": "<suggestion>",
    "nextSteps": ["step1", "step2", "step3"]
  }
}

Only respond with valid JSON, no other text.`;

  try {
    const response = await makeGroqRequest(prompt);
    return JSON.parse(response);
  } catch (err) {
    console.log(`⚠️  AI analysis failed: ${err.message}`);
    return {
      overallScore: 50,
      strengths: [],
      weaknesses: ['AI analysis failed'],
      skillRecommendations: {},
      titleImprovements: {},
      overviewImprovements: {},
      portfolioRecommendations: {},
      strategy: {}
    };
  }
}

// ==================== PROFILE EXTRACTION ====================

/**
 * Extract profile data from page
 */
async function extractProfileData(page) {
  console.log('📊 Extracting profile data...');

  const profileData = await page.evaluate(() => {
    const result = {};

    // Basic Info
    const nameEl = document.querySelector('h1[data-test="profile-name"], .up-profile-menu h1, h1.text-h2, .freelancer-profile-name');
    result.name = nameEl?.textContent?.trim() || 'Unknown';

    const titleEl = document.querySelector('[data-test="profile-title"], .up-title-label, .freelancer-title, .profile-title-text');
    result.title = titleEl?.textContent?.trim() || 'Unknown';

    // Profile Overview / Description
    const overviewEl = document.querySelector('[data-test="profile-overview"], .up-description, .profile-overview-text, .freelancer-overview');
    result.overview = overviewEl?.textContent?.trim() || '';

    // Hourly Rate
    const rateEl = document.querySelector('[data-test="profile-rate"], .profile-rate, .hourly-rate, .display-rate');
    result.hourlyRate = rateEl?.textContent?.trim() || 'Not specified';

    // Location
    const locationEl = document.querySelector('[data-test="profile-location"], .profile-location, .location-text, .freelancer-location');
    result.location = locationEl?.textContent?.trim() || 'Unknown';

    // Job Success Score
    const jssEl = document.querySelector('[data-test="profile-jss"], .jss-score, .job-success-score, .success-score');
    result.jobSuccessScore = jssEl?.textContent?.trim() || 'Not available';

    // Total Hours / Earnings
    const hoursEl = document.querySelector('[data-test="profile-hours"], .total-hours, .hours-worked');
    result.totalHours = hoursEl?.textContent?.trim() || 'Not specified';

    const earningsEl = document.querySelector('[data-test="profile-earnings"], .total-earnings, .earnings');
    result.totalEarnings = earningsEl?.textContent?.trim() || 'Not specified';

    // Skills
    result.skills = [];
    const skillEls = document.querySelectorAll('[data-test="profile-skill"], .up-skill-badge, .skill-name, .profile-skill-item');
    skillEls.forEach(skillEl => {
      const skill = skillEl?.textContent?.trim();
      if (skill && skill.length > 0 && !result.skills.includes(skill)) {
        result.skills.push(skill);
      }
    });

    // Portfolio Items Count
    const portfolioEls = document.querySelectorAll('[data-test="portfolio-item"], .portfolio-item, .project-card');
    result.portfolioCount = portfolioEls.length;

    // Portfolio Items details
    result.portfolioItems = [];
    portfolioEls.forEach(portfolioEl => {
      const title = portfolioEl.querySelector('[data-test="portfolio-title"], .project-title')?.textContent?.trim() || '';
      const description = portfolioEl.querySelector('[data-test="portfolio-desc"], .project-description')?.textContent?.trim() || '';
      if (title) {
        result.portfolioItems.push({ title, description: description.substring(0, 300) });
      }
    });

    // Employment History
    result.employments = [];
    const employmentEls = document.querySelectorAll('[data-test="employment-item"], .employment-item, .work-history-item');
    employmentEls.forEach(empEl => {
      const title = empEl.querySelector('[data-test="employment-title"], .job-title')?.textContent?.trim() || '';
      const company = empEl.querySelector('[data-test="employment-company"], .company-name')?.textContent?.trim() || '';
      const period = empEl.querySelector('[data-test="employment-period"], .work-period')?.textContent?.trim() || '';
      if (title || company) {
        result.employments.push({ title, company, period });
      }
    });

    // Certifications
    result.certifications = [];
    const certEls = document.querySelectorAll('[data-test="certification-item"], .certification-item');
    certEls.forEach(certEl => {
      const name = certEl.querySelector('[data-test="cert-name"]')?.textContent?.trim() || '';
      const issuer = certEl.querySelector('[data-test="cert-issuer"]')?.textContent?.trim() || '';
      if (name) {
        result.certifications.push({ name, issuer });
      }
    });

    // Test Scores
    result.tests = [];
    const testEls = document.querySelectorAll('[data-test="test-score"], .test-score-item');
    testEls.forEach(testEl => {
      const testName = testEl.querySelector('[data-test="test-name"]')?.textContent?.trim() || '';
      const score = testEl.querySelector('[data-test="score-value"]')?.textContent?.trim() || '';
      if (testName) {
        result.tests.push({ name: testName, score });
      }
    });

    // Languages
    result.languages = [];
    const langEls = document.querySelectorAll('[data-test="language-item"], .language-item');
    langEls.forEach(langEl => {
      const lang = langEl?.textContent?.trim();
      if (lang && lang.length > 0) {
        result.languages.push(lang);
      }
    });

    // Education
    result.education = [];
    const eduEls = document.querySelectorAll('[data-test="education-item"], .education-item');
    eduEls.forEach(eduEl => {
      const school = eduEl.querySelector('[data-test="school-name"]')?.textContent?.trim() || '';
      const degree = eduEl.querySelector('[data-test="degree"]')?.textContent?.trim() || '';
      const field = eduEl.querySelector('[data-test="field-of-study"]')?.textContent?.trim() || '';
      if (school) {
        result.education.push({ school, degree, field });
      }
    });

    // Badges / Achievements
    result.badges = [];
    const badgeEls = document.querySelectorAll('[data-test="badge"], .profile-badge, .achievement-badge');
    badgeEls.forEach(badgeEl => {
      const badge = badgeEl?.textContent?.trim();
      if (badge && !result.badges.includes(badge)) {
        result.badges.push(badge);
      }
    });

    // Availability
    const availabilityEl = document.querySelector('[data-test="availability-status"], .availability-badge');
    result.availability = availabilityEl?.textContent?.trim() || 'Not specified';

    result.extractedAt = new Date().toISOString();

    return result;
  });

  console.log(`✅ Extracted data for: ${profileData.name}`);
  console.log(`   Title: ${profileData.title}`);
  console.log(`   Skills: ${profileData.skills.length}`);
  console.log(`   Portfolio: ${profileData.portfolioCount} items`);
  console.log(`   JSS: ${profileData.jobSuccessScore}`);

  return profileData;
}

// ==================== MAIN ANALYZER ====================

async function start() {
  console.log('🌐 Launching browser...');

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

  // Navigate to profile
  console.log(`🌐 Navigating to profile: ${PROFILE_URL}`);
  await page.goto(PROFILE_URL, {
    waitUntil: 'domcontentloaded',
    timeout: 60000
  });

  console.log('\n⏸️  PAUSED:');
  console.log('1. Wait for the profile page to fully load');
  console.log('2. Complete any verification if needed');
  console.log('3. Press Enter to start analysis...\n');

  await new Promise(resolve => {
    process.stdin.once('data', resolve);
  });

  console.log('\n✅ Starting profile analysis...\n');

  // Extract profile data
  const profileData = await extractProfileData(page);

  // Run AI analysis
  console.log('🤖 Running AI analysis...');
  const aiAnalysis = await analyzeProfileWithAI(profileData);

  // Combine results
  const result = {
    profile: profileData,
    analysis: aiAnalysis,
    analyzedAt: new Date().toISOString()
  };

  // Save to file
  writeFileSync(OUTPUT_PATH, JSON.stringify(result, null, 2));
  console.log(`\n💾 Analysis saved to: ${OUTPUT_PATH}`);

  // Print summary
  console.log('\n' + '='.repeat(60));
  console.log('📊 PROFILE ANALYSIS SUMMARY');
  console.log('='.repeat(60));

  console.log(`\n🎯 Overall Score: ${aiAnalysis.overallScore}/100`);
  if (aiAnalysis.overallScore >= 80) console.log('   ✅ Excellent profile!');
  else if (aiAnalysis.overallScore >= 60) console.log('   ⚠️  Good, but has room for improvement');
  else console.log('   ❌ Needs significant improvement');

  if (aiAnalysis.strengths && aiAnalysis.strengths.length > 0) {
    console.log(`\n💪 Strengths:`);
    aiAnalysis.strengths.slice(0, 5).forEach(s => console.log(`   • ${s}`));
  }

  if (aiAnalysis.weaknesses && aiAnalysis.weaknesses.length > 0) {
    console.log(`\n🔧 Areas to Improve:`);
    aiAnalysis.weaknesses.slice(0, 5).forEach(w => console.log(`   • ${w}`));
  }

  if (aiAnalysis.skillRecommendations) {
    console.log(`\n🎯 Skill Recommendations:`);
    if (aiAnalysis.skillRecommendations.criticalMissing) {
      aiAnalysis.skillRecommendations.criticalMissing.forEach(s => console.log(`   🔴 ${s} (critical - add this)`));
    }
    if (aiAnalysis.skillRecommendations.recommendedAdditions) {
      aiAnalysis.skillRecommendations.recommendedAdditions.slice(0, 5).forEach(s => console.log(`   💡 ${s} (recommended)`));
    }
  }

  if (aiAnalysis.titleImprovements && aiAnalysis.titleImprovements.suggestedTitles) {
    console.log(`\n📝 Suggested Titles:`);
    aiAnalysis.titleImprovements.suggestedTitles.forEach((title, i) => console.log(`   ${i + 1}. ${title}`));
  }

  if (aiAnalysis.overviewImprovements && aiAnalysis.overviewImprovements.rewriteSuggestion) {
    console.log(`\n📄 Improved Overview Suggestion:`);
    console.log(`   "${aiAnalysis.overviewImprovements.rewriteSuggestion.substring(0, 200)}..."`);
  }

  if (aiAnalysis.strategy && aiAnalysis.strategy.nextSteps) {
    console.log(`\n🚀 Next Steps:`);
    aiAnalysis.strategy.nextSteps.forEach((step, i) => console.log(`   ${i + 1}. ${step}`));
  }

  console.log('\n' + '='.repeat(60) + '\n');

  await browser.close();
  console.log('✅ Analysis complete!');
  process.exit(0);
}

// Start the analyzer
start().catch(err => {
  console.error('❌ Failed to start:', err);
  process.exit(1);
});
