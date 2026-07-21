# Upwork Job Agent

Automated job checker that monitors Upwork and sends email notifications for new jobs.

## Quick Setup

### 1. Setup Cookies (One Time)

1. Open https://www.upwork.com in your browser (logged in)
2. Click EditThisCookie extension
3. Click "Export" → "JSON"
4. Run this command and paste the JSON:

```bash
cd crawler
node setup-cookies.js
```

Paste the exported cookies and type DONE when finished.

### 2. Run Automated Job Checker

```bash
cd crawler
node persistent-checker.js
```

What happens:
- Browser opens with your cookies loaded
- Navigate to Upwork jobs page
- Complete any Cloudflare challenge (once)
- Wait for job listings to appear
- Press Enter in terminal
- Auto-checks every 60 seconds for new jobs!

## Configuration

Edit `crawler/config.json` to change:
- `jobsUrl`: Which jobs page to monitor
  - Most recent: https://www.upwork.com/nx/find-work/most-recent
  - Best matches: https://www.upwork.com/nx/find-work/best-matches
  - Custom search: https://www.upwork.com/nx/find-work/search/?q=your-keyword

## Files

- `crawler/persistent-checker.js` - Main automated checker
- `crawler/setup-cookies.js` - Cookie import tool
- `crawler/config.json` - Configuration
- `crawler/jobs.json` - Latest job data
- `crawler/known-jobs.json` - Tracks seen jobs

## Troubleshooting

**If cookies expire:**
1. Re-export from EditThisCookie
2. Run `node setup-cookies.js` again

**If Cloudflare appears:**
- Complete the challenge once in the browser
- The persistent session will handle future requests

## Status

✅ Cookie import tool created
✅ Persistent browser checker
✅ Auto-checks every 60 seconds
✅ Detects new jobs
✅ Email notifications (ready to configure)
