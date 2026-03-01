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
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700">Recent deposits</h2>
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
        return '<tr class="border-b border-zinc-700/50 hover:bg-zinc-800/50">' +
          '<td class="py-3 px-4 text-zinc-300">' + (d.date || '—') + '</td>' +
          '<td class="py-3 px-4 font-mono text-safety">' + (d.reference || '—') + '</td>' +
          '<td class="py-3 px-4 text-zinc-300">' + (d.email || '—') + '</td>' +
          '<td class="py-3 px-4 text-zinc-400">' + (d.postcode || '—') + '</td>' +
          '<td class="py-3 px-4 text-right font-semibold text-white">' + (d.amount_paid || '—') + '</td>' +
          '<td class="py-3 px-4 text-right text-zinc-400">' + (d.estimate_total || '—') + '</td>' +
        '</tr>';
      }).join('');
    })
    .catch(function() {
      document.getElementById('stats-loading').textContent = 'Failed to load stats.';
    });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
