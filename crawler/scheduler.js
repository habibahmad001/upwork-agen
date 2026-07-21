/**
 * Job Checker Scheduler
 *
 * Runs the job checker every minute and sends email notifications.
 * Usage: node scheduler.js
 */

import { spawn } from 'child_process';
import { readFileSync, writeFileSync } from 'fs';
import { resolve } from 'path';

// Configuration
const CHECK_INTERVAL = 60 * 1000; // 1 minute
const LOG_FILE = resolve('./scheduler.log');

console.log('🕐 Upwork Job Checker Scheduler');
console.log('================================');
console.log(`Check interval: ${CHECK_INTERVAL / 1000} seconds`);
console.log(`Log file: ${LOG_FILE}`);
console.log('');
console.log('Press Ctrl+C to stop\n');

/**
 * Log message to file and console
 */
function log(message) {
  const timestamp = new Date().toISOString();
  const logMessage = `[${timestamp}] ${message}`;

  console.log(logMessage);
  writeFileSync(LOG_FILE, logMessage + '\n', { flag: 'a' });
}

/**
 * Run the job checker
 */
function runChecker() {
  return new Promise((resolve, reject) => {
    log('🔍 Starting job check...');

    const checker = spawn('node', ['job-checker.js'], {
      cwd: resolve('./crawler'),
      stdio: 'pipe'
    });

    let output = '';
    let errorOutput = '';

    checker.stdout.on('data', (data) => {
      output += data.toString();
      process.stdout.write(data);
    });

    checker.stderr.on('data', (data) => {
      errorOutput += data.toString();
      process.stderr.write(data);
    });

    checker.on('close', (code) => {
      if (code === 0) {
        log('✅ Job check completed successfully');
        resolve();
      } else {
        log(`❌ Job check failed with code ${code}`);
        if (errorOutput) {
          log(`Error: ${errorOutput}`);
        }
        reject(new Error(`Exit code ${code}`));
      }
    });

    checker.on('error', (err) => {
      log(`❌ Failed to spawn checker: ${err.message}`);
      reject(err);
    });
  });
}

/**
 * Main scheduler loop
 */
async function startScheduler() {
  log('🚀 Scheduler started');

  // Run immediately on start
  try {
    await runChecker();
  } catch (err) {
    log(`⚠️  Initial check failed: ${err.message}`);
  }

  // Schedule recurring checks
  setInterval(async () => {
    try {
      await runChecker();
    } catch (err) {
      log(`⚠️  Check failed: ${err.message}`);
    }
  }, CHECK_INTERVAL);
}

/**
 * Handle graceful shutdown
 */
process.on('SIGINT', () => {
  log('\n👋 Scheduler stopped by user');
  process.exit(0);
});

process.on('SIGTERM', () => {
  log('\n👋 Scheduler terminated');
  process.exit(0);
});

// Start the scheduler
startScheduler().catch(err => {
  log(`❌ Scheduler failed to start: ${err.message}`);
  process.exit(1);
});
