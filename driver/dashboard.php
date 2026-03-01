<?php
$pageTitle = 'My jobs';
require_once __DIR__ . '/auth.php';
$driver = getDriverById($_SESSION[DRIVER_SESSION_KEY]);
?>
<!DOCTYPE html>
<html lang="en-GB" id="html-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | No 5 Tyre Driver</title>
  <link rel="manifest" href="manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="No5 Driver">
  <link rel="apple-touch-icon" href="../logo.php">
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('../sw.js', { scope: '/' }).catch(function() {});
    }
    (function() {
      var s = localStorage.getItem('driver-theme');
      var theme = s === 'light' || s === 'dark' ? s : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      document.documentElement.setAttribute('data-theme', theme);
      document.querySelector('meta[name="theme-color"]').setAttribute('content', theme === 'light' ? '#ffffff' : '#18181b');
    })();
  </script>
  <meta name="theme-color" content="#18181b">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00', primary: '#2563eb' } } } }</script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <style>
    [data-theme="light"] { --app-bg: #f4f4f5; --app-surface: #ffffff; --app-border: #e4e4e7; --app-text: #18181b; --app-text-muted: #71717a; --app-accent: #2563eb; --app-accent-hover: #1d4ed8; --app-online: #16a34a; --app-map-bg: #e4e4e7; }
    [data-theme="dark"] { --app-bg: #09090b; --app-surface: #18181b; --app-border: #3f3f46; --app-text: #fafafa; --app-text-muted: #a1a1aa; --app-accent: #3b82f6; --app-accent-hover: #2563eb; --app-online: #22c55e; --app-map-bg: #27272a; }
    body { background: var(--app-bg); color: var(--app-text); }
    .app-surface { background-color: var(--app-surface) !important; }
    .app-border { border-color: var(--app-border) !important; }
    .app-text { color: var(--app-text) !important; }
    .app-text-muted { color: var(--app-text-muted) !important; }
    .app-accent { color: var(--app-accent) !important; }
    .app-map-bg { background-color: var(--app-map-bg) !important; }
    .safe-area-pb { padding-bottom: env(safe-area-inset-bottom, 0); }
    @keyframes slide-up { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .animate-slide-up { animation: slide-up 0.3s ease-out; }
  </style>
</head>
<body class="antialiased min-h-screen transition-colors duration-200">
  <div id="pwa-install-banner" class="hidden fixed bottom-20 left-4 right-4 z-50 max-w-2xl mx-auto px-4 py-3 rounded-xl app-surface border app-border shadow-lg flex items-center justify-between gap-4">
    <p class="text-sm app-text-muted">Install app for best experience</p>
    <div class="flex gap-2">
      <button type="button" id="pwa-install-btn" class="px-3 py-1.5 rounded-lg bg-safety text-zinc-900 font-semibold text-sm">Install</button>
      <button type="button" id="pwa-install-dismiss" class="px-3 py-1.5 rounded-lg app-text-muted text-sm hover:opacity-80">Later</button>
    </div>
  </div>

  <!-- Blocked driver banner -->
  <div id="blocked-banner" class="hidden bg-red-900/90 border-b border-red-700 text-white px-4 py-3">
    <p class="font-semibold">Your account has been blocked.</p>
    <p id="blocked-reason-text" class="text-sm text-red-200 mt-0.5"></p>
    <p class="text-xs text-red-300 mt-1">Contact the office to resolve this.</p>
  </div>

  <!-- App-style header with status -->
  <header class="sticky top-0 z-40 app-surface/95 backdrop-blur border-b app-border">
    <div class="max-w-2xl mx-auto px-4 py-5">
      <div class="flex items-start justify-between">
        <div>
          <h1 id="status-heading" class="text-2xl font-bold app-text">You're offline</h1>
          <p id="status-sub" class="app-text-muted text-sm mt-0.5">Ready to go?</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="profile.php" class="p-2.5 rounded-full app-border border app-text-muted hover:app-text transition-colors" aria-label="Profile">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </a>
          <button type="button" id="btn-menu" class="p-2.5 rounded-full app-border border app-text-muted hover:app-text transition-colors" aria-label="Menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
        </div>
      </div>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-4 pb-36">
    <!-- Map (shown only when driver has jobs) -->
    <div id="map-container" class="relative rounded-2xl mb-4 shadow-lg hidden" style="height: 280px; width: 100%;">
      <div id="map" style="width: 100% !important; height: 280px !important; min-height: 280px !important; position: relative; background: var(--app-map-bg, #e4e4e7);"></div>
      <button type="button" id="btn-map-expand" class="absolute top-2 right-2 z-[400] w-9 h-9 rounded-full app-surface border app-border shadow flex items-center justify-center app-text-muted hover:opacity-80" aria-label="Expand map">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
      </button>
    </div>

    <!-- Opportunities (shown only when driver has jobs) -->
    <div id="opportunities-section" class="rounded-2xl app-surface border app-border p-4 mb-4 flex items-center justify-between gap-4 hidden">
      <div class="flex-1 min-w-0">
        <h2 class="font-semibold app-text text-base flex items-center gap-2">
          Opportunities
          <svg class="w-4 h-4 app-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </h2>
        <p id="opportunities-text" class="app-text-muted text-sm mt-1">Loading…</p>
      </div>
      <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 border app-border flex items-center justify-center app-map-bg">
        <svg class="w-8 h-8 app-text-muted opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      </div>
    </div>

    <!-- Go online / Go offline button -->
    <button type="button" id="btn-online" class="w-full py-4 px-6 rounded-2xl font-bold text-base flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-lg" style="background: var(--app-accent); color: white;" title="Go online">
      <svg id="btn-online-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      <span id="online-label">Go online</span>
    </button>

    <div id="verification-banner" class="hidden rounded-xl border border-amber-500/50 bg-amber-500/10 p-4 mt-4">
      <p class="text-amber-700 dark:text-amber-300 text-sm font-medium">Verify your identity (KYC)</p>
      <p class="text-amber-600/90 dark:text-amber-400/80 text-xs mt-1">Complete payout setup and verify your license/ID before you can start jobs.</p>
      <div class="flex flex-wrap gap-2 mt-2">
        <button type="button" id="btn-verify-identity" class="px-3 py-1.5 rounded-lg bg-amber-600 text-white font-medium text-sm">Verify license/ID</button>
        <a href="onboarding.html" class="px-3 py-1.5 rounded-lg bg-zinc-600 text-zinc-200 text-sm">Complete payout setup</a>
      </div>
    </div>

    <div id="jobs-loading" class="app-text-muted py-8 text-center hidden">Loading jobs…</div>
    <div id="jobs-list" class="space-y-4 hidden mt-6 pb-8"></div>
    <div id="jobs-empty" class="hidden text-center py-12 app-text-muted">
      <p class="text-lg">No jobs assigned yet.</p>
      <p class="text-sm mt-2">Jobs will appear here when assigned by the office.</p>
      <button type="button" id="btn-get-location-empty" class="mt-6 px-6 py-3 rounded-xl bg-safety text-zinc-900 font-bold text-sm hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety flex items-center justify-center gap-2 mx-auto">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        Update my location
      </button>
    </div>
  </main>

  <!-- Bottom navigation -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 app-surface border-t app-border safe-area-pb">
    <div class="max-w-2xl mx-auto flex items-center justify-around h-16 px-2">
      <a href="dashboard.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 font-medium" style="color: var(--app-accent);">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="text-xs">Home</span>
      </a>
      <a href="earnings.php" id="nav-earnings" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:opacity-80 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-xs">Earnings</span>
      </a>
      <a href="inbox.php" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:app-text transition-colors relative">
        <span class="relative">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
          <span id="inbox-badge" class="hidden absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center z-10 leading-none">0</span>
        </span>
        <span class="text-xs">Inbox</span>
      </a>
      <button type="button" id="nav-menu" class="flex flex-col items-center justify-center gap-1 flex-1 py-2 app-text-muted hover:app-text transition-colors bg-transparent border-none cursor-pointer">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span class="text-xs">Menu</span>
      </button>
    </div>
  </nav>

  <!-- Menu sheet -->
  <div id="menu-sheet" class="fixed inset-0 z-50 hidden" style="display: none;">
    <div id="menu-backdrop" class="absolute inset-0 bg-black/40" onclick="document.getElementById('menu-sheet').classList.add('hidden'); document.getElementById('menu-sheet').style.display='none';"></div>
    <div class="absolute bottom-0 left-0 right-0 rounded-t-3xl app-surface border-t app-border max-h-[70vh] overflow-y-auto animate-slide-up">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold app-text">Menu</h3>
          <button type="button" onclick="document.getElementById('menu-sheet').classList.add('hidden'); document.getElementById('menu-sheet').style.display='none';" class="p-2 -m-2 app-text-muted hover:app-text">×</button>
        </div>
        <div class="space-y-2">
          <a href="earnings.php" class="flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Earnings
          </a>
          <a href="inbox.php" class="flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
            Inbox
          </a>
          <a href="profile.php" class="flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Profile
          </a>
          <button type="button" id="menu-btn-location" class="w-full flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90 text-left">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            Update my location
          </button>
          <button type="button" id="menu-btn-ref" class="w-full flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90 text-left">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
            Enter reference
          </button>
          <button type="button" id="menu-btn-theme" class="w-full flex items-center gap-3 p-3 rounded-xl app-border border app-text hover:opacity-90 text-left">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span id="theme-label">Switch to light mode</span>
          </button>
          <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl border border-red-500/30 text-red-500 hover:bg-red-500/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Reference entry modal -->
  <div id="ref-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
    <div class="w-full max-w-lg rounded-2xl app-surface border app-border p-6 my-4">
      <div class="text-center mb-6">
        <h3 class="text-xl font-bold text-white mb-1">Enter job reference</h3>
        <p class="text-zinc-500 text-sm">Enter the 4–6 digit reference number from the job receipt</p>
      </div>
      <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-4 mb-4">
        <form id="ref-form" class="flex gap-3">
          <div class="flex-1 relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 font-mono text-lg">#</span>
            <input type="text" id="ref-input" placeholder="123456" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full pl-8 pr-4 py-3 rounded-xl bg-zinc-700/80 border-2 border-zinc-600 text-white font-mono text-xl placeholder-zinc-600 focus:border-safety focus:ring-2 focus:ring-safety/20 focus:outline-none transition-colors">
          </div>
          <button type="submit" class="px-5 py-3 bg-safety text-zinc-900 font-bold rounded-xl hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-800 shrink-0 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            View
          </button>
        </form>
      </div>
      <p id="ref-status" class="text-center text-sm py-3 rounded-lg hidden mb-4"></p>
      <button type="button" id="ref-close" class="w-full px-4 py-2 border border-zinc-600 text-zinc-300 rounded-lg text-sm hover:bg-zinc-700">Close</button>
    </div>
  </div>

  <!-- Google Review QR modal -->
  <div id="review-qr-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
    <div class="w-full max-w-sm rounded-2xl app-surface border app-border p-6 my-4">
      <h3 class="text-lg font-bold text-white mb-1">Leave a review</h3>
      <p class="text-zinc-500 text-sm mb-4">Scan to leave a Google review</p>
      <div class="bg-white rounded-xl p-4 mb-4 flex justify-center">
        <img id="review-qr-img" src="" alt="QR code" class="w-48 h-48 object-contain">
      </div>
      <a id="review-qr-link" href="#" target="_blank" rel="noopener" class="block text-center text-safety text-sm font-medium hover:underline mb-4">Or open review link</a>
      <button type="button" id="review-qr-close" class="w-full px-4 py-2 border border-zinc-600 text-zinc-300 rounded-lg text-sm hover:bg-zinc-700">Close</button>
    </div>
  </div>

  <!-- Location modal -->
  <div id="location-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
    <div class="w-full max-w-sm rounded-2xl app-surface border app-border p-6">
      <h3 class="text-lg font-bold text-white mb-4">Update location</h3>
      <p id="location-status" class="text-sm text-zinc-400 mb-4">Getting your position…</p>
      <div class="flex flex-wrap gap-3">
        <button type="button" id="location-confirm" class="flex-1 px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Update</button>
        <button type="button" id="location-retry" class="hidden px-4 py-2 bg-zinc-600 text-zinc-200 rounded-lg text-sm hover:bg-zinc-500">Retry</button>
        <button type="button" id="location-cancel" class="px-4 py-2 border border-zinc-600 rounded-lg text-sm">Cancel</button>
      </div>
    </div>
  </div>

  <script>window.DRIVER_API_BASE = <?php echo json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/driver/'), '/') . '/'); ?>;</script>
  <script>
  (function() {
    var currentLat, currentLng, currentRef;
    var map, driverMarker, jobMarkers = [];
    var driverData = {};

    function initMap() {
      if (map) return;
      var el = document.getElementById('map');
      if (!el) return;
      if (typeof L === 'undefined') {
        setTimeout(initMap, 100);
        return;
      }
      try {
        map = L.map('map', { center: [51.5074, -0.1278], zoom: 10 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap',
          maxZoom: 19
        }).addTo(map);
        requestAnimationFrame(function() {
          if (map) { map.invalidateSize(); map.setView([51.5074, -0.1278], 10); }
        });
      } catch (e) { console.error('Map init:', e); }
    }

    function updateMap(jobs, driver) {
      initMap();
      jobMarkers.forEach(function(m) { map.removeLayer(m); });
      jobMarkers = [];
      var bounds = [];
      if (driver.driver_lat && driver.driver_lng) {
        var lat = parseFloat(driver.driver_lat), lng = parseFloat(driver.driver_lng);
        if (!isNaN(lat) && !isNaN(lng)) {
          if (driverMarker) map.removeLayer(driverMarker);
          driverMarker = L.marker([lat, lng]).addTo(map).bindPopup('Your location');
          driverMarker._icon && driverMarker._icon.classList.add('driver-marker');
          bounds.push([lat, lng]);
        }
      }
      (jobs || []).forEach(function(j) {
        var lat = parseFloat(j.lat), lng = parseFloat(j.lng);
        if (isNaN(lat) || isNaN(lng)) return;
        var m = L.marker([lat, lng]).addTo(map).bindPopup('#' + (j.reference||'') + ' ' + (j.postcode||''));
        jobMarkers.push(m);
        bounds.push([lat, lng]);
      });
      if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
      }
    }

    function setOnlineBtn(online) {
      var btn = document.getElementById('btn-online');
      var lbl = document.getElementById('online-label');
      var icon = document.getElementById('btn-online-icon');
      var heading = document.getElementById('status-heading');
      var sub = document.getElementById('status-sub');
      var baseClass = 'w-full py-4 px-6 rounded-2xl font-bold text-base flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-lg';
      if (online) {
        btn.className = baseClass;
        btn.style.background = 'var(--app-online)';
        btn.style.color = 'white';
        lbl.textContent = 'You\'re online – Tap to go offline';
        btn.title = 'You\'re online. Tap to go offline';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 0v4m0-4V8"/>';
        icon.setAttribute('viewBox', '0 0 24 24');
        if (heading) heading.textContent = "You're online";
        if (sub) sub.textContent = "You're visible to the office";
      } else {
        btn.className = baseClass;
        btn.style.background = 'var(--app-accent)';
        btn.style.color = 'white';
        lbl.textContent = 'Go online';
        btn.title = 'Tap to go online';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>';
        icon.setAttribute('viewBox', '0 0 24 24');
        if (heading) heading.textContent = "You're offline";
        if (sub) sub.textContent = 'Ready to go?';
      }
    }

    function setOpportunitiesText(jobsCount, walletEarned) {
      var el = document.getElementById('opportunities-text');
      if (!el) return;
      if (jobsCount > 0) {
        el.textContent = jobsCount + ' job' + (jobsCount !== 1 ? 's' : '') + ' assigned. Complete them to earn.';
      } else {
        el.textContent = 'No jobs yet. Go online and jobs will appear when assigned.';
      }
    }

    var API_BASE = (typeof window.DRIVER_API_BASE === 'string' && window.DRIVER_API_BASE)
      ? window.DRIVER_API_BASE
      : (function() { var p = window.location.pathname; var d = p.replace(/[^/]+$/, ''); return d.endsWith('/') ? d : d + '/'; })();

    function applyDriverData(d) {
      if (!d) return;
      document.getElementById('jobs-loading').classList.add('hidden');
      driverData = d.driver || {};
      var blockedBanner = document.getElementById('blocked-banner');
      var blockedReason = document.getElementById('blocked-reason-text');
      if (driverData.blacklisted && blockedBanner && blockedReason) {
        blockedBanner.classList.remove('hidden');
        blockedReason.textContent = (driverData.blocked_reason || '').trim() || 'No reason provided.';
      } else if (blockedBanner) {
        blockedBanner.classList.add('hidden');
      }
      var unread = d.unreadMessages != null ? d.unreadMessages : -1;
      if (unread < 0) {
        fetch(API_BASE + 'api/messages.php', { credentials: 'same-origin' })
          .then(function(r) { return r.json(); })
          .then(function(md) {
            var u = ((md.messages || []).filter(function(x) { return !x.read; })).length;
            updateInboxBadge(u);
          })
          .catch(function() {});
      } else {
        updateInboxBadge(unread);
      }
      function updateInboxBadge(u) {
        var badge = document.getElementById('inbox-badge');
        if (badge) {
          if (u > 0) { badge.textContent = u > 99 ? '99+' : String(u); badge.classList.remove('hidden'); }
          else { badge.classList.add('hidden'); }
        }
      }
      var googleReviewUrl = (d.googleReviewUrl || '').trim();
      var jobs = d.jobs || [];
      var verified = driverData.kyc_verified;
          document.getElementById('verification-banner').classList.toggle('hidden', verified);
          setOnlineBtn(driverData.is_online);
          var mapEl = document.getElementById('map-container');
          var oppEl = document.getElementById('opportunities-section');
          if (jobs.length === 0) {
            if (mapEl) mapEl.classList.add('hidden');
            if (oppEl) oppEl.classList.add('hidden');
            setOpportunitiesText(0, driverData.wallet_earned);
            var list = document.getElementById('jobs-list');
            var empty = document.getElementById('jobs-empty');
            list.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
          }
          if (mapEl) mapEl.classList.remove('hidden');
          if (oppEl) oppEl.classList.remove('hidden');
          setOpportunitiesText(jobs.length, driverData.wallet_earned);
          updateMap(jobs, driverData);
          var list = document.getElementById('jobs-list');
          var empty = document.getElementById('jobs-empty');
          empty.classList.add('hidden');
          list.classList.remove('hidden');
          list.innerHTML = jobs.map(function(j) {
            var v = (j.make||'') + ' ' + (j.model||''); if (!v.trim()) v = j.vrm || '—'; else if (j.vrm) v += ' (' + j.vrm + ')';
            var payment = j.payment_method === 'cash' ? '<span class="text-amber-400">Cash</span>' + (j.cash_paid_at ? ' (marked paid)' : '');
            else payment = 'Card (deposit) – balance due: ' + (j.balance_due || '—');
            var proofBtn = j.proof_url ? '<span class="text-green-400 text-xs">Proof uploaded</span>' : '<button type="button" class="proof-btn px-2 py-1 rounded bg-zinc-700 text-xs" data-ref="' + j.reference + '">Upload proof</button>';
            var canStart = verified;
            var startBtn = j.job_started_at ? '<span class="text-green-400 text-xs">Started</span>' : (canStart ? '<button type="button" class="start-btn px-2 py-1 rounded bg-safety text-zinc-900 text-xs font-medium" data-ref="' + (j.reference||'') + '">Start job</button>' : '<span class="text-amber-400 text-xs">Verify first</span>');
            var jobDone = !!(j.proof_url || j.cash_paid_at || j.job_completed_at);
            var reviewBtn = (jobDone && googleReviewUrl) ? '<button type="button" class="review-btn px-2 py-1 rounded bg-safety/20 text-safety text-xs font-medium" data-ref="' + (j.reference||'') + '">Leave review</button>' : '';
            var phone = (j.phone || '').trim();
            var phoneLink = phone ? '<a href="tel:' + escapeHtml(phone.replace(/\D/g,'')) + '" class="text-safety hover:underline">' + escapeHtml(phone) + '</a>' : '';
            var custLine = escapeHtml(j.name||j.email||'') + (phoneLink ? ' · ' + phoneLink : '');
            var tyreWheels = [j.tyre_size, j.wheels ? j.wheels + ' wheels' : ''].filter(Boolean).join(' · ');
            var mapLink = (j.lat && j.lng) ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(j.lat + ',' + j.lng) + '" target="_blank" rel="noopener" class="text-safety text-xs hover:underline">Directions</a>' : '';
            return '<div class="rounded-xl app-surface border app-border p-4" data-ref="' + (j.reference||'') + '">' +
              '<div class="flex justify-between items-start mb-2">' +
                '<span class="font-mono font-bold text-safety">#' + (j.reference||'') + '</span>' +
                '<span class="text-zinc-500 text-sm">' + escapeHtml(j.date||j.postcode||'') + '</span>' +
              '</div>' +
              '<p class="text-white font-medium">' + escapeHtml(v) + (j.colour ? ' · ' + escapeHtml(j.colour) : '') + '</p>' +
              '<p class="text-zinc-400 text-sm mt-1">' + escapeHtml(j.postcode||'') + '</p>' +
              '<p class="text-zinc-400 text-sm mt-0.5">' + custLine + '</p>' +
              (tyreWheels ? '<p class="text-zinc-500 text-xs mt-1">' + escapeHtml(tyreWheels) + '</p>' : '') +
              '<p class="text-zinc-500 text-xs mt-2">' + payment + '</p>' +
              (mapLink ? '<p class="mt-1">' + mapLink + '</p>' : '') +
              '<div class="flex flex-wrap items-center gap-2 mt-3">' +
                startBtn +
                '<button type="button" class="loc-btn px-2 py-1 rounded bg-zinc-700 text-xs" data-ref="' + (j.reference||'') + '">Update location</button>' +
                proofBtn +
                (j.payment_method !== 'cash' ? '<button type="button" class="cash-btn px-2 py-1 rounded bg-amber-900/50 text-amber-300 text-xs" data-ref="' + (j.reference||'') + '">Mark cash paid</button>' : '') +
                reviewBtn +
              '</div>' +
            '</div>';
          }).join('');
          list.querySelectorAll('.start-btn').forEach(function(b) {
            b.addEventListener('click', function() {
              var ref = b.getAttribute('data-ref');
              var fd = new FormData();
              fd.append('action', 'job_start');
              fd.append('reference', ref);
              fetch(API_BASE + 'api/jobs.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.ok) loadJobs(); else alert(d.error || 'Failed');
              });
            });
          });
          list.querySelectorAll('.loc-btn').forEach(function(b) {
            b.addEventListener('click', function() { openLocationModal(b.getAttribute('data-ref')); });
          });
          list.querySelectorAll('.proof-btn').forEach(function(b) {
            if (b.classList.contains('proof-btn')) {
              b.addEventListener('click', function() { uploadProof(b.getAttribute('data-ref')); });
            }
          });
          list.querySelectorAll('.cash-btn').forEach(function(b) {
            b.addEventListener('click', function() {
              if (confirm('Mark this job as paid in cash?')) markCashPaid(b.getAttribute('data-ref'));
            });
          });
          list.querySelectorAll('.review-btn').forEach(function(b) {
            b.addEventListener('click', function() { openReviewModal(googleReviewUrl); });
          });
    }

    function loadJobs() {
      var loadingEl = document.getElementById('jobs-loading');
      var emptyEl = document.getElementById('jobs-empty');
      var listEl = document.getElementById('jobs-list');
      var mapEl = document.getElementById('map-container');
      var oppSection = document.getElementById('opportunities-section');
      var oppTextEl = document.getElementById('opportunities-text');
      loadingEl.classList.remove('hidden');
      function showError() {
        loadingEl.classList.add('hidden');
        listEl.classList.add('hidden');
        if (mapEl) mapEl.classList.add('hidden');
        if (oppSection) oppSection.classList.add('hidden');
        if (emptyEl) {
          emptyEl.classList.remove('hidden');
          emptyEl.innerHTML = '<p class="text-lg">Could not load jobs</p><p class="text-sm mt-2">Check your connection and refresh. Make sure you are logged in.</p><button type="button" onclick="location.reload()" class="mt-4 px-4 py-2 rounded-xl bg-safety text-zinc-900 font-medium text-sm">Refresh</button>';
        }
        if (oppTextEl) oppTextEl.textContent = 'Pull down to refresh.';
      }
      var controller = new AbortController();
      var timeoutId = setTimeout(function() { controller.abort(); }, 15000);
      fetch(API_BASE + 'api/jobs.php', { credentials: 'same-origin', signal: controller.signal })
        .then(function(r) {
          clearTimeout(timeoutId);
          if (!r.ok) throw new Error('Server error ' + r.status);
          return r.json();
        })
        .then(function(d) {
          clearTimeout(timeoutId);
          if (d && typeof d === 'object') applyDriverData(d);
          else showError();
        })
        .catch(function(err) {
          clearTimeout(timeoutId);
          console.error('loadJobs:', err);
          showError();
        });
    }

    function escapeHtml(s) {
      if (!s) return '';
      var d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    function openLocationModal(ref) {
      currentRef = ref;
      currentLat = null;
      currentLng = null;
      var statusEl = document.getElementById('location-status');
      var retryBtn = document.getElementById('location-retry');
      var confirmBtn = document.getElementById('location-confirm');
      document.getElementById('location-modal').classList.remove('hidden');
      document.getElementById('location-modal').style.display = 'flex';
      statusEl.textContent = 'Getting your position…';
      if (retryBtn) retryBtn.classList.add('hidden');
      confirmBtn.disabled = true;
      function onSuccess(p) {
        currentLat = p.coords.latitude;
        currentLng = p.coords.longitude;
        statusEl.textContent = 'Location ready. Tap Update to save.';
        if (retryBtn) retryBtn.classList.add('hidden');
        confirmBtn.disabled = false;
      }
      function onError(err) {
        var msg = 'Could not get location. ';
        if (err.code === 1) msg += 'Allow location in browser/phone settings.';
        else if (err.code === 2) msg += 'Position unavailable. Try outdoors or near a window.';
        else if (err.code === 3) msg += 'Timed out. Tap Retry.';
        statusEl.textContent = msg;
        if (retryBtn) { retryBtn.classList.remove('hidden'); retryBtn.onclick = function() { openLocationModal(ref); }; }
      }
      if (!window.isSecureContext) {
        statusEl.textContent = 'Location requires HTTPS. Open via https:// to use GPS.';
        return;
      }
      if (!navigator.geolocation) {
        statusEl.textContent = 'Geolocation not supported by this device.';
        return;
      }
      function tryGetPosition(highAccuracy) {
        var opts = { enableHighAccuracy: highAccuracy, timeout: 20000, maximumAge: highAccuracy ? 0 : 60000 };
        navigator.geolocation.getCurrentPosition(onSuccess, function(err) {
          if (highAccuracy) tryGetPosition(false);
          else onError(err);
        }, opts);
      }
      tryGetPosition(true);
    }

    function openMenu() {
      var sheet = document.getElementById('menu-sheet');
      sheet.classList.remove('hidden');
      sheet.style.display = 'block';
    }
    function closeMenu() {
      var sheet = document.getElementById('menu-sheet');
      sheet.classList.add('hidden');
      sheet.style.display = 'none';
    }
    document.getElementById('btn-menu').addEventListener('click', openMenu);
    document.getElementById('nav-menu').addEventListener('click', openMenu);
    document.getElementById('menu-btn-location').addEventListener('click', function() { closeMenu(); currentRef = null; openLocationModal(null); });
    var btnGetLocEmpty = document.getElementById('btn-get-location-empty');
    if (btnGetLocEmpty) btnGetLocEmpty.addEventListener('click', function() { openLocationModal(null); });
    document.getElementById('menu-btn-ref').addEventListener('click', function() { closeMenu(); openRefModal(); });
    document.getElementById('menu-btn-theme').addEventListener('click', function() {
      var html = document.documentElement;
      var theme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      html.setAttribute('data-theme', theme);
      localStorage.setItem('driver-theme', theme);
      document.querySelector('meta[name="theme-color"]').setAttribute('content', theme === 'light' ? '#ffffff' : '#18181b');
      document.getElementById('theme-label').textContent = theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode';
    });
    var html = document.documentElement;
    document.getElementById('theme-label').textContent = (html.getAttribute('data-theme') === 'light' ? 'Switch to dark mode' : 'Switch to light mode');


    document.getElementById('btn-verify-identity').addEventListener('click', function() {
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Loading…';
      fetch(API_BASE + 'api/verification-session.php', { method: 'POST', credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.url) window.location.href = d.url;
          else { alert(d.error || 'Failed'); btn.disabled = false; btn.textContent = 'Verify license/ID'; }
        })
        .catch(function() { alert('Network error'); btn.disabled = false; btn.textContent = 'Verify license/ID'; });
    });

    document.getElementById('btn-online').addEventListener('click', function() {
      var btn = this;
      var wantOnline = !driverData.is_online;
      var prevOnline = driverData.is_online;
      btn.disabled = true;
      driverData.is_online = wantOnline;
      setOnlineBtn(wantOnline);
      var fd = new FormData();
      fd.append('action', 'set_online');
      fd.append('online', wantOnline ? '1' : '0');
      fetch(API_BASE + 'api/jobs.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      }).then(function(r) {
        if (!r.ok) return r.json().catch(function() { return r.text(); }).then(function(t) {
          throw new Error(typeof t === 'object' && t.error ? t.error : (t || 'Request failed'));
        });
        return r.json();
      }).then(function(d) {
        if (d.ok) {
          driverData.is_online = d.is_online;
          setOnlineBtn(d.is_online);
        } else throw new Error(d.error || 'Failed');
      }).catch(function(err) {
        driverData.is_online = prevOnline;
        setOnlineBtn(prevOnline);
        alert(err.message || 'Could not update. Check connection and try again.');
      }).finally(function() { btn.disabled = false; });
    });

    document.getElementById('location-confirm').addEventListener('click', function() {
      if (currentLat == null || currentLng == null || isNaN(currentLat) || isNaN(currentLng)) {
        alert('Location not available yet. Wait for GPS or check permissions.');
        return;
      }
      var btn = document.getElementById('location-confirm');
      btn.disabled = true;
      var done = function() { btn.disabled = false; };
      if (currentRef) {
        var fd = new FormData();
        fd.append('action', 'location');
        fd.append('lat', String(currentLat));
        fd.append('lng', String(currentLng));
        fd.append('reference', currentRef);
        fetch(API_BASE + 'api/jobs.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); loadJobs(); } else alert(d.error || 'Failed');
        }).catch(function() { alert('Update failed. Check connection.'); }).finally(done);
      } else {
        var fd = new FormData();
        fd.append('lat', String(currentLat));
        fd.append('lng', String(currentLng));
        fetch(API_BASE + 'api/location.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); loadJobs(); } else alert(d.error || 'Failed');
        }).catch(function() { alert('Update failed. Check connection.'); }).finally(done);
      }
    });

    document.getElementById('location-cancel').addEventListener('click', closeLocationModal);
    function closeLocationModal() {
      document.getElementById('location-modal').classList.add('hidden');
      document.getElementById('location-modal').style.display = 'none';
    }

    function openReviewModal(url) {
      if (!url) { alert('Review link not configured. Ask the office to add it in Settings.'); return; }
      var modal = document.getElementById('review-qr-modal');
      var img = document.getElementById('review-qr-img');
      var link = document.getElementById('review-qr-link');
      img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(url);
      link.href = url;
      link.textContent = 'Or open review link';
      modal.classList.remove('hidden');
      modal.style.display = 'flex';
    }
    function closeReviewModal() {
      var modal = document.getElementById('review-qr-modal');
      modal.classList.add('hidden');
      modal.style.display = 'none';
    }
    document.getElementById('review-qr-close').addEventListener('click', closeReviewModal);
    document.getElementById('review-qr-modal').addEventListener('click', function(e) {
      if (e.target.id === 'review-qr-modal') closeReviewModal();
    });

    function openRefModal() {
      var modal = document.getElementById('ref-modal');
      var status = document.getElementById('ref-status');
      status.classList.add('hidden');
      status.textContent = '';
      status.classList.remove('text-green-400', 'text-amber-400', 'bg-green-500/10', 'bg-amber-500/10');
      document.getElementById('ref-input').value = '';
      modal.classList.remove('hidden');
      modal.style.display = 'flex';
    }

    function closeRefModal() {
      document.getElementById('ref-modal').classList.add('hidden');
      document.getElementById('ref-modal').style.display = 'none';
    }

    document.getElementById('btn-map-expand').addEventListener('click', function() {
      document.getElementById('map-container').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    document.getElementById('ref-close').addEventListener('click', closeRefModal);
    document.getElementById('ref-modal').addEventListener('click', function(e) {
      if (e.target.id === 'ref-modal') closeRefModal();
    });
    document.getElementById('ref-form').addEventListener('submit', function(e) {
      e.preventDefault();
      var ref = document.getElementById('ref-input').value.replace(/\D/g, '');
      if (ref.length >= 4) {
        window.location.href = '../verify.php?ref=' + encodeURIComponent(ref);
      } else {
        var st = document.getElementById('ref-status');
        st.textContent = 'Enter at least 4 digits of the reference.';
        st.classList.remove('hidden');
        st.classList.add('text-amber-400', 'bg-amber-500/10');
      }
    });
    document.getElementById('ref-input').addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    function markCashPaid(ref) {
      var fd = new FormData();
      fd.append('action', 'cash_paid');
      fd.append('reference', ref);
      fetch(API_BASE + 'api/jobs.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) loadJobs(); else alert(d.error || 'Failed');
      });
    }

    function uploadProof(ref) {
      var input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.capture = 'environment';
      input.onchange = function() {
        if (!input.files || !input.files[0]) return;
        var fd = new FormData();
        fd.append('action', 'proof');
        fd.append('reference', ref);
        fd.append('photo', input.files[0]);
        fetch(API_BASE + 'api/jobs.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) loadJobs(); else alert(d.error || 'Failed');
        });
      };
      input.click();
    }

    var urlParams = new URLSearchParams(window.location.search);
    var verify = urlParams.get('verify');
    if (verify === 'success') {
      alert('Identity verified. You can now start jobs.');
      window.history.replaceState({}, '', window.location.pathname);
    } else if (verify === 'pending') {
      alert('Verification submitted. We\'ll review it shortly. You can start jobs once approved.');
      window.history.replaceState({}, '', window.location.pathname);
    } else if (verify === 'error') {
      alert('Verification could not be completed.');
      window.history.replaceState({}, '', window.location.pathname);
    }
    if (urlParams.get('ref') === '1') setTimeout(function() { openRefModal(); }, 500);

    var installPrompt = null;
    var installBanner = document.getElementById('pwa-install-banner');
    if (installBanner && !window.matchMedia('(display-mode: standalone)').matches && !window.navigator.standalone) {
      window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        installPrompt = e;
        installBanner.classList.remove('hidden');
      });
      document.getElementById('pwa-install-btn') && document.getElementById('pwa-install-btn').addEventListener('click', function() {
        if (installPrompt) {
          installPrompt.prompt();
          installPrompt.userChoice.then(function() { installPrompt = null; installBanner.classList.add('hidden'); });
        }
      });
      document.getElementById('pwa-install-dismiss') && document.getElementById('pwa-install-dismiss').addEventListener('click', function() {
        installBanner.classList.add('hidden');
      });
    }

    function onReady() {
      initMap();
      loadJobs();
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', onReady);
    } else {
      onReady();
    }
    if (typeof EventSource !== 'undefined') {
      var evtSrc = new EventSource(API_BASE + 'api/stream.php');
      evtSrc.addEventListener('update', function(e) {
        try { applyDriverData(JSON.parse(e.data)); } catch (_) {}
      });
      evtSrc.onerror = function() { evtSrc.close(); };
    }
  })();
  </script>
  <style>
  .driver-marker { filter: hue-rotate(45deg) saturate(1.5); }
  #map.leaflet-container { background: var(--app-map-bg) !important; }
  </style>
</body>
</html>
