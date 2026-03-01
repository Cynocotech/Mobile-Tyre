<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<h1 class="text-2xl font-bold text-white mb-8">Reports</h1>

<div id="reports-loading" class="text-zinc-500">Loading…</div>
<div id="reports-content" class="hidden space-y-8">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <p class="text-zinc-400">Revenue and deposit summary. Export to PDF for records.</p>
    <button type="button" id="export-pdf-btn" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] text-sm inline-flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Export PDF
    </button>
  </div>

  <div id="report-stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Total revenue</p>
      <p id="stat-total-revenue" class="text-2xl font-bold text-safety mt-1">£0.00</p>
      <p class="text-zinc-400 text-xs mt-1"><span id="stat-deposit-count">0</span> deposits paid</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Last 7 days</p>
      <p id="stat-last7-revenue" class="text-2xl font-bold text-white mt-1">£0.00</p>
      <p class="text-zinc-400 text-xs mt-1"><span id="stat-last7-count">0</span> deposits</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Last 30 days</p>
      <p id="stat-last30-revenue" class="text-2xl font-bold text-white mt-1">£0.00</p>
      <p class="text-zinc-400 text-xs mt-1"><span id="stat-last30-count">0</span> deposits</p>
    </div>
    <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
      <p class="text-zinc-500 text-sm font-medium">Jobs & quotes</p>
      <p id="stat-jobs" class="text-xl font-bold text-white mt-1">0 jobs</p>
      <p class="text-zinc-400 text-xs mt-1"><span id="stat-quotes">0</span> quote requests</p>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
    <h2 class="text-lg font-semibold text-white px-6 py-4 border-b border-zinc-700">Recent deposits</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm" id="report-deposits-table">
        <thead>
          <tr class="border-b border-zinc-700">
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Date</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Reference</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Customer</th>
            <th class="text-left py-3 px-4 text-zinc-400 font-medium">Postcode</th>
            <th class="text-right py-3 px-4 text-zinc-400 font-medium">Amount</th>
            <th class="text-right py-3 px-4 text-zinc-400 font-medium">Est. total</th>
          </tr>
        </thead>
        <tbody id="report-deposits-body">
          <tr><td colspan="6" class="py-8 text-center text-zinc-500">No deposits</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Hidden report content for PDF export -->
<div id="report-pdf-content" class="hidden" style="background:#fff;color:#18181b;padding:24px;font-family:system-ui,sans-serif;font-size:14px;">
  <div style="margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #18181b;">
    <h1 style="font-size:20px;font-weight:bold;margin:0;">No 5 Tyre & MOT – Revenue Report</h1>
    <p style="margin:4px 0 0 0;color:#71717a;">Generated: <span id="pdf-generated-at">—</span></p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
    <div style="padding:16px;border:1px solid #e4e4e7;border-radius:8px;">
      <p style="font-size:11px;text-transform:uppercase;color:#71717a;margin:0 0 4px 0;">Total revenue</p>
      <p id="pdf-total-revenue" style="font-size:24px;font-weight:bold;margin:0;color:#18181b;">£0.00</p>
      <p style="font-size:12px;color:#71717a;margin:4px 0 0 0;"><span id="pdf-deposit-count">0</span> deposits</p>
    </div>
    <div style="padding:16px;border:1px solid #e4e4e7;border-radius:8px;">
      <p style="font-size:11px;text-transform:uppercase;color:#71717a;margin:0 0 4px 0;">Last 7 days</p>
      <p id="pdf-last7-revenue" style="font-size:24px;font-weight:bold;margin:0;color:#18181b;">£0.00</p>
      <p style="font-size:12px;color:#71717a;margin:4px 0 0 0;"><span id="pdf-last7-count">0</span> deposits</p>
    </div>
  </div>
  <div style="margin-bottom:16px;">
    <h2 style="font-size:14px;font-weight:600;margin:0 0 8px 0;">Recent deposits</h2>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
      <thead>
        <tr style="border-bottom:1px solid #e4e4e7;">
          <th style="text-align:left;padding:8px 0;color:#71717a;">Date</th>
          <th style="text-align:left;padding:8px 0;color:#71717a;">Ref</th>
          <th style="text-align:left;padding:8px 0;color:#71717a;">Customer</th>
          <th style="text-align:left;padding:8px 0;color:#71717a;">Postcode</th>
          <th style="text-align:right;padding:8px 0;color:#71717a;">Amount</th>
        </tr>
      </thead>
      <tbody id="pdf-deposits-body"></tbody>
    </table>
  </div>
</div>

<script>
(function() {
  var reportData = null;

  function escape(s) {
    if (s == null || s === '') return '—';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function loadReports() {
    fetch('api/reports.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        reportData = data;
        document.getElementById('reports-loading').classList.add('hidden');
        document.getElementById('reports-content').classList.remove('hidden');

        document.getElementById('stat-total-revenue').textContent = '£' + (data.totalRevenue || 0).toFixed(2);
        document.getElementById('stat-deposit-count').textContent = data.depositCount || 0;
        document.getElementById('stat-last7-revenue').textContent = '£' + (data.last7Revenue || 0).toFixed(2);
        document.getElementById('stat-last7-count').textContent = data.last7Count || 0;
        document.getElementById('stat-last30-revenue').textContent = '£' + (data.last30Revenue || 0).toFixed(2);
        document.getElementById('stat-last30-count').textContent = data.last30Count || 0;
        document.getElementById('stat-jobs').textContent = (data.jobsCount || 0) + ' jobs';
        document.getElementById('stat-quotes').textContent = data.quotesCount || 0;

        var tbody = document.getElementById('report-deposits-body');
        var rows = data.recentDeposits || [];
        if (rows.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-zinc-500">No deposits</td></tr>';
        } else {
          tbody.innerHTML = rows.map(function(d) {
            return '<tr class="border-b border-zinc-700/50">' +
              '<td class="py-3 px-4 text-zinc-300">' + escape(d.date) + '</td>' +
              '<td class="py-3 px-4 font-mono text-safety">' + escape(d.reference) + '</td>' +
              '<td class="py-3 px-4 text-zinc-300">' + escape(d.name || d.email) + '</td>' +
              '<td class="py-3 px-4 text-zinc-400">' + escape(d.postcode) + '</td>' +
              '<td class="py-3 px-4 text-right font-semibold text-white">' + escape(d.amount_paid) + '</td>' +
              '<td class="py-3 px-4 text-right text-zinc-400">' + escape(d.estimate_total) + '</td>' +
            '</tr>';
          }).join('');
        }
      })
      .catch(function() {
        document.getElementById('reports-loading').textContent = 'Failed to load reports.';
      });
  }

  document.getElementById('export-pdf-btn').addEventListener('click', function() {
    if (!reportData) return;
    var btn = this;
    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Generating…';

    document.getElementById('pdf-generated-at').textContent = reportData.generatedAt || new Date().toLocaleString();
    document.getElementById('pdf-total-revenue').textContent = '£' + (reportData.totalRevenue || 0).toFixed(2);
    document.getElementById('pdf-deposit-count').textContent = reportData.depositCount || 0;
    document.getElementById('pdf-last7-revenue').textContent = '£' + (reportData.last7Revenue || 0).toFixed(2);
    document.getElementById('pdf-last7-count').textContent = reportData.last7Count || 0;

    var pdfTbody = document.getElementById('pdf-deposits-body');
    var rows = reportData.recentDeposits || [];
    pdfTbody.innerHTML = rows.slice(0, 30).map(function(d) {
      return '<tr style="border-bottom:1px solid #e4e4e7">' +
        '<td style="padding:6px 0">' + escape(d.date) + '</td>' +
        '<td style="padding:6px 0;font-family:monospace">' + escape(d.reference) + '</td>' +
        '<td style="padding:6px 0">' + escape(d.name || d.email) + '</td>' +
        '<td style="padding:6px 0">' + escape(d.postcode) + '</td>' +
        '<td style="padding:6px 0;text-align:right;font-weight:600">' + escape(d.amount_paid) + '</td>' +
      '</tr>';
    }).join('');
    if (rows.length === 0) {
      pdfTbody.innerHTML = '<tr><td colspan="5" style="padding:12px;text-align:center;color:#71717a">No deposits</td></tr>';
    }

    var pdfEl = document.getElementById('report-pdf-content');
    pdfEl.classList.remove('hidden');
    pdfEl.style.position = 'absolute';
    pdfEl.style.left = '-9999px';
    pdfEl.style.width = '210mm';

    var opt = {
      margin: [10, 10],
      filename: 'revenue-report-' + (reportData.generatedAt ? reportData.generatedAt.replace(/[^0-9]/g, '').substring(0, 8) : new Date().toISOString().slice(0, 10).replace(/-/g, '')) + '.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(pdfEl).save().then(function() {
      pdfEl.classList.add('hidden');
      pdfEl.style.position = '';
      pdfEl.style.left = '';
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }).catch(function() {
      pdfEl.classList.add('hidden');
      pdfEl.style.position = '';
      btn.disabled = false;
      btn.innerHTML = origHtml;
    });
  });

  loadReports();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
