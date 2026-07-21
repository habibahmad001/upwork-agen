/**
 * Manual Cookie Export Helper
 *
 * Run this in your browser console after logging into Upwork
 * Then save the output to storage.json
 */

(function() {
  const cookies = document.cookie.split(';').map(c => {
    const [name, ...rest] = c.split('=');
    return {
      name: name.trim(),
      value: rest.join('=').trim(),
      domain: '.upwork.com',
      path: '/',
      secure: true,
      httpOnly: false,
    };
  });

  const localStorageData = {};
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    localStorageData[key] = localStorage.getItem(key);
  }

  const storage = {
    timestamp: new Date().toISOString(),
    url: window.location.href,
    cookies: cookies,
    localStorage: localStorageData,
  };

  console.log('=== UPWORK SESSION DATA ===');
  console.log(JSON.stringify(storage, null, 2));

  // Copy to clipboard
  copy(JSON.stringify(storage, null, 2));
  console.log('\n✅ Data copied to clipboard!');
  console.log('📋 Paste this into: crawler/playwright/storage.json');
})();
