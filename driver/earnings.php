<?php
$pageTitle = 'Earnings';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en-GB" id="html-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | No 5 Tyre Driver</title>
  <link rel="manifest" href="manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <link rel="apple-touch-icon" href="../logo.php">
  <script>
    (function() {
      var s = localStorage.getItem('driver-theme');
      var theme = s === 'light' || s === 'dark' ? s : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      document.documentElement.setAttribute('data-theme', theme);
    })();
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00', primary: '#2563eb' } } } }</script>
  <style>
    [data-theme="light"] { --app-bg: #f4f4f5; --app-surface: #ffffff; --app-border: #e4e4e7; --app-text: #18181b; --app-text-muted: #71717a; --app-accent: #2563eb; }
    [data-theme="dark"] { --app-bg: #09090b; --app-surface: #18181b; --app-border: #3f3f46; --app-text: #fafafa; --app-text-muted: #a1a1aa; --app-accent: #3b82f6; }
    body { background: var(--app-bg); color: var(--app-text); }
    .app-surface { background-color: var(--app-surface) !important; }
    .app-border { border-color: var(--app-border) !important; }
    .app-text { color: var(--app-text) !important; }
    .app-text-muted { color: var(--app-text-muted) !important; }
    .safe-area-pb { padding-bottom: env(safe-area-inset-bottom, 0); }
  </style>
</head>
<body class="antialiased min-h-screen transition-colors duration-200">
  <header class="sticky top-0 z-40 app-surface border-b app-border">
    <div class="max-w-2xl mx-auto px-4 py-5">
      <div class="flex items-center justify-between">
        <a href="dashboard.php" class="text-sm app-text-muted hover:app-text">← Back</a>
        <a href="profile.php" class="p-2 rounded-full app-border border app-text-muted hover:app-text" aria-label="Profile">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </a>
      </div>
      <h1 class="text-2xl font-bold app-text mt-2">Wallet earned</h1>
      <p class="app-text-muted text-sm mt-0.5">Total from completed jobs</p>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-6 pb-24">
    <div class="rounded-2xl app-surface border app-border p-6">
      <p class="app-text-muted text-sm">Your earnings</p>
      <p id="wallet-amount" class="text-3xl font-bold mt-1" style="color: var(--app-accent);">£0.00</p>
      <p class="app-text-muted text-xs mt-3">Earnings are calculated from completed card payments and cash jobs. Payouts are processed via Stripe Connect.</p>
    </div>
    <p id="earnings-loading" class="app-text-muted py-4 text-center text-sm">Loading…</p>
  </main>

  <!-- Bottom navigation (same as dashboard) -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 app-surface border-t app-border safe-area-pb">
    <div class="max-w-2xl mx-auto flex items-center justify-around h-16 px-2">
      <a href="dashboard.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:opacity-80 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="text-xs">Home</span>
      </a>
      <a href="earnings.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 font-medium" style="color: var(--app-accent);">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-xs">Earnings</span>
      </a>
      <a href="inbox.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:app-text transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
        <span class="text-xs">Inbox</span>
      </a>
      <a href="dashboard.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:app-text transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span class="text-xs">Menu</span>
      </a>
    </div>
  </nav>

  <script>
  (function() {
    var API_BASE = (function() {
      var p = window.location.pathname;
      return p.replace(/[^/]+$/, '');
    })();
    fetch(API_BASE + 'api/jobs.php', { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        document.getElementById('earnings-loading').classList.add('hidden');
        var wallet = (d.driver && d.driver.wallet_earned) ? d.driver.wallet_earned : 0;
        document.getElementById('wallet-amount').textContent = '£' + Number(wallet).toFixed(2);
      })
      .catch(function() {
        document.getElementById('earnings-loading').textContent = 'Failed to load.';
      });
  })();
  </script>
</body>
</html>
