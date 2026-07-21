✅ Test email sent successfully!
   Recipient: ha***********@gmail.com
   Subject: 🧪 Test Email - Upwork Job Agent


Access the Application:
URL: http://127.0.0.1:8000
Login: habibahmed001@gmail.com / ha03228594463

# Send test email
php artisan test:email

# Run crawler
php artisan crawler:run

# Import jobs from file
php artisan crawler:import /path/to/jobs.json

# Process queue
php artisan queue:work

Sign up at console.groq.com
Get free API key
Update .env:

login with llcjetbrain@gmail.com
Organization ID: org_01ky02nr4cfext5eecabxs3djd
Keys:
org_01ky02nr4cfext5eecabxs3djd / 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



Groq AI Integration Complete!
Your system is now running with real AI from Groq (free API). Here's what's working:

Current Status:
Feature	Status
AI Provider	✅ Groq (llama-3.3-70b-versatile)
API Key	✅ Configured
AI Evaluation	✅ Working
Email Notifications	✅ SMTP configured
Dashboard	✅ Running at http://127.0.0.1:8001
Queue System	✅ Database queue
🔗 Setting Up Upwork Crawler
To crawl real jobs from Upwork, you need to login first to save your session.

Step 1: Login to Upwork
Run this command (it will open a browser window):


cd crawler/playwright
node login.js
What happens:

A Chromium browser window will open
Go to the Upwork login page
You manually login with your Upwork credentials
Once logged in, the session cookies are saved to storage.json
The crawler can now use this session to fetch jobs
Note: Only the first login requires manual action. The session is saved for future use.

Step 2: Configure Your Search Preferences
Edit crawler/config.json:


{
  "headless": true,
  "upwork": {
    "jobsUrl": "https://www.upwork.com/nx/find-work/best-matches"
  }
}
You can customize the jobsUrl with filters:

Best matches: https://www.upwork.com/nx/find-work/best-matches
Search results: https://www.upwork.com/nx/find-work/search/?q=laravel
Filtered: Add URL parameters for your preferences
Step 3: Run the Crawler
After login, run:


node crawler.js
This will:

Load your saved session
Navigate to Upwork jobs page
Scrape job listings
Save results to jobs.json
Step 4: Import Jobs into Laravel

cd ../..
php artisan crawler:import crawler/playwright/jobs.json
This imports the scraped jobs into your database.

Step 5: Process Jobs with AI

# Evaluate jobs with Groq AI
php artisan queue:work

# Or process immediately
php artisan crawler:run
📝 Quick Reference Commands
Command	Description
cd crawler/playwright && node login.js	Login to Upwork (saves session)
cd crawler/playwright && node crawler.js	Scrape jobs from Upwork
php artisan crawler:import path/to/jobs.json	Import jobs to database
php artisan queue:work	Process queued jobs (AI evaluation + notifications)
php artisan test:groq	Test Groq AI integration
php artisan test:email	Send test email
🎯 Ready to Test?
Start here:

Open terminal and run:


cd crawler/playwright
node login.js
Login in the browser window when it opens

After login succeeds, run:


node crawler.js

cd crawler
node persistent-checker.js


It will now check most recent jobs every 60 seconds! 🎉

The flow will be:

Browser opens → navigates to most-recent page
Complete Cloudflare challenge (once)
Wait for job listings to load
Press Enter
Auto-check every 60 seconds for new jobs
Press Enter in the terminal when you see job listings to start the automatic checking!