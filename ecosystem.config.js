// PM2 Ecosystem Configuration
// Deploy: pm2 start ecosystem.config.js
// View logs: pm2 logs
// Monitor: pm2 monit

module.exports = {
  apps: [
    {
      name: 'upwork-job-checker',
      script: './crawler/ai-job-checker.js',
      interpreter: 'node',
      interpreter_args: '--experimental-modules',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      },
      error_file: './logs/err.log',
      out_file: './logs/out.log',
      log_file: './logs/combined.log',
      time: true,
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
      merge_logs: true,
      cron_restart: '0 */6 * * *', // Restart every 6 hours
      restart_delay: 4000,
      exp_backoff_restart_delay: 100,
      min_uptime: 10000,
      max_restarts: 10,
      listen_timeout: 3000,
      kill_with_signal: 'SIGKILL',
      wait_ready: false,
      shutdown_with_message: true
    }
  ]
};
