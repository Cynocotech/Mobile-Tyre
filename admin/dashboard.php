<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<h1 class="text-2xl font-bold text-white mb-8 flex items-center gap-3">
  <svg class="w-8 h-8 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
  Dashboard
</h1>

<div id="stats-loading" class="text-zinc-500">Loading stats…</div>
<div id="stats-content" class="hidden space-y-8">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-safety/20 flex items-center justify-center">
          <svg class="w-5 h-5 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-zinc-500 text-sm font-medium">Total deposits</p>
          <p id="stat-deposits-total" class="text-2xl font-bold text-safety mt-0.5">£0</p>
          <p class="text-zinc-400 text-xs mt-0.5"><span id="stat-deposits-count">0</span> paid</p>
        </div>
      </div>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-zinc-700 flex items-center justify-center">
          <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-zinc-500 text-sm font-medium">Last 7 days</p>
          <p id="stat-deposits-7" class="text-2xl font-bold text-white mt-0.5">0</p>
          <p class="text-zinc-400 text-xs mt-0.5">deposits</p>
        </div>
      </div>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-zinc-700 flex items-center justify-center">
          <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-zinc-500 text-sm font-medium">Jobs</p>
          <p id="stat-jobs" class="text-2xl font-bold text-white mt-0.5">0</p>
          <p class="text-zinc-400 text-xs mt-0.5">in system</p>
        </div>
      </div>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-zinc-700 flex items-center justify-center">
          <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-zinc-500 text-sm font-medium">Quotes</p>
          <p id="stat-quotes" class="text-2xl font-bold text-white mt-0.5">0</p>
          <p class="text-zinc-400 text-xs mt-0.5">requests</p>
        </div>
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700 flex items-center gap-2">
      <svg class="w-5 h-5 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Online drivers
    </h2>
    <div id="online-drivers-list" class="px-6 py-4 space-y-2 max-h-48 overflow-y-auto border-b border-zinc-700">
      <p class="text-zinc-500 text-sm">Loading…</p>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700 flex items-center gap-2">
      <svg class="w-5 h-5 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Driver locations
    </h2>
    <div id="admin-map-container" class="w-full" style="height: 320px; min-height: 240px;">
      <div id="admin-map" class="w-full h-full bg-zinc-800"></div>
    </div>
    <p id="admin-map-empty" class="hidden px-6 py-4 text-zinc-500 text-sm">No drivers with location data yet. Drivers appear here when they go online and update their location.</p>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700 flex items-center gap-2">
      <svg class="w-5 h-5 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Recent deposits <span class="text-zinc-500 text-sm font-normal">(click for details)</span>
    </h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-700">
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Date</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Reference</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Email</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Postcode</th>
            <th class="text-right py-3 px-4 text-zinc-400 font-medium">Amount</th>
            <th class="text-right py-3 px-4 text-zinc-400 font-medium">Est. total</th>
          </tr>
        </thead>
        <tbody id="recent-deposits-body">
          <tr><td colspan="6" class="py-8 text-center text-zinc-500">No deposits yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Order detail modal -->
<div id="order-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
  <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-800 p-6">
    <div class="flex justify-between items-start mb-6">
      <h2 class="text-xl font-bold text-white">Order #<span id="order-ref">—</span></h2>
      <div class="flex items-center gap-2">
        <a id="print-invoice-btn" href="#" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-safety text-zinc-900 font-bold text-sm hover:bg-[#e5c900] no-underline">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Print invoice
        </a>
        <button type="button" id="order-modal-close" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700">×</button>
      </div>
    </div>
    <div id="order-loading" class="text-zinc-500 py-8 text-center">Loading…</div>
    <div id="order-detail" class="hidden space-y-6"></div>
  </div>
</div>

<script>
(function() {
  var adminMap, adminMarkers = [];

  function initAdminMap(locations) {
    var container = document.getElementById('admin-map-container');
    var emptyMsg = document.getElementById('admin-map-empty');
    if (!locations || locations.length === 0) {
      if (container) container.classList.add('hidden');
      if (emptyMsg) emptyMsg.classList.remove('hidden');
      return;
    }
    if (container) container.classList.remove('hidden');
    if (emptyMsg) emptyMsg.classList.add('hidden');
    if (!adminMap) {
      adminMap = L.map('admin-map').setView([51.5074, -0.1278], 10);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
      }).addTo(adminMap);
    }
    adminMarkers.forEach(function(m) { adminMap.removeLayer(m); });
    adminMarkers = [];
    var bounds = [];
    locations.forEach(function(d) {
      var lat = parseFloat(d.lat), lng = parseFloat(d.lng);
      if (isNaN(lat) || isNaN(lng)) return;
      var name = (d.name || 'Driver').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      var popup = name + (d.is_online ? ' <span class="text-green-400">• Online</span>' : '');
      var m = L.marker([lat, lng]).addTo(adminMap).bindPopup(popup);
      adminMarkers.push(m);
      bounds.push([lat, lng]);
    });
    if (bounds.length > 0) {
      adminMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
    }
  }

  function renderStats(data) {
    document.getElementById('stats-loading').classList.add('hidden');
    document.getElementById('stats-content').classList.remove('hidden');
    initAdminMap(data.driverLocations || []);
    var drivers = data.drivers || [];
    var onlineDriversEl = document.getElementById('online-drivers-list');
    if (onlineDriversEl) {
      if (drivers.length === 0) {
        onlineDriversEl.innerHTML = '<p class="text-zinc-500 text-sm">No drivers yet. Add drivers or they will appear after onboarding.</p>';
      } else {
        var onlineCount = drivers.filter(function(d) { return d.is_online; }).length;
        function escD(s) { if (s == null || s === '') return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        onlineDriversEl.innerHTML = drivers.map(function(d) {
          var status = d.is_online ? '<span class="text-green-400">Online</span>' : '<span class="text-zinc-500">Offline</span>';
          return '<div class="flex justify-between items-center py-1 text-sm"><span class="text-zinc-300">' + escD(d.name || d.id || '—') + '</span>' + status + '</div>';
        }).join('');
        var h2 = onlineDriversEl.previousElementSibling;
        if (h2) h2.innerHTML = '<svg class="w-5 h-5 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Online drivers <span class="text-zinc-500 font-normal text-sm">(' + onlineCount + '/' + drivers.length + ')</span>';
      }
    }
      if (data.deposits) {
        document.getElementById('stat-deposits-total').textContent = '£' + (data.deposits.total || 0).toFixed(2);
        document.getElementById('stat-deposits-count').textContent = data.deposits.count || 0;
        document.getElementById('stat-deposits-7').textContent = data.deposits.last7 || 0;
      }
      document.getElementById('stat-jobs').textContent = data.jobs || 0;
      document.getElementById('stat-quotes').textContent = data.quotes || 0;
      var tbody = document.getElementById('recent-deposits-body');
      var rows = data.recentDeposits || [];
      if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-zinc-500">No deposits yet</td></tr>';
        return;
      }
      function esc(s) { if (s == null || s === '') return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
      tbody.innerHTML = rows.map(function(d) {
        var ref = esc((d.reference || '').toString());
        return '<tr class="order-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer" data-ref="' + ref + '" role="button" tabindex="0">' +
          '<td class="py-3 px-4 text-zinc-300">' + esc(d.date) + '</td>' +
          '<td class="py-3 px-4 font-mono text-safety">' + esc(d.reference) + '</td>' +
          '<td class="py-3 px-4 text-zinc-300">' + esc(d.email) + '</td>' +
          '<td class="py-3 px-4 text-zinc-400">' + esc(d.postcode) + '</td>' +
          '<td class="py-3 px-4 text-right font-semibold text-white">' + esc(d.amount_paid) + '</td>' +
          '<td class="py-3 px-4 text-right text-zinc-400">' + esc(d.estimate_total) + '</td>' +
        '</tr>';
      }).join('');
      tbody.querySelectorAll('.order-row').forEach(function(row) {
        row.addEventListener('click', function() { showOrder(row.getAttribute('data-ref')); });
        row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showOrder(row.getAttribute('data-ref')); } });
      });
  }

  function fetchStats() {
    fetch('api/stats.php')
      .then(function(r) { return r.json(); })
      .then(renderStats)
      .catch(function() {
        document.getElementById('stats-loading').textContent = 'Failed to load stats.';
      });
  }

  fetchStats();
  if (typeof EventSource !== 'undefined') {
    var evtSrc = new EventSource('api/stream.php');
    evtSrc.addEventListener('stats', function(e) {
      try { renderStats(JSON.parse(e.data)); } catch (_) {}
    });
    evtSrc.onerror = function() {
      evtSrc.close();
      setInterval(fetchStats, 15000);
    };
  } else {
    setInterval(fetchStats, 15000);
  }

  function showOrder(ref) {
    if (!ref) return;
    var modal = document.getElementById('order-modal');
    var modalContent = modal && modal.firstElementChild ? modal.firstElementChild : null;
    var detailEl = document.getElementById('order-detail');
    var loadingEl = document.getElementById('order-loading');
    document.getElementById('order-ref').textContent = ref;
    document.getElementById('print-invoice-btn').href = 'invoice.php?ref=' + encodeURIComponent(ref);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    detailEl.classList.add('hidden');
    loadingEl.classList.remove('hidden');
    if (modalContent) modalContent.scrollTop = 0;
    fetch('api/order.php?ref=' + encodeURIComponent(ref))
      .then(function(r) { return r.json(); })
      .then(function(o) {
        loadingEl.classList.add('hidden');
        detailEl.classList.remove('hidden');
        var bal = '';
        if (o.estimate_total && o.amount_paid) {
          var est = parseFloat(String(o.estimate_total).replace(/[^0-9.]/g, ''));
          var paid = parseFloat(String(o.amount_paid).replace(/[^0-9.]/g, ''));
          if (!isNaN(est) && !isNaN(paid)) bal = '£' + Math.max(0, est - paid).toFixed(2);
        }
        function esc2(s) { if (s == null || s === '') return '—'; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
        detailEl.innerHTML =
          '<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Payment</h3>' +
              '<dl class="space-y-2 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Date</dt><dd class="text-white">' + esc2(o.date) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Deposit paid</dt><dd class="font-semibold text-safety">' + esc2(o.amount_paid) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Estimate total</dt><dd class="text-white">' + esc2(o.estimate_total) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Balance due</dt><dd class="font-semibold text-safety">' + esc2(bal) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Status</dt><dd class="text-white">' + esc2(o.payment_status) + '</dd></div>' +
              '</dl>' +
            '</div>' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Customer</h3>' +
              '<dl class="space-y-2 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Name</dt><dd class="text-white">' + esc2(o.name) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Email</dt><dd class="text-white break-all">' + esc2(o.email) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Phone</dt><dd class="text-white"><a href="tel:' + esc2((o.phone||'').replace(/\D/g,'')) + '" class="text-safety hover:underline">' + esc2(o.phone) + '</a></dd></div>' +
              '</dl>' +
            '</div>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Location</h3>' +
            '<p class="text-white font-medium">' + esc2(o.postcode) + '</p>' +
            (o.lat && o.lng ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(o.lat + ',' + o.lng) + '" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-safety text-sm mt-2 hover:underline">Open in Maps</a>' : '') +
          '</div>' +
          (o.session_id ? '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<a href="../verify.php?session_id=' + encodeURIComponent(o.session_id) + '" target="_blank" class="inline-flex items-center gap-2 text-safety text-sm font-medium hover:underline">View verify page (driver scan)</a>' +
          '</div>' : '') +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Driver</h3>' +
            '<div class="flex flex-wrap items-center gap-2">' +
              '<span class="text-white">' + esc2(o.assigned_driver_name) + '</span>' +
              '<select id="assign-driver-select" class="px-3 py-1.5 rounded bg-zinc-700 border border-zinc-600 text-white text-sm">' +
                '<option value="">Assign driver…</option>' +
              '</select>' +
              '<button type="button" id="assign-driver-btn" class="px-3 py-1.5 rounded bg-safety text-zinc-900 text-sm font-medium">Assign</button>' +
            '</div>' +
            (o.payment_method === 'cash' ? '<p class="text-amber-400 text-xs mt-2">Paid in cash' + (o.cash_paid_at ? ' at ' + o.cash_paid_at : '') + '</p>' : '') +
            (o.proof_url ? '<p class="text-green-400 text-xs mt-1">Proof uploaded</p>' : '') +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Vehicle</h3>' +
            '<dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">VRM</dt><dd class="text-white font-mono">' + esc2(o.vrm) + '</dd>' +
              '<dt class="text-zinc-500">Make</dt><dd class="text-white">' + esc2(o.make) + '</dd>' +
              '<dt class="text-zinc-500">Model</dt><dd class="text-white">' + esc2(o.model) + '</dd>' +
              '<dt class="text-zinc-500">Tyre size</dt><dd class="text-white">' + esc2(o.tyre_size) + '</dd>' +
              '<dt class="text-zinc-500">Wheels</dt><dd class="text-white">' + esc2(o.wheels) + '</dd>' +
            '</dl>' +
          '</div>';
        return o;
      })
      .then(function(o) {
        if (o) loadDriversForAssign(ref, o.assigned_driver_id);
      })
      .catch(function() {
        loadingEl.classList.add('hidden');
        detailEl.classList.remove('hidden');
        detailEl.innerHTML = '<p class="text-red-400">Failed to load order details.</p>';
      });
  }

  function loadDriversForAssign(ref, assignedDriverId) {
    fetch('api/drivers-list.php').then(function(r) { return r.json(); }).then(function(d) {
      var sel = document.getElementById('assign-driver-select');
      if (!sel) return;
      var drivers = d.drivers || [];
      sel.innerHTML = '<option value="">Assign driver…</option>' + drivers.map(function(drv) {
        var selAttr = (assignedDriverId && drv.id === assignedDriverId) ? ' selected' : '';
        return '<option value="' + drv.id + '"' + selAttr + '>' + (drv.name || drv.email) + ' – ' + (drv.van_make || '') + ' ' + (drv.van_reg || '') + '</option>';
      }).join('');
      var btn = document.getElementById('assign-driver-btn');
      if (btn) btn.onclick = function() {
        var did = sel.value;
        if (!did) {
          alert('Select a driver first.');
          return;
        }
        btn.disabled = true;
        btn.textContent = 'Assigning…';
        fetch('api/assign-driver.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ reference: ref, driver_id: did })
        }).then(function(r) { return r.json(); }).then(function(res) {
          btn.disabled = false;
          btn.textContent = 'Assign';
          if (res.ok) {
            showOrder(ref);
          } else {
            alert(res.error || 'Failed to assign driver');
          }
        }).catch(function() {
          btn.disabled = false;
          btn.textContent = 'Assign';
          alert('Network error. Try again.');
        });
      };
    });
  }

  document.getElementById('order-modal-close').addEventListener('click', function() {
    document.getElementById('order-modal').classList.add('hidden');
    document.getElementById('order-modal').style.display = 'none';
  });
  document.getElementById('order-modal').addEventListener('click', function(e) {
    if (e.target === document.getElementById('order-modal')) {
      document.getElementById('order-modal').classList.add('hidden');
      document.getElementById('order-modal').style.display = 'none';
    }
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
