<?php
$pageTitle = 'Jobs';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Jobs</h1>
<p class="text-zinc-500 mb-6">All jobs. Click a row to view details and assign drivers.</p>

<div class="w-full rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-zinc-800">
        <tr class="border-b border-zinc-700">
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Ref</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Date</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Vehicle</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Postcode</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Driver</th>
          <th class="text-right py-4 px-4 text-zinc-400 font-medium">Amount</th>
        </tr>
      </thead>
      <tbody id="jobs-tbody">
        <tr><td colspan="6" class="py-12 text-center text-zinc-500">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Job detail modal -->
<div id="job-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
  <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-8">
    <div class="flex justify-between items-start mb-6">
      <h2 class="text-xl font-bold text-white">Job #<span id="job-modal-ref">—</span></h2>
      <button type="button" id="job-modal-close" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700">×</button>
    </div>
    <div id="job-modal-loading" class="text-zinc-500 py-8 text-center">Loading…</div>
    <div id="job-modal-content" class="hidden"></div>
  </div>
</div>

<script>
(function() {
  var jobsTbody = document.getElementById('jobs-tbody');

  function loadJobs() {
    fetch('api/drivers.php?action=jobs')
      .then(function(r) { return r.json(); })
      .then(function(jobs) {
        if (!Array.isArray(jobs)) { jobsTbody.innerHTML = '<tr><td colspan="6" class="py-8 text-zinc-500">No jobs</td></tr>'; return; }
        if (jobs.length === 0) {
          jobsTbody.innerHTML = '<tr><td colspan="6" class="py-12 text-center text-zinc-500">No jobs yet</td></tr>';
          return;
        }
        jobsTbody.innerHTML = jobs.map(function(j) {
          var v = (j.make||'') + ' ' + (j.model||''); if (!v.trim()) v = j.vrm || '—'; else if (j.vrm) v += ' (' + j.vrm + ')';
          var ref = (j.reference||'').toString();
          var driver = (j.assigned_driver_name || '—');
          return '<tr class="job-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer" data-ref="' + ref + '" role="button" tabindex="0">' +
            '<td class="py-3 px-4 font-mono text-safety font-semibold">' + (j.reference||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + (j.date||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-300">' + (v.length > 40 ? v.substring(0,37)+'…' : v) + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + (j.postcode||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + driver + '</td>' +
            '<td class="py-3 px-4 text-right font-semibold text-white">' + (j.amount_paid||j.estimate_total||'—') + '</td>' +
          '</tr>';
        }).join('');
        jobsTbody.querySelectorAll('.job-row').forEach(function(row) {
          row.addEventListener('click', function() { showJobDetail(row.getAttribute('data-ref')); });
          row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showJobDetail(row.getAttribute('data-ref')); } });
        });
      })
      .catch(function() {
        jobsTbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-red-400">Failed to load jobs.</td></tr>';
      });
  }

  function showJobDetail(ref) {
    if (!ref) return;
    var modal = document.getElementById('job-modal');
    var content = document.getElementById('job-modal-content');
    var loading = document.getElementById('job-modal-loading');
    document.getElementById('job-modal-ref').textContent = ref;
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    content.classList.add('hidden');
    loading.classList.remove('hidden');
    fetch('api/order.php?ref=' + encodeURIComponent(ref))
      .then(function(r) { return r.json(); })
      .then(function(o) {
        loading.classList.add('hidden');
        content.classList.remove('hidden');
        var esc = function(s) { if (s == null || s === '') return '—'; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); };
        var proofHtml = o.proof_url
          ? '<div class="rounded-lg border border-zinc-600 overflow-hidden"><a href="api/proof.php?ref=' + encodeURIComponent(ref) + '" target="_blank" class="block"><img src="api/proof.php?ref=' + encodeURIComponent(ref) + '" alt="Proof" class="w-full max-h-64 object-contain bg-zinc-900"></a><p class="text-zinc-500 text-xs p-2">Completion proof' + (o.proof_uploaded_at ? ' – ' + esc(o.proof_uploaded_at) : '') + '</p></div>'
          : '<p class="text-zinc-500 text-sm">No proof uploaded yet</p>';
        var times = [];
        times.push('<div class="flex justify-between py-1"><dt class="text-zinc-500">Assigned driver</dt><dd class="text-white">' + esc(o.assigned_driver_name || 'Not assigned') + (o.assigned_at ? ' <span class="text-zinc-500 text-xs">(' + esc(o.assigned_at) + ')</span>' : '') + '</dd></div>');
        if (o.job_started_at) times.push('<div class="flex justify-between py-1"><dt class="text-zinc-500">Start time</dt><dd class="text-white">' + esc(o.job_started_at) + '</dd></div>');
        if (o.proof_uploaded_at || o.cash_paid_at || o.job_completed_at) times.push('<div class="flex justify-between py-1"><dt class="text-zinc-500">End time</dt><dd class="text-white">' + esc(o.proof_uploaded_at || o.cash_paid_at || o.job_completed_at) + '</dd></div>');
        content.innerHTML =
          '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Customer</h3>' +
              '<dl class="space-y-1 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Name</dt><dd class="text-white">' + esc(o.name) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Email</dt><dd class="text-white break-all">' + esc(o.email) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Phone</dt><dd class="text-white"><a href="tel:' + (o.phone||'').replace(/\D/g,'') + '" class="text-safety hover:underline">' + esc(o.phone) + '</a></dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Postcode</dt><dd class="text-white">' + esc(o.postcode) + '</dd></div>' +
              '</dl>' +
            '</div>' +
            '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
              '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Payment</h3>' +
              '<dl class="space-y-1 text-sm">' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Deposit</dt><dd class="font-semibold text-safety">' + esc(o.amount_paid) + '</dd></div>' +
                '<div class="flex justify-between"><dt class="text-zinc-500">Est. total</dt><dd class="text-white">' + esc(o.estimate_total) + '</dd></div>' +
                (o.payment_method === 'cash' ? '<div class="flex justify-between"><dt class="text-zinc-500">Payment</dt><dd class="text-amber-400">Cash' + (o.cash_paid_at ? ' at ' + esc(o.cash_paid_at) : '') + '</dd></div>' : '') +
              '</dl>' +
            '</div>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4 mt-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Vehicle</h3>' +
            '<dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">VRM</dt><dd class="text-white font-mono">' + esc(o.vrm) + '</dd>' +
              '<dt class="text-zinc-500">Make</dt><dd class="text-white">' + esc(o.make) + '</dd>' +
              '<dt class="text-zinc-500">Model</dt><dd class="text-white">' + esc(o.model) + '</dd>' +
              '<dt class="text-zinc-500">Tyre size</dt><dd class="text-white">' + esc(o.tyre_size) + '</dd>' +
              '<dt class="text-zinc-500">Wheels</dt><dd class="text-white">' + esc(o.wheels) + '</dd>' +
            '</dl>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4 mt-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Driver & times</h3>' +
            '<div class="space-y-2 text-sm">' + times.join('') +
            '<div class="flex flex-wrap items-center gap-2 pt-2 border-t border-zinc-600 mt-2">' +
            '<select id="assign-driver-select" class="px-3 py-1.5 rounded bg-zinc-700 border border-zinc-600 text-white text-sm">' +
            '<option value="">Assign driver…</option></select>' +
            '<button type="button" id="assign-driver-btn" class="px-3 py-1.5 rounded bg-safety text-zinc-900 text-sm font-medium">Assign</button></div></div>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4 mt-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Proof</h3>' +
            proofHtml +
          '</div>';
      })
      .then(function(o) {
        if (o) loadDriversForAssign(ref, o.assigned_driver_id);
      })
      .catch(function() {
        loading.classList.add('hidden');
        content.classList.remove('hidden');
        content.innerHTML = '<p class="text-red-400">Failed to load job details.</p>';
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
        if (!did) { alert('Select a driver first.'); return; }
        btn.disabled = true;
        btn.textContent = 'Assigning…';
        fetch('api/assign-driver.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ reference: ref, driver_id: did })
        }).then(function(r) { return r.json(); }).then(function(res) {
          btn.disabled = false;
          btn.textContent = 'Assign';
          if (res.ok) showJobDetail(ref);
          else alert(res.error || 'Failed to assign driver');
        }).catch(function() {
          btn.disabled = false;
          btn.textContent = 'Assign';
          alert('Network error. Try again.');
        });
      };
    });
  }

  function closeJobModal() {
    var jobModal = document.getElementById('job-modal');
    jobModal.classList.add('hidden');
    jobModal.style.display = 'none';
  }
  document.getElementById('job-modal-close').addEventListener('click', closeJobModal);
  document.getElementById('job-modal').addEventListener('click', function(e) { if (e.target.id === 'job-modal') closeJobModal(); });

  loadJobs();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
