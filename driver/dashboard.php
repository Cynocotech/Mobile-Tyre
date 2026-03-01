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
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
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

  <main class="max-w-2xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <button type="button" id="btn-location" class="px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-600 text-zinc-300 text-sm hover:bg-zinc-700">
        Update my location
      </button>
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

    function loadJobs() {
      fetch('api/jobs.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          document.getElementById('jobs-loading').classList.add('hidden');
          var list = document.getElementById('jobs-list');
          var empty = document.getElementById('jobs-empty');
          var jobs = d.jobs || [];
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
            return '<div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-4" data-ref="' + (j.reference||'') + '">' +
              '<div class="flex justify-between items-start mb-2">' +
                '<span class="font-mono font-bold text-safety">#' + (j.reference||'') + '</span>' +
                '<span class="text-zinc-500 text-sm">' + (j.date||j.postcode||'') + '</span>' +
              '</div>' +
              '<p class="text-white font-medium">' + v + '</p>' +
              '<p class="text-zinc-400 text-sm mt-1">' + (j.postcode||'') + ' · ' + (j.name||j.email||'') + '</p>' +
              '<p class="text-zinc-500 text-xs mt-2">' + payment + '</p>' +
              '<div class="flex flex-wrap items-center gap-2 mt-3">' +
                '<button type="button" class="loc-btn px-2 py-1 rounded bg-zinc-700 text-xs" data-ref="' + (j.reference||'') + '">Update location</button>' +
                proofBtn +
                (j.payment_method !== 'cash' ? '<button type="button" class="cash-btn px-2 py-1 rounded bg-amber-900/50 text-amber-300 text-xs" data-ref="' + (j.reference||'') + '">Mark cash paid</button>' : '') +
              '</div>' +
            '</div>';
          }).join('');
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

    function openLocationModal(ref) {
      currentRef = ref;
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
          function() {
            document.getElementById('location-status').textContent = 'Could not get location. Enable GPS or enter manually later.';
          }
        );
      } else {
        document.getElementById('location-status').textContent = 'Geolocation not supported.';
      }
    }

    document.getElementById('btn-location').addEventListener('click', function() {
      currentRef = null;
      openLocationModal(null);
    });

    document.getElementById('location-confirm').addEventListener('click', function() {
      if (!currentLat || !currentLng) {
        alert('Location not available yet.');
        return;
      }
      if (currentRef) {
        var fd = new FormData();
        fd.append('action', 'location');
        fd.append('lat', currentLat);
        fd.append('lng', currentLng);
        fd.append('reference', currentRef);
        fetch('api/jobs.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); loadJobs(); } else alert(d.error || 'Failed');
        });
      } else {
        fetch('api/location.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ lat: currentLat, lng: currentLng })
        }).then(function(r) { return r.json(); }).then(function(d) {
          if (d.ok) { closeLocationModal(); } else alert(d.error || 'Failed');
        });
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

    loadJobs();
  })();
  </script>
</body>
</html>
