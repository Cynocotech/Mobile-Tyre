<?php
$pageTitle = 'My jobs';
require_once __DIR__ . '/auth.php';
$driver = getDriverById($_SESSION[DRIVER_SESSION_KEY]);
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | No 5 Tyre Driver</title>
  <link rel="manifest" href="manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="No5 Driver">
  <link rel="apple-touch-icon" href="https://no5tyreandmot.co.uk/wp-content/uploads/2026/02/Car-Service-Logo-with-Wrench-and-Tyre-Icon-370-x-105-px.png">
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('../sw.js', { scope: '/' }).catch(function() {});
    }
  </script>
  <meta name="theme-color" content="#18181b">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
  <div id="pwa-install-banner" class="hidden fixed bottom-4 left-4 right-4 z-50 max-w-2xl mx-auto px-4 py-3 rounded-xl bg-zinc-800 border border-zinc-600 shadow-lg flex items-center justify-between gap-4">
    <p class="text-sm text-zinc-300">Install app for best experience</p>
    <div class="flex gap-2">
      <button type="button" id="pwa-install-btn" class="px-3 py-1.5 rounded-lg bg-safety text-zinc-900 font-semibold text-sm">Install</button>
      <button type="button" id="pwa-install-dismiss" class="px-3 py-1.5 rounded-lg text-zinc-400 text-sm hover:bg-zinc-700">Later</button>
    </div>
  </div>
  <header class="sticky top-0 z-40 bg-zinc-900/95 backdrop-blur border-b border-zinc-700">
    <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-white"><?php echo htmlspecialchars($driver['name'] ?? 'Driver'); ?></h1>
        <p class="text-zinc-500 text-xs">My jobs</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="profile.php" class="px-3 py-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 text-sm">Profile</a>
        <a href="logout.php" class="px-3 py-2 rounded-lg text-zinc-500 hover:text-red-400 text-sm">Logout</a>
      </div>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-6 pb-28">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div id="wallet-card" class="rounded-xl border border-zinc-700 bg-zinc-800/50 px-4 py-3">
          <p class="text-zinc-500 text-xs">Wallet earned</p>
          <p id="wallet-amount" class="text-safety font-bold text-xl">£0.00</p>
        </div>
        <button type="button" id="btn-location" class="px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-600 text-zinc-300 text-sm hover:bg-zinc-700 whitespace-nowrap">
          Update my location
        </button>
        <button type="button" id="btn-scan-qr" class="px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-600 text-zinc-300 text-sm hover:bg-zinc-700 whitespace-nowrap">
          Scan receipt QR
        </button>
      </div>
    </div>

    <div id="map-container" class="rounded-xl border border-zinc-700 overflow-hidden mb-6" style="height: 240px;">
      <div id="map" class="w-full h-full bg-zinc-800"></div>
    </div>

    <div id="verification-banner" class="hidden rounded-xl border border-amber-700 bg-amber-900/30 p-4 mb-6">
      <p class="text-amber-200 text-sm font-medium">Verify your identity (KYC)</p>
      <p class="text-amber-300/80 text-xs mt-1">Complete payout setup (Stripe) and verify your license/ID with Stripe Identity before you can start jobs.</p>
      <div class="flex flex-wrap gap-2 mt-2">
        <button type="button" id="btn-verify-identity" class="px-3 py-1.5 rounded-lg bg-amber-600 text-amber-900 font-medium text-sm hover:bg-amber-500">Verify license/ID</button>
        <a href="onboarding.html" class="px-3 py-1.5 rounded-lg bg-zinc-700 text-zinc-200 text-sm hover:bg-zinc-600">Complete payout setup</a>
      </div>
    </div>

    <div id="jobs-loading" class="text-zinc-500 py-8 text-center">Loading jobs…</div>
    <div id="jobs-list" class="space-y-4 hidden pb-24"></div>
    <div id="jobs-empty" class="hidden text-center py-12 text-zinc-500 pb-24">
      <p class="text-lg">No jobs assigned yet.</p>
      <p class="text-sm mt-2">Jobs will appear here when assigned by the office.</p>
    </div>
  </main>

  <!-- Floating Go online button -->
  <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center justify-center">
    <div class="relative">
      <div class="absolute inset-0 rounded-full bg-safety animate-ping opacity-40"></div>
      <button type="button" id="btn-online" class="relative w-24 h-24 rounded-full bg-safety text-zinc-900 font-bold text-sm shadow-xl shadow-black/40 hover:bg-[#e5c900] active:scale-95 transition-all flex items-center justify-center ring-4 ring-safety/30" title="Go online">
        <span id="online-label" class="text-center text-sm font-bold leading-tight">Go<br>online</span>
      </button>
    </div>
  </div>

  <!-- QR Scanner modal (same template as driver-scanner) -->
  <div id="qr-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
    <div class="w-full max-w-lg rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-4">
      <div class="text-center mb-6">
        <h3 class="text-xl font-bold text-white mb-1">Scan job receipt</h3>
        <p class="text-zinc-500 text-sm">Point your camera at the receipt QR, or enter reference below</p>
        <p id="qr-ios-hint" class="hidden text-amber-400/90 text-xs mt-2">Camera not working? Open in Safari first: <a href="#" id="qr-open-safari" class="underline">Open this page in Safari</a></p>
      </div>
      <div class="scanner-frame mb-6 bg-black rounded-2xl p-4">
        <div id="qr-reader" class="rounded-xl overflow-hidden" style="width: 100%; min-height: 260px;"></div>
      </div>
      <div class="relative flex items-center gap-4 mb-6">
        <div class="flex-1 h-px bg-zinc-700"></div>
        <span class="text-zinc-500 text-xs font-medium">or enter reference</span>
        <div class="flex-1 h-px bg-zinc-700"></div>
      </div>
      <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-4 mb-4">
        <form id="qr-ref-form" class="flex gap-3">
          <div class="flex-1 relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 font-mono text-lg">#</span>
            <input type="text" id="qr-ref-input" placeholder="123456" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="w-full pl-8 pr-4 py-3 rounded-xl bg-zinc-700/80 border-2 border-zinc-600 text-white font-mono text-xl placeholder-zinc-600 focus:border-safety focus:ring-2 focus:ring-safety/20 focus:outline-none transition-colors">
          </div>
          <button type="submit" class="px-5 py-3 bg-safety text-zinc-900 font-bold rounded-xl hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-800 shrink-0 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            View
          </button>
        </form>
      </div>
      <p id="qr-status" class="text-center text-sm py-3 rounded-lg hidden mb-4"></p>
      <button type="button" id="qr-close" class="w-full px-4 py-2 border border-zinc-600 text-zinc-300 rounded-lg text-sm hover:bg-zinc-700">Close</button>
    </div>
  </div>

  <!-- Location modal -->
  <div id="location-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
    <div class="w-full max-w-sm rounded-2xl border border-zinc-700 bg-zinc-800 p-6">
      <h3 class="text-lg font-bold text-white mb-4">Update location</h3>
      <p id="location-status" class="text-sm text-zinc-400 mb-4">Getting your position…</p>
      <div class="flex flex-wrap gap-3">
        <button type="button" id="location-confirm" class="flex-1 px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Update</button>
        <button type="button" id="location-retry" class="hidden px-4 py-2 bg-zinc-600 text-zinc-200 rounded-lg text-sm hover:bg-zinc-500">Retry</button>
        <button type="button" id="location-cancel" class="px-4 py-2 border border-zinc-600 rounded-lg text-sm">Cancel</button>
      </div>
    </div>
  </div>

  <script>
  (function() {
    var currentLat, currentLng, currentRef;
    var map, driverMarker, jobMarkers = [];
    var driverData = {};

    function initMap() {
      if (map) return;
      map = L.map('map').setView([51.5074, -0.1278], 10);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
      }).addTo(map);
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
      var pingEl = btn && btn.previousElementSibling;
      var baseClass = 'relative w-24 h-24 rounded-full font-bold text-sm shadow-xl shadow-black/40 active:scale-95 transition-all flex items-center justify-center';
      if (online) {
        btn.className = baseClass + ' bg-green-500 text-white hover:bg-green-400 ring-4 ring-green-500/30';
        lbl.innerHTML = 'Online';
        btn.title = 'You are online';
        if (pingEl) pingEl.classList.add('hidden');
      } else {
        btn.className = baseClass + ' bg-safety text-zinc-900 hover:bg-[#e5c900] ring-4 ring-safety/30';
        lbl.innerHTML = 'Go<br>online';
        btn.title = 'Go online';
        if (pingEl) pingEl.classList.remove('hidden');
      }
    }

    var API_BASE = (function() {
      var p = window.location.pathname;
      return p.replace(/[^/]+$/, '');
    })();

    function loadJobs() {
      fetch(API_BASE + 'api/jobs.php', { credentials: 'same-origin' })
        .then(function(r) {
          if (!r.ok) throw new Error('Server error ' + r.status);
          return r.json();
        })
        .then(function(d) {
          document.getElementById('jobs-loading').classList.add('hidden');
          driverData = d.driver || {};
          var jobs = d.jobs || [];
          var verified = driverData.kyc_verified;
          document.getElementById('verification-banner').classList.toggle('hidden', verified);
          document.getElementById('wallet-amount').textContent = '£' + (driverData.wallet_earned || 0).toFixed(2);
          setOnlineBtn(driverData.is_online);
          updateMap(jobs, driverData);
          var list = document.getElementById('jobs-list');
          var empty = document.getElementById('jobs-empty');
          if (jobs.length === 0) {
            list.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
          }
          empty.classList.add('hidden');
          list.classList.remove('hidden');
          list.innerHTML = jobs.map(function(j) {
            var v = (j.make||'') + ' ' + (j.model||''); if (!v.trim()) v = j.vrm || '—'; else if (j.vrm) v += ' (' + j.vrm + ')';
            var payment = j.payment_method === 'cash' ? '<span class="text-amber-400">Cash</span>' + (j.cash_paid_at ? ' (marked paid)' : '');
            else payment = 'Card (deposit) – balance due: ' + (j.balance_due || '—');
            var proofBtn = j.proof_url ? '<span class="text-green-400 text-xs">Proof uploaded</span>' : '<button type="button" class="proof-btn px-2 py-1 rounded bg-zinc-700 text-xs" data-ref="' + j.reference + '">Upload proof</button>';
            var canStart = verified;
            var startBtn = j.job_started_at ? '<span class="text-green-400 text-xs">Started</span>' : (canStart ? '<button type="button" class="start-btn px-2 py-1 rounded bg-safety text-zinc-900 text-xs font-medium" data-ref="' + (j.reference||'') + '">Start job</button>' : '<span class="text-amber-400 text-xs">Verify first</span>');
            return '<div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-4" data-ref="' + (j.reference||'') + '">' +
              '<div class="flex justify-between items-start mb-2">' +
                '<span class="font-mono font-bold text-safety">#' + (j.reference||'') + '</span>' +
                '<span class="text-zinc-500 text-sm">' + (j.date||j.postcode||'') + '</span>' +
              '</div>' +
              '<p class="text-white font-medium">' + escapeHtml(v) + '</p>' +
              '<p class="text-zinc-400 text-sm mt-1">' + escapeHtml(j.postcode||'') + ' · ' + escapeHtml(j.name||j.email||'') + '</p>' +
              '<p class="text-zinc-500 text-xs mt-2">' + payment + '</p>' +
              '<div class="flex flex-wrap items-center gap-2 mt-3">' +
                startBtn +
                '<button type="button" class="loc-btn px-2 py-1 rounded bg-zinc-700 text-xs" data-ref="' + (j.reference||'') + '">Update location</button>' +
                proofBtn +
                (j.payment_method !== 'cash' ? '<button type="button" class="cash-btn px-2 py-1 rounded bg-amber-900/50 text-amber-300 text-xs" data-ref="' + (j.reference||'') + '">Mark cash paid</button>' : '') +
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
        })
        .catch(function(err) {
          document.getElementById('jobs-loading').textContent = 'Failed to load. Try refreshing.';
          console.error('loadJobs:', err);
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

    document.getElementById('btn-location').addEventListener('click', function() {
      currentRef = null;
      openLocationModal(null);
    });

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
      btn.disabled = true;
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

    var qrScanner = null;
    var qrLastScanned = '';
    function goToVerifyFromScan(decodedText) {
      var u = String(decodedText).trim();
      if (u.match(/^[0-9]{4,6}$/)) return '../verify.php?ref=' + encodeURIComponent(u);
      var m = u.match(/session_id=([a-zA-Z0-9_]+)/);
      if (m) return '../verify.php?session_id=' + encodeURIComponent(m[1]);
      if (u.indexOf('http') === 0 && u.indexOf('verify') !== -1) return u;
      return null;
    }
    function openQrModal() {
      var modal = document.getElementById('qr-modal');
      var status = document.getElementById('qr-status');
      var iosHint = document.getElementById('qr-ios-hint');
      if (iosHint) iosHint.classList.add('hidden');
      status.classList.add('hidden');
      status.textContent = '';
      status.classList.remove('text-green-400', 'text-amber-400', 'bg-green-500/10', 'bg-amber-500/10');
      document.getElementById('qr-ref-input').value = '';
      qrLastScanned = '';
      modal.classList.remove('hidden');
      modal.style.display = 'flex';
      if (!window.isSecureContext) {
        status.textContent = 'Camera requires HTTPS. Open this app via https:// to scan.';
        status.classList.remove('hidden');
        status.classList.add('text-amber-400', 'bg-amber-500/10');
        return;
      }
      var Html5QrcodeClass = (typeof Html5Qrcode !== 'undefined' && Html5Qrcode) || (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode);
      if (!Html5QrcodeClass) {
        status.textContent = 'Scanner not loaded. Enter reference below.';
        status.classList.remove('hidden');
        status.classList.add('text-amber-400', 'bg-amber-500/10');
        return;
      }
      var readerEl = document.getElementById('qr-reader');
      readerEl.innerHTML = '';
      status.textContent = 'Starting camera…';
      status.classList.remove('hidden');
      status.classList.add('text-zinc-500');
      qrScanner = new Html5QrcodeClass('qr-reader');
      var scanConfig = { fps: 15, qrbox: { width: 200, height: 200 }, disableFlip: false };
      function onScan(decodedText) {
        if (!decodedText || decodedText === qrLastScanned) return;
        qrLastScanned = decodedText;
        var target = goToVerifyFromScan(decodedText);
        if (target) {
          status.textContent = 'Found! Loading job…';
          status.classList.remove('hidden', 'text-amber-400', 'bg-amber-500/10');
          status.classList.add('text-green-400', 'bg-green-500/10');
          qrScanner.stop().then(function() { qrScanner = null; }).catch(function() { qrScanner = null; });
          window.location.href = target;
        } else {
          status.textContent = 'Unknown format. Scan the receipt QR or enter the 6-digit reference.';
          status.classList.remove('hidden', 'text-green-400', 'bg-green-500/10');
          status.classList.add('text-amber-400', 'bg-amber-500/10');
        }
      }
      function tryStart(c) {
        return qrScanner.start(c, scanConfig, onScan, function() {}).then(function() { return c; });
      }
      function showCameraError(showSafariHint) {
        status.textContent = 'Camera not available. Enter reference below.';
        status.classList.remove('hidden', 'text-green-400', 'bg-green-500/10');
        status.classList.add('text-amber-400', 'bg-amber-500/10');
        if (showSafariHint && iosHint && (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches)) {
          iosHint.classList.remove('hidden');
          var link = document.getElementById('qr-open-safari');
          if (link) link.href = window.location.href;
        }
      }
      tryStart({ facingMode: 'environment' }).catch(function() {
        return tryStart({ facingMode: 'user' }).catch(function() {
          return Html5QrcodeClass.getCameras ? Html5QrcodeClass.getCameras().then(function(cams) {
            if (!cams || cams.length === 0) throw new Error('No camera');
            var cam = cams.find(function(c) { return /back|rear|environment/i.test(c.label); }) || cams[0];
            return tryStart(cam.id);
          }) : Promise.reject(new Error('No camera'));
        });
      }).then(function() {
        status.classList.add('hidden');
        status.classList.remove('text-zinc-500');
      }).catch(function() {
        showCameraError(true);
      });
    }

    function closeQrModal() {
      var modal = document.getElementById('qr-modal');
      if (qrScanner) {
        qrScanner.stop().catch(function() {});
        qrScanner = null;
      }
      document.getElementById('qr-reader').innerHTML = '';
      modal.classList.add('hidden');
      modal.style.display = 'none';
    }

    document.getElementById('btn-scan-qr').addEventListener('click', openQrModal);
    document.getElementById('qr-close').addEventListener('click', closeQrModal);
    var qrOpenSafari = document.getElementById('qr-open-safari');
    if (qrOpenSafari) qrOpenSafari.addEventListener('click', function(e) {
      e.preventDefault();
      window.open(window.location.href, '_blank', 'noopener');
    });
    document.getElementById('qr-modal').addEventListener('click', function(e) {
      if (e.target.id === 'qr-modal') closeQrModal();
    });
    document.getElementById('qr-ref-form').addEventListener('submit', function(e) {
      e.preventDefault();
      var ref = document.getElementById('qr-ref-input').value.replace(/\D/g, '');
      if (ref.length >= 4) {
        window.location.href = '../verify.php?ref=' + encodeURIComponent(ref);
      } else {
        var st = document.getElementById('qr-status');
        st.textContent = 'Enter at least 4 digits of the reference.';
        st.classList.remove('hidden');
        st.classList.add('text-amber-400', 'bg-amber-500/10');
      }
    });
    document.getElementById('qr-ref-input').addEventListener('input', function() {
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
    if (urlParams.get('scan') === '1') setTimeout(openQrModal, 500);

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

    loadJobs();
  })();
  </script>
  <style>
  .driver-marker { filter: hue-rotate(45deg) saturate(1.5); }
  #map.leaflet-container { background: #27272a !important; }
  .scanner-frame { position: relative; border-radius: 1.5rem; overflow: visible; box-shadow: 0 0 0 4px rgba(254, 222, 0, 0.15), 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
  #qr-reader { border-radius: 1rem; overflow: hidden; }
  #qr-reader video, #qr-reader img, #qr-reader canvas { border-radius: 1rem; object-fit: cover; }
  #qr-reader__scan_region { background: #000 !important; }
  #qr-reader__dashboard { margin-top: 0.5rem !important; }
  #qr-reader__dashboard_section { padding: 0.5rem 0 !important; }
  #qr-reader__dashboard_section_csr button, #qr-reader button { background: #fede00 !important; color: #000 !important; border: none !important; padding: 0.5rem 1rem !important; border-radius: 0.5rem !important; font-weight: 600 !important; cursor: pointer !important; }
  </style>
</body>
</html>
