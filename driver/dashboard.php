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
  <link rel="manifest" href="../manifest.json">
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('../sw.js').catch(function() {});
    }
  </script>
  <meta name="theme-color" content="#18181b">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
  <header class="sticky top-0 z-40 bg-zinc-900/95 backdrop-blur border-b border-zinc-700">
    <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-white"><?php echo htmlspecialchars($driver['name'] ?? 'Driver'); ?></h1>
        <p class="text-zinc-500 text-xs">My jobs</p>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" id="btn-online" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors">
          <span id="online-label">Go online</span>
        </button>
        <a href="profile.php" class="px-3 py-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 text-sm">Profile</a>
        <a href="logout.php" class="px-3 py-2 rounded-lg text-zinc-500 hover:text-red-400 text-sm">Logout</a>
      </div>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div id="wallet-card" class="rounded-xl border border-zinc-700 bg-zinc-800/50 px-4 py-3">
          <p class="text-zinc-500 text-xs">Wallet earned</p>
          <p id="wallet-amount" class="text-safety font-bold text-xl">£0.00</p>
        </div>
        <button type="button" id="btn-location" class="px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-600 text-zinc-300 text-sm hover:bg-zinc-700 whitespace-nowrap">
          Update my location
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
    <div id="jobs-list" class="space-y-4 hidden"></div>
    <div id="jobs-empty" class="hidden text-center py-12 text-zinc-500">
      <p class="text-lg">No jobs assigned yet.</p>
      <p class="text-sm mt-2">Jobs will appear here when assigned by the office.</p>
    </div>
  </main>

  <!-- Location modal -->
  <div id="location-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
    <div class="w-full max-w-sm rounded-2xl border border-zinc-700 bg-zinc-800 p-6">
      <h3 class="text-lg font-bold text-white mb-4">Update location</h3>
      <p id="location-status" class="text-sm text-zinc-400 mb-4">Getting your position…</p>
      <div class="flex gap-3">
        <button type="button" id="location-confirm" class="flex-1 px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Update</button>
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
      if (online) {
        btn.className = 'px-3 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-500';
        lbl.textContent = 'Online';
      } else {
        btn.className = 'px-3 py-2 rounded-lg text-sm font-medium bg-zinc-700 text-zinc-300 hover:bg-zinc-600';
        lbl.textContent = 'Go online';
      }
    }

    function loadJobs() {
      fetch('api/jobs.php')
        .then(function(r) { return r.json(); })
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
              fetch('api/jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'job_start', reference: ref })
              }).then(function(r) { return r.json(); }).then(function(d) {
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
        .catch(function() {
          document.getElementById('jobs-loading').textContent = 'Failed to load.';
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
      document.getElementById('location-modal').classList.remove('hidden');
      document.getElementById('location-modal').style.display = 'flex';
      document.getElementById('location-status').textContent = 'Getting your position…';
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(p) {
            currentLat = p.coords.latitude;
            currentLng = p.coords.longitude;
            document.getElementById('location-status').textContent = 'Location ready. Click Update to save.';
          },
          function(err) {
            var msg = 'Could not get location. ';
            if (err.code === 1) msg += 'Allow location access in browser settings.';
            else if (err.code === 2) msg += 'Position unavailable.';
            else if (err.code === 3) msg += 'Request timed out.';
            document.getElementById('location-status').textContent = msg;
          },
          { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
      } else {
        document.getElementById('location-status').textContent = 'Geolocation not supported.';
      }
    }

    document.getElementById('btn-location').addEventListener('click', function() {
      currentRef = null;
      openLocationModal(null);
    });

    document.getElementById('btn-verify-identity').addEventListener('click', function() {
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Loading…';
      fetch('api/verification-session.php', { method: 'POST' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.url) window.location.href = d.url;
          else { alert(d.error || 'Failed'); btn.disabled = false; btn.textContent = 'Verify license/ID'; }
        })
        .catch(function() { alert('Network error'); btn.disabled = false; btn.textContent = 'Verify license/ID'; });
    });

    document.getElementById('btn-online').addEventListener('click', function() {
      var wantOnline = !driverData.is_online;
      fetch('api/jobs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'set_online', online: wantOnline })
      }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) {
          driverData.is_online = d.is_online;
          setOnlineBtn(d.is_online);
        } else alert(d.error || 'Failed');
      });
    });

    document.getElementById('location-confirm').addEventListener('click', function() {
      if (currentLat == null || currentLng == null || isNaN(currentLat) || isNaN(currentLng)) {
        alert('Location not available yet. Wait for GPS or check permissions.');
        return;
      }
      if (currentRef) {
        var fd = new FormData();
        fd.append('action', 'location');
        fd.append('lat', String(currentLat));
        fd.append('lng', String(currentLng));
        fd.append('reference', currentRef);
        fetch('api/jobs.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); loadJobs(); } else alert(d.error || 'Failed');
        }).catch(function() { alert('Update failed. Check connection.'); });
      } else {
        fetch('api/location.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ lat: currentLat, lng: currentLng })
        }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); loadJobs(); } else alert(d.error || 'Failed');
        }).catch(function() { alert('Update failed. Check connection.'); });
      }
    });

    document.getElementById('location-cancel').addEventListener('click', closeLocationModal);
    function closeLocationModal() {
      document.getElementById('location-modal').classList.add('hidden');
      document.getElementById('location-modal').style.display = 'none';
    }

    function markCashPaid(ref) {
      var fd = new FormData();
      fd.append('action', 'cash_paid');
      fd.append('reference', ref);
      fetch('api/jobs.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
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
        fetch('api/jobs.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
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

    loadJobs();
  })();
  </script>
  <style>
  .driver-marker { filter: hue-rotate(45deg) saturate(1.5); }
  #map.leaflet-container { background: #27272a !important; }
  </style>
</body>
</html>
