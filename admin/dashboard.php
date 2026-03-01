<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Dashboard</h1>

<div id="stats-loading" class="text-zinc-500">Loading stats…</div>
<div id="stats-content" class="hidden space-y-8">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Total deposits</p>
      <p id="stat-deposits-total" class="text-2xl font-bold text-safety mt-1">£0</p>
      <p class="text-zinc-400 text-xs mt-1"><span id="stat-deposits-count">0</span> paid</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Last 7 days</p>
      <p id="stat-deposits-7" class="text-2xl font-bold text-white mt-1">0</p>
      <p class="text-zinc-400 text-xs mt-1">deposits</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Jobs</p>
      <p id="stat-jobs" class="text-2xl font-bold text-white mt-1">0</p>
      <p class="text-zinc-400 text-xs mt-1">in system</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Quotes</p>
      <p id="stat-quotes" class="text-2xl font-bold text-white mt-1">0</p>
      <p class="text-zinc-400 text-xs mt-1">requests</p>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700">Recent deposits <span class="text-zinc-500 text-sm font-normal">(click for details)</span></h2>
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
      <button type="button" id="order-modal-close" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700">×</button>
    </div>
    <div id="order-loading" class="text-zinc-500 py-8 text-center">Loading…</div>
    <div id="order-detail" class="hidden space-y-6"></div>
  </div>
</div>

<script>
(function() {
  fetch('api/stats.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      document.getElementById('stats-loading').classList.add('hidden');
      document.getElementById('stats-content').classList.remove('hidden');
      if (data.deposits) {
        document.getElementById('stat-deposits-total').textContent = '£' + (data.deposits.total || 0).toFixed(2);
        document.getElementById('stat-deposits-count').textContent = data.deposits.count || 0;
        document.getElementById('stat-deposits-7').textContent = data.deposits.last7 || 0;
      }
      document.getElementById('stat-jobs').textContent = data.jobs || 0;
      document.getElementById('stat-quotes').textContent = data.quotes || 0;
      var tbody = document.getElementById('recent-deposits-body');
      var rows = data.recentDeposits || [];
      if (rows.length === 0) return;
      tbody.innerHTML = rows.map(function(d) {
        var ref = (d.reference || '').toString();
        return '<tr class="order-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer" data-ref="' + ref + '" role="button" tabindex="0">' +
          '<td class="py-3 px-4 text-zinc-300">' + (d.date || '—') + '</td>' +
          '<td class="py-3 px-4 font-mono text-safety">' + (d.reference || '—') + '</td>' +
          '<td class="py-3 px-4 text-zinc-300">' + (d.email || '—') + '</td>' +
          '<td class="py-3 px-4 text-zinc-400">' + (d.postcode || '—') + '</td>' +
          '<td class="py-3 px-4 text-right font-semibold text-white">' + (d.amount_paid || '—') + '</td>' +
          '<td class="py-3 px-4 text-right text-zinc-400">' + (d.estimate_total || '—') + '</td>' +
        '</tr>';
      }).join('');
      tbody.querySelectorAll('.order-row').forEach(function(row) {
        row.addEventListener('click', function() { showOrder(row.getAttribute('data-ref')); });
        row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showOrder(row.getAttribute('data-ref')); } });
      });
    })
    .catch(function() {
      document.getElementById('stats-loading').textContent = 'Failed to load stats.';
    });

  function showOrder(ref) {
    if (!ref) return;
    var modal = document.getElementById('order-modal');
    var detailEl = document.getElementById('order-detail');
    var loadingEl = document.getElementById('order-loading');
    document.getElementById('order-ref').textContent = ref;
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    detailEl.classList.add('hidden');
    loadingEl.classList.remove('hidden');
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
        detailEl.innerHTML =
          '<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Payment</h3>' +
              '<dl class="space-y-2 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Date</dt><dd class="text-white">' + (o.date || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Deposit paid</dt><dd class="font-semibold text-safety">' + (o.amount_paid || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Estimate total</dt><dd class="text-white">' + (o.estimate_total || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Balance due</dt><dd class="font-semibold text-safety">' + (bal || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Status</dt><dd class="text-white">' + (o.payment_status || '—') + '</dd></div>' +
              '</dl>' +
            '</div>' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Customer</h3>' +
              '<dl class="space-y-2 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Name</dt><dd class="text-white">' + (o.name || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Email</dt><dd class="text-white break-all">' + (o.email || '—') + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Phone</dt><dd class="text-white"><a href="tel:' + (o.phone||'').replace(/\D/g,'') + '" class="text-safety hover:underline">' + (o.phone || '—') + '</a></dd></div>' +
              '</dl>' +
            '</div>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Location</h3>' +
            '<p class="text-white font-medium">' + (o.postcode || '—') + '</p>' +
            (o.lat && o.lng ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(o.lat + ',' + o.lng) + '" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-safety text-sm mt-2 hover:underline">Open in Maps</a>' : '') +
          '</div>' +
          (o.session_id ? '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<a href="../verify.php?session_id=' + encodeURIComponent(o.session_id) + '" target="_blank" class="inline-flex items-center gap-2 text-safety text-sm font-medium hover:underline">View verify page (driver scan)</a>' +
          '</div>' : '') +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Vehicle</h3>' +
            '<dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">VRM</dt><dd class="text-white font-mono">' + (o.vrm || '—') + '</dd>' +
              '<dt class="text-zinc-500">Make</dt><dd class="text-white">' + (o.make || '—') + '</dd>' +
              '<dt class="text-zinc-500">Model</dt><dd class="text-white">' + (o.model || '—') + '</dd>' +
              '<dt class="text-zinc-500">Tyre size</dt><dd class="text-white">' + (o.tyre_size || '—') + '</dd>' +
              '<dt class="text-zinc-500">Wheels</dt><dd class="text-white">' + (o.wheels || '—') + '</dd>' +
            '</dl>' +
          '</div>';
      })
      .catch(function() {
        loadingEl.classList.add('hidden');
        detailEl.classList.remove('hidden');
        detailEl.innerHTML = '<p class="text-red-400">Failed to load order details.</p>';
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
