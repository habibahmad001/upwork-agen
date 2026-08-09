/**
 * Test Pusher Notification from Node.js
 *
 * Usage: node test-pusher.js
 */

import { default as Pusher } from 'pusher';

console.log('📡 Testing Pusher Notification...\n');

const pusher = new Pusher({
  appId: '1695089',
  key: '4d2cd7d38e091601e28c',
  secret: '35d959b307a0e508a7b9',
  cluster: 'ap2',
  useTLS: true
});

const testData = {
  type: 'test',
  message: 'Pusher is working from Node.js!',
  timestamp: new Date().toISOString()
};

pusher.trigger('jobs', 'test', testData)
  .then(() => {
    console.log('✅ Test notification sent successfully!');
    console.log('📡 Channel: jobs');
    console.log('🎯 Event: test');
    console.log('\nCheck your dashboard: http://127.0.0.1:8000/pusher-test');
  })
  .catch(err => {
    console.error('❌ Failed to send test notification:', err.message);
  });
