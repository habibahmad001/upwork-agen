/**
 * Upwork Profile Analyzer - From HTML
 *
 * Use this when captcha prevents direct browser access.
 *
 * Instructions:
 * 1. Open your profile in Chrome browser
 * 2. Right-click -> "Save Page As" -> "Webpage, Complete"
 * 3. Or: Right-click -> "Inspect" -> Right-click on <html> -> "Copy" -> "Copy outerHTML"
 * 4. Run this script and paste the HTML when prompted
 *
 * Usage: node profile-analyzer-from-html.js
 */

import 'dotenv/config';
import { readFileSync, writeFileSync } from 'fs';
import { resolve } from 'path';
import https from 'https';

// Groq AI Configuration
const GROQ_CONFIG = {
  apiKey: process.env.GROQ_API_KEY || '',
  baseUrl: 'https://api.groq.com/openai/v1',
  model: 'openai/gpt-oss-120b'
};

const OUTPUT_PATH = resolve('./profile-analysis.json');

console.log('🔍 Upwork Profile Analyzer - From HTML');
console.log('========================================\n');

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
      max_tokens: 3000
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
 * Extract profile data from HTML using AI
 */
async function extractProfileDataFromHTML(html) {
  console.log('🤖 Extracting profile data from HTML...');

  const prompt = `Extract all profile information from this Upwork freelancer profile HTML.

HTML CONTENT (truncated to first 15000 chars):
${html.substring(0, 15000)}

Extract and return ONLY a JSON object with this exact structure:
{
  "name": "full name",
  "title": "profile title/headline",
  "overview": "full profile description/overview text",
  "hourlyRate": "hourly rate as shown",
  "location": "location/country",
  "jobSuccessScore": "JSS score if visible",
  "totalHours": "total hours worked",
  "totalEarnings": "total earnings",
  "skills": ["skill1", "skill2", ...],
  "portfolioCount": number of portfolio items,
  "portfolioItems": [{"title": "project title", "description": "brief description"}, ...],
  "employments": [{"title": "job title", "company": "company", "period": "time period"}, ...],
  "certifications": [{"name": "cert name", "issuer": "issuer"}, ...],
  "tests": [{"name": "test name", "score": "score"}, ...],
  "education": [{"school": "school", "degree": "degree", "field": "field"}, ...],
  "languages": ["language1", "language2"],
  "badges": ["badge1", "badge2"],
  "availability": "availability status"
}

If a field is not found, use empty string, empty array, or "Not specified". Return ONLY valid JSON, no markdown, no extra text.`;

  try {
    const response = await makeGroqRequest(prompt, 'You are a web scraping expert. Extract structured data from HTML and return only valid JSON.');
    return JSON.parse(response);
  } catch (err) {
    console.error('❌ Failed to extract profile data:', err.message);
    return null;
  }
}

/**
 * Analyze profile with AI
 */
async function analyzeProfileWithAI(profileData) {
  console.log('🤖 Analyzing profile...');

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
    "rewriteSuggestion": "<improved overview text (100-200 words)>"
  },
  "portfolioRecommendations": {
    "currentRating": <0-100>,
    "gaps": ["gap1"],
    "recommendedProjects": ["specific project idea 1", "project idea 2"],
    "presentationTips": ["tip1", "tip2"]
  },
  "strategy": {
    "targetClients": ["client type1", "client type2"],
    "uniqueValue": "<what makes them unique - 50-100 words>",
    "recommendedNiche": "<specific niche to focus on>",
    "pricingStrategy": "<suggestion for hourly rate>",
    "servicePackages": ["package idea 1", "package idea 2"],
    "nextSteps": ["immediate step1", "step2", "step3"]
  }
}

Only respond with valid JSON, no other text.`;

  try {
    const response = await makeGroqRequest(prompt);
    return JSON.parse(response);
  } catch (err) {
    console.error('❌ AI analysis failed:', err.message);
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

/**
 * Main execution
 */
async function main() {
  console.log('Instructions to get your profile HTML:\n');
  console.log('Option 1 (Recommended):');
  console.log('  1. Open your profile in Chrome');
  console.log('  2. Press F12 to open DevTools');
  console.log('  3. Right-click on the <html> element in Elements tab');
  console.log('  4. Select "Copy" -> "Copy outerHTML"');
  console.log('  5. Paste below and press Ctrl+D (Windows) or Ctrl+Z (Mac)\n');
  console.log('Option 2:');
  console.log('  1. Open your profile in Chrome');
  console.log('  2. Right-click -> "Save Page As" -> "Webpage, Complete"');
  console.log('  3. Open the saved .html file in a text editor');
  console.log('  4. Copy the content and paste below\n');
  console.log('📝 Paste your HTML below, then press Ctrl+D (Windows) or Ctrl+Z (Mac) to finish:\n');

  let html = '';
  process.stdin.on('data', (chunk) => {
    html += chunk.toString();
  });

  await new Promise((resolve) => {
    process.stdin.on('end', resolve);
  });

  if (!html || html.trim().length < 1000) {
    console.error('❌ HTML content is too short. Please copy the full page HTML.');
    process.exit(1);
  }

  console.log(`\n✅ Received ${html.length} characters of HTML\n`);

  // Extract profile data
  const profileData = await extractProfileDataFromHTML(html);

  if (!profileData) {
    console.error('❌ Failed to extract profile data. Please check the HTML content.');
    process.exit(1);
  }

  console.log(`✅ Extracted profile for: ${profileData.name}`);
  console.log(`   Title: ${profileData.title}`);
  console.log(`   Skills: ${profileData.skills?.length || 0}`);
  console.log(`   Portfolio: ${profileData.portfolioCount || 0} items`);
  console.log(`   JSS: ${profileData.jobSuccessScore || 'N/A'}\n`);

  // Run AI analysis
  const aiAnalysis = await analyzeProfileWithAI(profileData);

  // Combine results
  const result = {
    profile: profileData,
    analysis: aiAnalysis,
    analyzedAt: new Date().toISOString()
  };

  // Save to file
  writeFileSync(OUTPUT_PATH, JSON.stringify(result, null, 2));
  console.log(`💾 Analysis saved to: ${OUTPUT_PATH}\n`);

  // Print summary
  console.log('='.repeat(60));
  console.log('📊 PROFILE ANALYSIS SUMMARY');
  console.log('='.repeat(60));

  console.log(`\n🎯 Overall Score: ${aiAnalysis.overallScore}/100`);
  if (aiAnalysis.overallScore >= 80) console.log('   ✅ Excellent profile!');
  else if (aiAnalysis.overallScore >= 60) console.log('   ⚠️  Good, but has room for improvement');
  else console.log('   ❌ Needs significant improvement');

  if (aiAnalysis.strengths?.length > 0) {
    console.log(`\n💪 Strengths:`);
    aiAnalysis.strengths.slice(0, 5).forEach(s => console.log(`   • ${s}`));
  }

  if (aiAnalysis.weaknesses?.length > 0) {
    console.log(`\n🔧 Areas to Improve:`);
    aiAnalysis.weaknesses.slice(0, 5).forEach(w => console.log(`   • ${w}`));
  }

  if (aiAnalysis.skillRecommendations) {
    console.log(`\n🎯 Skill Recommendations:`);
    if (aiAnalysis.skillRecommendations.criticalMissing?.length > 0) {
      console.log('   🔴 Critical (add these):');
      aiAnalysis.skillRecommendations.criticalMissing.forEach(s => console.log(`      • ${s}`));
    }
    if (aiAnalysis.skillRecommendations.recommendedAdditions?.length > 0) {
      console.log('   💡 Recommended:');
      aiAnalysis.skillRecommendations.recommendedAdditions.slice(0, 5).forEach(s => console.log(`      • ${s}`));
    }
    if (aiAnalysis.skillRecommendations.trendingSkills?.length > 0) {
      console.log('   🔥 Trending:');
      aiAnalysis.skillRecommendations.trendingSkills.slice(0, 5).forEach(s => console.log(`      • ${s}`));
    }
  }

  if (aiAnalysis.titleImprovements?.suggestedTitles) {
    console.log(`\n📝 Suggested Titles (current rating: ${aiAnalysis.titleImprovements.currentRating}/100):`);
    aiAnalysis.titleImprovements.suggestedTitles.forEach((title, i) => console.log(`   ${i + 1}. ${title}`));
  }

  if (aiAnalysis.overviewImprovements?.issues) {
    console.log(`\n📄 Overview Issues (rating: ${aiAnalysis.overviewImprovements.currentRating}/100):`);
    aiAnalysis.overviewImprovements.issues.forEach(issue => console.log(`   • ${issue}`));
  }

  if (aiAnalysis.overviewImprovements?.rewriteSuggestion) {
    console.log(`\n📄 Improved Overview Suggestion:`);
    console.log(`   "${aiAnalysis.overviewImprovements.rewriteSuggestion}"`);
  }

  if (aiAnalysis.portfolioRecommendations?.gaps) {
    console.log(`\n💼 Portfolio Gaps (rating: ${aiAnalysis.portfolioRecommendations.currentRating}/100):`);
    aiAnalysis.portfolioRecommendations.gaps.forEach(gap => console.log(`   • ${gap}`));
  }

  if (aiAnalysis.portfolioRecommendations?.recommendedProjects) {
    console.log(`\n💼 Suggested Portfolio Projects:`);
    aiAnalysis.portfolioRecommendations.recommendedProjects.forEach(proj => console.log(`   • ${proj}`));
  }

  if (aiAnalysis.strategy) {
    console.log(`\n🎯 Strategy:`);
    if (aiAnalysis.strategy.targetClients) {
      console.log(`   Target Clients: ${aiAnalysis.strategy.targetClients.join(', ')}`);
    }
    if (aiAnalysis.strategy.recommendedNiche) {
      console.log(`   Recommended Niche: ${aiAnalysis.strategy.recommendedNiche}`);
    }
    if (aiAnalysis.strategy.pricingStrategy) {
      console.log(`   Pricing: ${aiAnalysis.strategy.pricingStrategy}`);
    }
  }

  if (aiAnalysis.strategy?.nextSteps) {
    console.log(`\n🚀 Next Steps:`);
    aiAnalysis.strategy.nextSteps.forEach((step, i) => console.log(`   ${i + 1}. ${step}`));
  }

  console.log('\n' + '='.repeat(60) + '\n');
  console.log('✅ Analysis complete! Check profile-analysis.json for full details.\n');

  process.exit(0);
}

main().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});
