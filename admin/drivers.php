<?php
$pageTitle = 'Drivers & Vans';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Drivers & Vans</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-white">Drivers</h2>
      <button type="button" id="btn-add-driver" class="px-3 py-1.5 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] text-sm">+ Add driver</button>
    </div>
    <div id="drivers-list" class="space-y-3">
      <p class="text-zinc-500">Loading…</p>
    </div>

    <!-- Driver modal -->
    <div id="driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
      <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-8">
        <h3 id="driver-modal-title" class="text-lg font-bold text-white mb-4">Add driver</h3>
        <form id="driver-form" class="space-y-4">
          <input type="hidden" id="driver-id">
          <input type="hidden" id="driver-vehicleData" value="">

          <div class="space-y-3">
            <label for="driver-name" class="block text-sm font-medium text-zinc-300">Name *</label>
            <input type="text" id="driver-name" required class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
          </div>
          <div>
            <label for="driver-email" class="block text-sm font-medium text-zinc-300 mb-1">Email</label>
            <input type="email" id="driver-email" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="driver@example.com">
            <p class="text-zinc-500 text-xs mt-0.5">Required for login and Stripe Connect payouts</p>
          </div>
          <div>
            <label for="driver-phone" class="block text-sm font-medium text-zinc-300 mb-1">Phone</label>
            <input type="tel" id="driver-phone" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
          </div>
          <div>
            <label for="driver-password" class="block text-sm font-medium text-zinc-300 mb-1">Password</label>
            <input type="password" id="driver-password" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Leave blank to auto-generate">
            <p class="text-zinc-500 text-xs mt-0.5">For driver login. Min 8 chars. Blank = generate temporary</p>
          </div>

          <div class="border-t border-zinc-700 pt-4">
            <h4 class="text-sm font-semibold text-white mb-3">Van / vehicle – verify roadworthy</h4>
            <div class="flex gap-2 mb-2">
              <input type="text" id="driver-vanReg" class="flex-1 px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono" placeholder="e.g. EA65 AMX">
              <button type="button" id="driver-lookup-vrm" class="px-3 py-2 rounded-lg bg-zinc-600 text-zinc-200 text-sm whitespace-nowrap hover:bg-zinc-600/80">Look up</button>
            </div>
            <input type="text" id="driver-van" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none mb-2" placeholder="Make & model (auto-filled from lookup)">
            <div id="driver-vehicle-summary" class="hidden rounded-lg border border-zinc-600 bg-zinc-800/80 p-3 text-sm space-y-1">
              <div id="driver-vehicle-ok" class="hidden flex items-center gap-2 text-green-400"><svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Vehicle appears roadworthy</span></div>
              <div id="driver-vehicle-warn" class="hidden flex items-center gap-2 text-amber-400"><svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>Check MOT & tax – vehicle may not be suitable</span></div>
              <dl id="driver-vehicle-dl" class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-zinc-400"></dl>
            </div>
          </div>

          <div class="border-t border-zinc-700 pt-4">
            <h4 class="text-sm font-semibold text-white mb-3">KYC (identity & compliance)</h4>
            <div class="space-y-2">
              <label class="flex items-center gap-2"><input type="checkbox" id="kyc-right-to-work" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Right to work in UK confirmed</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="kyc-licence-verified" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Driving licence verified</span></label>
              <div>
                <input type="text" id="kyc-licence-number" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm" placeholder="Driving licence number">
              </div>
              <label class="flex items-center gap-2"><input type="checkbox" id="kyc-insurance-valid" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Insurance valid</span></label>
              <div>
                <label class="block text-zinc-500 text-xs mb-0.5">Insurance expiry (optional)</label>
                <input type="date" id="kyc-insurance-expiry" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm">
              </div>
              <div id="driver-insurance-uploaded" class="hidden rounded-lg border border-zinc-600 bg-zinc-800/80 p-3">
                <p class="text-zinc-400 text-sm mb-2">Driver uploaded insurance document</p>
                <a id="driver-insurance-view" href="#" target="_blank" class="inline-flex items-center gap-1 text-safety text-sm font-medium hover:underline">View document</a>
                <p id="driver-insurance-date" class="text-zinc-500 text-xs mt-1"></p>
              </div>
              <label class="flex items-center gap-2"><input type="checkbox" id="kyc-id-verified" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">ID (passport/ID) verified</span></label>
            </div>
          </div>

          <div class="border-t border-zinc-700 pt-4">
            <h4 class="text-sm font-semibold text-white mb-3">Equipment for emergency tyre</h4>
            <div class="space-y-2">
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-jack" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Jack</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-torque-wrench" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Torque wrench</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-compressor" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Tyre compressor / inflator</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-locking-nut" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Locking wheel nut key/set</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-pressure-gauge" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Tyre pressure gauge</span></label>
              <label class="flex items-center gap-2"><input type="checkbox" id="equip-chocks" class="rounded bg-zinc-700 border-zinc-600 text-safety focus:ring-safety"> <span class="text-zinc-300 text-sm">Wheel chocks</span></label>
              <div>
                <input type="text" id="equip-other" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm" placeholder="Other equipment">
              </div>
            </div>
          </div>

          <div>
            <label for="driver-rate" class="block text-sm font-medium text-zinc-300 mb-1">Driver rate (%)</label>
            <input type="number" id="driver-rate" min="1" max="100" value="80" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
            <p class="text-zinc-500 text-xs mt-0.5">Share of job value paid to driver (default 80%)</p>
          </div>
          <div>
            <label for="driver-notes" class="block text-sm font-medium text-zinc-300 mb-1">Notes</label>
            <textarea id="driver-notes" rows="2" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none"></textarea>
          </div>
          <div id="connect-link-section" class="border-t border-zinc-700 pt-4 hidden">
            <h4 class="text-sm font-semibold text-white mb-2">Stripe Connect (payouts)</h4>
            <p class="text-zinc-500 text-xs mb-2">Send this link to the driver to complete bank details & identity for payouts.</p>
            <div class="flex gap-2">
              <input type="text" id="connect-link-url" readonly class="flex-1 px-3 py-2 rounded-lg bg-zinc-800 border border-zinc-600 text-zinc-400 text-sm font-mono">
              <button type="button" id="btn-copy-connect-link" class="px-3 py-2 rounded-lg bg-zinc-600 text-zinc-200 text-sm">Copy</button>
              <button type="button" id="btn-get-connect-link" class="px-3 py-2 rounded-lg bg-safety text-zinc-900 font-medium text-sm">Get link</button>
            </div>
          </div>
          <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Save</button>
            <button type="button" id="driver-modal-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Block driver modal -->
    <div id="block-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
      <div class="w-full max-w-md rounded-2xl border border-zinc-700 bg-zinc-800 p-6">
        <h3 class="text-lg font-bold text-white mb-2">Block driver</h3>
        <p class="text-zinc-400 text-sm mb-4">They will not be able to log in or receive job assignments. Add a reason (optional but recommended):</p>
        <textarea id="block-reason" rows="3" class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none mb-4" placeholder="e.g. Failed to complete jobs, insurance expired..."></textarea>
        <div class="flex gap-2">
          <button type="button" id="block-confirm" class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg text-sm hover:bg-red-700">Block</button>
          <button type="button" id="block-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <div>
    <h2 class="text-lg font-semibold text-white mb-4">Jobs <span class="text-zinc-500 text-sm font-normal">(click for details)</span></h2>
    <div id="jobs-list" class="rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
      <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="sticky top-0 bg-zinc-800">
            <tr class="border-b border-zinc-700">
              <th class="text-left py-3 px-4 text-zinc-400 font-medium">Ref</th>
              <th class="text-left py-3 px-4 text-zinc-400 font-medium">Vehicle</th>
              <th class="text-left py-3 px-4 text-zinc-400 font-medium">Postcode</th>
              <th class="text-right py-3 px-4 text-zinc-400 font-medium">Amount</th>
            </tr>
          </thead>
          <tbody id="jobs-tbody">
            <tr><td colspan="4" class="py-8 text-center text-zinc-500">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Job detail modal -->
<div id="job-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
  <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-8">
    <div class="flex justify-between items-start mb-6">
      <h2 class="text-xl font-bold text-white">Job #<span id="job-modal-ref">—</span></h2>
      <button type="button" id="job-modal-close" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700 text-2xl leading-none">×</button>
    </div>
    <div id="job-modal-loading" class="text-zinc-500 py-8 text-center">Loading…</div>
    <div id="job-modal-content" class="hidden space-y-4"></div>
  </div>
</div>

<script>
(function() {
  var driversList = document.getElementById('drivers-list');
  var jobsTbody = document.getElementById('jobs-tbody');
  var modal = document.getElementById('driver-modal');
  var form = document.getElementById('driver-form');

  function loadDrivers() {
    fetch('api/drivers.php?action=all')
      .then(function(r) { return r.json(); })
      .then(function(drivers) {
        if (!Array.isArray(drivers)) { driversList.innerHTML = '<p class="text-red-400">Failed to load</p>'; return; }
        if (drivers.length === 0) {
          driversList.innerHTML = '<p class="text-zinc-500">No drivers. Click Add driver or they will appear after onboarding.</p>';
          return;
        }
        function escape(s) { if (!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        function vehicleRows(v) {
          if (!v || typeof v !== 'object') return '';
          var rows = [
            { label: 'Reg', value: v.registrationNumber, mono: true },
            { label: 'Make', value: v.make }, { label: 'Model', value: v.model },
            { label: 'Colour', value: v.colour }, { label: 'Fuel', value: v.fuelType },
            { label: 'Year', value: v.yearOfManufacture }, { label: 'Engine', value: v.engineCapacity ? v.engineCapacity + ' cc' : '' },
            { label: 'MOT status', value: v.mot && v.mot.motStatus }, { label: 'MOT due', value: v.mot && v.mot.motDueDate }, { label: 'MOT days', value: v.mot && v.mot.days != null ? v.mot.days + ' days' : '' },
            { label: 'Tax status', value: v.tax && v.tax.taxStatus }, { label: 'Tax due', value: v.tax && v.tax.taxDueDate },
            { label: 'CO₂', value: v.co2Emissions != null ? v.co2Emissions + ' g/km' : '' }, { label: 'V5C issued', value: v.dateOfLastV5CIssued }
          ];
          var html = '';
          rows.forEach(function(r) {
            if (r.value === undefined || r.value === null || r.value === '') return;
            html += '<dt class="text-zinc-500">' + escape(r.label) + '</dt><dd class="text-zinc-300' + (r.mono ? ' font-mono' : '') + '">' + escape(String(r.value)) + '</dd>';
          });
          return html;
        }
        function kycSummary(k, insuranceUrl) {
          if (!k || typeof k !== 'object') k = {};
          var items = [];
          if (k.rightToWork) items.push('Right to work');
          if (k.licenceVerified) items.push('Licence OK');
          if (k.licenceNumber) items.push('Lic: ' + escape(k.licenceNumber).substring(0,12) + (k.licenceNumber.length > 12 ? '…' : ''));
          if (k.insuranceValid) items.push('Insurance OK');
          if (insuranceUrl) items.push('<span class="text-green-400">Doc uploaded</span>');
          if (k.idVerified) items.push('ID verified');
          if (items.length === 0) return '';
          return '<div class="mt-2 pt-2 border-t border-zinc-700"><p class="text-zinc-500 text-xs mb-1">KYC</p><p class="text-zinc-400 text-xs">' + items.join(' · ') + '</p></div>';
        }
        function equipSummary(e) {
          if (!e || typeof e !== 'object') return '';
          var items = [];
          if (e.jack) items.push('Jack');
          if (e.torqueWrench) items.push('Torque');
          if (e.compressor) items.push('Compressor');
          if (e.lockingNut) items.push('Lock nut');
          if (e.pressureGauge) items.push('Gauge');
          if (e.chocks) items.push('Chocks');
          if (e.other) items.push(escape(e.other).substring(0, 20) + (e.other.length > 20 ? '…' : ''));
          if (items.length === 0) return '';
          return '<div class="mt-2 pt-2 border-t border-zinc-700"><p class="text-zinc-500 text-xs mb-1">Equipment</p><p class="text-zinc-400 text-xs">' + items.join(' · ') + '</p></div>';
        }
        driversList.innerHTML = drivers.map(function(d) {
          var phone = (d.phone||'').trim();
          var van = (d.van||'').trim();
          var vanReg = (d.vanReg||'').trim();
          var notes = (d.notes||'').trim();
          var v = d.vehicleData;
          var active = d.active !== false;
          var blacklisted = !!d.blacklisted;
          var blockedReason = (d.blocked_reason||'').trim();
          var driverRate = d.driver_rate != null ? d.driver_rate : 80;
          var source = d.source || 'admin';
          var statusBadge = blacklisted ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-red-900/60 text-red-300">Blocked</span>' : (active ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-300">Active</span>' : '<span class="px-2 py-0.5 rounded text-xs font-medium bg-zinc-700 text-zinc-400">Inactive</span>');
          var sourceBadge = source === 'connect' ? '<span class="px-2 py-0.5 rounded text-xs bg-safety/20 text-safety">Connect</span>' : '<span class="px-2 py-0.5 rounded text-xs bg-zinc-700 text-zinc-400">Admin</span>';
          var rateBadge = '<span class="px-2 py-0.5 rounded text-xs bg-zinc-700 text-zinc-400" title="Driver share of job value">' + driverRate + '%</span>';
          var blockedReasonHtml = blockedReason ? '<div class="mt-2 p-2 rounded bg-red-900/30 border border-red-800/50"><p class="text-zinc-500 text-xs mb-0.5">Block reason</p><p class="text-red-300 text-sm">' + escape(blockedReason) + '</p></div>' : '';
          var vehicleSection = v && typeof v === 'object' && (v.registrationNumber || v.make || v.model)
            ? '<div class="mt-3 pt-3 border-t border-zinc-700"><p class="text-zinc-500 text-xs font-medium mb-2">Vehicle details</p><dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' + vehicleRows(v) + '</dl></div>'
            : ('<dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' +
                '<dt class="text-zinc-500">Van</dt><dd class="text-zinc-300">' + escape(van||'—') + '</dd>' +
                '<dt class="text-zinc-500">Reg</dt><dd class="text-zinc-300 font-mono">' + escape(vanReg||'—') + '</dd>' +
              '</dl>');
          var statusBtns = (blacklisted ? '<button type="button" class="btn-unblock px-2 py-1 rounded bg-zinc-600 text-zinc-200 text-xs" data-id="' + (d.id||'') + '">Unblock</button>' : '<button type="button" class="btn-block px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + (d.id||'') + '">Block</button>') +
            (active ? '<button type="button" class="btn-deactivate px-2 py-1 rounded bg-zinc-600 text-zinc-200 text-xs" data-id="' + (d.id||'') + '">Deactivate</button>' : '<button type="button" class="btn-activate px-2 py-1 rounded bg-green-900/50 text-green-300 text-xs" data-id="' + (d.id||'') + '">Activate</button>');
          return '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4' + (blacklisted ? ' opacity-75 border-red-900/50' : '') + '" data-id="' + (d.id||'') + '">' +
            '<div class="flex justify-between items-start gap-3 mb-3">' +
              '<div class="flex flex-wrap items-center gap-2">' +
                '<p class="font-semibold text-white">' + escape(d.name||'—') + '</p>' +
                statusBadge +
                sourceBadge +
                rateBadge +
              '</div>' +
              '<div class="flex gap-1 shrink-0">' +
                (source === 'admin' ? '<button type="button" class="btn-edit-driver px-2 py-1 rounded bg-zinc-700 text-zinc-300 text-xs" data-id="' + (d.id||'') + '">Edit</button>' : '') +
                (source === 'admin' ? '<button type="button" class="btn-delete-driver px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + (d.id||'') + '">Delete</button>' : '') +
              '</div>' +
            '</div>' +
            '<div class="flex flex-wrap gap-1 mb-2">' + statusBtns + '</div>' +
            '<dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">Phone</dt><dd class="text-zinc-300">' + escape(phone||'—') + '</dd>' +
            '</dl>' +
            vehicleSection +
            blockedReasonHtml +
            kycSummary(d.kyc, d.insurance_url) +
            equipSummary(d.equipment) +
            (notes ? '<div class="mt-2 pt-2 border-t border-zinc-700"><p class="text-zinc-500 text-xs">Notes</p><p class="text-zinc-400 text-sm">' + escape(notes) + '</p></div>' : '') +
          '</div>';
        }).join('');
        driversList.querySelectorAll('.btn-edit-driver').forEach(function(b) {
          b.addEventListener('click', function() { openDriverModal(b.getAttribute('data-id')); });
        });
        driversList.querySelectorAll('.btn-delete-driver').forEach(function(b) {
          b.addEventListener('click', function() {
            if (confirm('Delete this driver?')) deleteDriver(b.getAttribute('data-id'));
          });
        });
        driversList.querySelectorAll('.btn-activate').forEach(function(b) {
          b.addEventListener('click', function() { setDriverStatus(b.getAttribute('data-id'), 'activate'); });
        });
        driversList.querySelectorAll('.btn-deactivate').forEach(function(b) {
          b.addEventListener('click', function() { setDriverStatus(b.getAttribute('data-id'), 'deactivate'); });
        });
        driversList.querySelectorAll('.btn-block').forEach(function(b) {
          b.addEventListener('click', function() { openBlockModal(b.getAttribute('data-id')); });
        });
        driversList.querySelectorAll('.btn-unblock').forEach(function(b) {
          b.addEventListener('click', function() { setDriverStatus(b.getAttribute('data-id'), 'unblock'); });
        });
      });
  }

  function loadJobs() {
    fetch('api/drivers.php?action=jobs')
      .then(function(r) { return r.json(); })
      .then(function(jobs) {
        if (!Array.isArray(jobs)) { jobsTbody.innerHTML = '<tr><td colspan="4" class="py-4 text-zinc-500">No jobs</td></tr>'; return; }
        if (jobs.length === 0) {
          jobsTbody.innerHTML = '<tr><td colspan="4" class="py-8 text-center text-zinc-500">No jobs yet</td></tr>';
          return;
        }
        jobsTbody.innerHTML = jobs.map(function(j) {
          var v = (j.make||'') + ' ' + (j.model||''); if (!v.trim()) v = j.vrm || '—'; else if (j.vrm) v += ' (' + j.vrm + ')';
          var ref = (j.reference||'').toString();
          return '<tr class="job-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer" data-ref="' + ref + '" role="button" tabindex="0">' +
            '<td class="py-3 px-4 font-mono text-safety">' + (j.reference||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-300">' + v + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + (j.postcode||'—') + '</td>' +
            '<td class="py-3 px-4 text-right font-semibold text-white">' + (j.amount_paid||j.estimate_total||'—') + '</td>' +
          '</tr>';
        }).join('');
        jobsTbody.querySelectorAll('.job-row').forEach(function(row) {
          row.addEventListener('click', function() { showJobDetail(row.getAttribute('data-ref')); });
          row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showJobDetail(row.getAttribute('data-ref')); } });
        });
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
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Vehicle</h3>' +
            '<dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">VRM</dt><dd class="text-white font-mono">' + esc(o.vrm) + '</dd>' +
              '<dt class="text-zinc-500">Make</dt><dd class="text-white">' + esc(o.make) + '</dd>' +
              '<dt class="text-zinc-500">Model</dt><dd class="text-white">' + esc(o.model) + '</dd>' +
              '<dt class="text-zinc-500">Tyre size</dt><dd class="text-white">' + esc(o.tyre_size) + '</dd>' +
              '<dt class="text-zinc-500">Wheels</dt><dd class="text-white">' + esc(o.wheels) + '</dd>' +
            '</dl>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Driver & times</h3>' +
            '<div class="space-y-1 text-sm">' + times.join('') + '</div>' +
          '</div>' +
          '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4">' +
            '<h3 class="text-sm font-semibold text-zinc-400 mb-3">Proof</h3>' +
            proofHtml +
          '</div>';
      })
      .catch(function() {
        loading.classList.add('hidden');
        content.classList.remove('hidden');
        content.innerHTML = '<p class="text-red-400">Failed to load job details.</p>';
      });
  }

  function openDriverModal(id) {
    document.getElementById('driver-modal-title').textContent = id ? 'Edit driver' : 'Add driver';
    document.getElementById('driver-id').value = id || '';
    document.getElementById('connect-link-url').value = '';
    document.getElementById('connect-link-section').classList.toggle('hidden', !id);
    if (id) {
      fetch('api/drivers.php?action=all').then(function(r) { return r.json(); }).then(function(drivers) {
        var d = Array.isArray(drivers) ? drivers.find(function(x) { return (x.id||'') === id; }) : null;
        if (d) {
          document.getElementById('driver-name').value = d.name || '';
          document.getElementById('driver-email').value = d.email || '';
          document.getElementById('driver-password').value = '';
          document.getElementById('driver-phone').value = d.phone || '';
          document.getElementById('driver-van').value = d.van || '';
          document.getElementById('driver-vanReg').value = d.vanReg || '';
          document.getElementById('driver-rate').value = d.driver_rate != null ? d.driver_rate : 80;
          document.getElementById('driver-notes').value = d.notes || '';
          document.getElementById('driver-vehicleData').value = (d.vehicleData && typeof d.vehicleData === 'object') ? JSON.stringify(d.vehicleData) : '';
          var kyc = d.kyc || {};
          document.getElementById('kyc-right-to-work').checked = !!kyc.rightToWork;
          document.getElementById('kyc-licence-verified').checked = !!kyc.licenceVerified;
          document.getElementById('kyc-licence-number').value = kyc.licenceNumber || '';
          document.getElementById('kyc-insurance-valid').checked = !!kyc.insuranceValid;
          document.getElementById('kyc-insurance-expiry').value = kyc.insuranceExpiry || '';
          document.getElementById('kyc-id-verified').checked = !!kyc.idVerified;
          var eq = d.equipment || {};
          document.getElementById('equip-jack').checked = !!eq.jack;
          document.getElementById('equip-torque-wrench').checked = !!eq.torqueWrench;
          document.getElementById('equip-compressor').checked = !!eq.compressor;
          document.getElementById('equip-locking-nut').checked = !!eq.lockingNut;
          document.getElementById('equip-pressure-gauge').checked = !!eq.pressureGauge;
          document.getElementById('equip-chocks').checked = !!eq.chocks;
          document.getElementById('equip-other').value = eq.other || '';
          if (d.vehicleData && typeof d.vehicleData === 'object') renderVehicleSummary(d.vehicleData);
          var insDiv = document.getElementById('driver-insurance-uploaded');
          var insLink = document.getElementById('driver-insurance-view');
          var insDate = document.getElementById('driver-insurance-date');
          if (d.insurance_url && insDiv && insLink) {
            insDiv.classList.remove('hidden');
            insLink.href = 'api/driver-insurance.php?id=' + encodeURIComponent(id);
            insDate.textContent = d.insurance_uploaded_at ? 'Uploaded ' + d.insurance_uploaded_at : '';
          } else if (insDiv) {
            insDiv.classList.add('hidden');
          }
        }
      });
    } else {
      form.reset();
      document.getElementById('driver-id').value = '';
      document.getElementById('driver-vehicleData').value = '';
      document.getElementById('driver-rate').value = 80;
      document.getElementById('driver-vehicle-summary').classList.add('hidden');
      document.getElementById('driver-insurance-uploaded').classList.add('hidden');
      document.getElementById('connect-link-section').classList.add('hidden');
    }
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
  }

  function closeDriverModal() {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }

  function deleteDriver(id) {
    fetch('api/drivers.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) loadDrivers(); else alert(d.error || 'Failed');
    });
  }

  function setDriverStatus(id, action, blockReason) {
    var payload = { driver_id: id, action: action };
    if (action === 'block' && blockReason != null) payload.block_reason = String(blockReason).trim();
    fetch('api/driver-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) loadDrivers(); else alert(d.error || 'Failed');
    });
  }

  var pendingBlockId = null;
  function openBlockModal(driverId) {
    pendingBlockId = driverId;
    document.getElementById('block-reason').value = '';
    var blockModal = document.getElementById('block-modal');
    blockModal.classList.remove('hidden');
    blockModal.style.display = 'flex';
  }
  function closeBlockModal() {
    pendingBlockId = null;
    var blockModal = document.getElementById('block-modal');
    blockModal.classList.add('hidden');
    blockModal.style.display = 'none';
  }
  document.getElementById('block-confirm').addEventListener('click', function() {
    if (pendingBlockId) {
      var reason = document.getElementById('block-reason').value;
      setDriverStatus(pendingBlockId, 'block', reason);
      closeBlockModal();
    }
  });
  document.getElementById('block-cancel').addEventListener('click', closeBlockModal);
  document.getElementById('block-modal').addEventListener('click', function(e) { if (e.target.id === 'block-modal') closeBlockModal(); });

  document.getElementById('btn-add-driver').addEventListener('click', function() { openDriverModal(); });
  document.getElementById('driver-modal-cancel').addEventListener('click', closeDriverModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeDriverModal(); });

  function closeJobModal() {
    var jobModal = document.getElementById('job-modal');
    jobModal.classList.add('hidden');
    jobModal.style.display = 'none';
  }
  document.getElementById('job-modal-close').addEventListener('click', closeJobModal);
  document.getElementById('job-modal').addEventListener('click', function(e) { if (e.target.id === 'job-modal') closeJobModal(); });

  function parseDate(dateStr) {
    if (!dateStr) return null;
    var s = String(dateStr).trim();
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    m = s.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (m) return new Date(parseInt(m[3],10), parseInt(m[2],10)-1, parseInt(m[1],10));
    return null;
  }
  function isExpiredOrSoon(dateStr, daysBuffer) {
    var d = parseDate(dateStr);
    if (!d) return false;
    var now = new Date();
    var diff = Math.ceil((d - now) / (1000*60*60*24));
    return diff < (daysBuffer || 0);
  }
  function isExpiredStatus(statusStr) {
    if (!statusStr) return false;
    var s = String(statusStr).toLowerCase();
    return /expired|not valid|no mot|untaxed|sorn|unlicensed|not licensed|fail/.test(s);
  }
  function renderVehicleSummary(data) {
    var panel = document.getElementById('driver-vehicle-summary');
    var dl = document.getElementById('driver-vehicle-dl');
    var ok = document.getElementById('driver-vehicle-ok');
    var warn = document.getElementById('driver-vehicle-warn');
    if (!data || !data.registrationNumber) { panel.classList.add('hidden'); return; }
    var rows = [
      { label: 'Year', value: data.yearOfManufacture },
      { label: 'Colour', value: data.colour },
      { label: 'Fuel', value: data.fuelType },
      { label: 'Engine', value: data.engineCapacity ? data.engineCapacity + ' cc' : '' },
      { label: 'MOT status', value: data.mot && data.mot.motStatus },
      { label: 'MOT due', value: data.mot && data.mot.motDueDate },
      { label: 'MOT days', value: data.mot && data.mot.days != null ? data.mot.days + ' days' : '' },
      { label: 'Tax status', value: data.tax && data.tax.taxStatus },
      { label: 'Tax due', value: data.tax && data.tax.taxDueDate },
      { label: 'V5C issued', value: data.dateOfLastV5CIssued }
    ];
    var html = '';
    rows.forEach(function(r) {
      if (r.value === undefined || r.value === null || r.value === '') return;
      html += '<dt class="text-zinc-500">' + (r.label||'').replace(/</g,'&lt;') + '</dt><dd class="text-zinc-300">' + String(r.value).replace(/</g,'&lt;') + '</dd>';
    });
    dl.innerHTML = html;
    var motExpired = isExpiredStatus(data.mot && data.mot.motStatus) || (data.mot && data.mot.motDueDate && isExpiredOrSoon(data.mot.motDueDate, 0));
    var taxExpired = isExpiredStatus(data.tax && data.tax.taxStatus) || (data.tax && data.tax.taxDueDate && isExpiredOrSoon(data.tax.taxDueDate, 0));
    if (motExpired || taxExpired) {
      ok.classList.add('hidden');
      warn.classList.remove('hidden');
    } else {
      ok.classList.remove('hidden');
      warn.classList.add('hidden');
    }
    panel.classList.remove('hidden');
  }

  document.getElementById('driver-lookup-vrm').addEventListener('click', function() {
    var vrm = document.getElementById('driver-vanReg').value.trim().replace(/\s+/g, '');
    if (!vrm || vrm.length < 2) { alert('Enter a registration to look up'); return; }
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Looking up…';
    fetch('../vehicle-check.php?vrm=' + encodeURIComponent(vrm))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.registrationNumber || data.make || data.model) {
          var make = (data.make || '').trim();
          var model = (data.model || '').trim();
          document.getElementById('driver-van').value = [make, model].filter(Boolean).join(' ') || document.getElementById('driver-van').value;
          document.getElementById('driver-vehicleData').value = JSON.stringify(data);
          renderVehicleSummary(data);
        } else {
          alert(data.error || 'Vehicle not found');
        }
      })
      .catch(function() { alert('Lookup failed'); })
      .finally(function() { btn.disabled = false; btn.textContent = 'Look up'; });
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var vd = document.getElementById('driver-vehicleData').value.trim();
    var payload = {
      action: 'save',
      id: document.getElementById('driver-id').value || undefined,
      name: document.getElementById('driver-name').value,
      email: document.getElementById('driver-email').value,
      password: document.getElementById('driver-password').value,
      phone: document.getElementById('driver-phone').value,
      van: document.getElementById('driver-van').value,
      vanReg: document.getElementById('driver-vanReg').value,
      driver_rate: parseInt(document.getElementById('driver-rate').value, 10) || 80,
      notes: document.getElementById('driver-notes').value,
      kyc: {
        rightToWork: document.getElementById('kyc-right-to-work').checked,
        licenceVerified: document.getElementById('kyc-licence-verified').checked,
        licenceNumber: document.getElementById('kyc-licence-number').value.trim(),
        insuranceValid: document.getElementById('kyc-insurance-valid').checked,
        insuranceExpiry: document.getElementById('kyc-insurance-expiry').value.trim(),
        idVerified: document.getElementById('kyc-id-verified').checked
      },
      equipment: {
        jack: document.getElementById('equip-jack').checked,
        torqueWrench: document.getElementById('equip-torque-wrench').checked,
        compressor: document.getElementById('equip-compressor').checked,
        lockingNut: document.getElementById('equip-locking-nut').checked,
        pressureGauge: document.getElementById('equip-pressure-gauge').checked,
        chocks: document.getElementById('equip-chocks').checked,
        other: document.getElementById('equip-other').value.trim()
      }
    };
    if (vd) {
      try { payload.vehicleData = JSON.parse(vd); } catch (e) {}
    } else {
      payload.vehicleData = null;
    }
    fetch('api/drivers.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) {
        if (d.temp_password) {
          alert('Driver saved. Temporary password: ' + d.temp_password + '\nShare this with the driver for first login.');
        }
        closeDriverModal();
        loadDrivers();
      } else {
        alert(d.error || 'Failed');
      }
    });
  });

  document.getElementById('btn-get-connect-link').addEventListener('click', function() {
    var id = document.getElementById('driver-id').value;
    if (!id) { alert('Save the driver first.'); return; }
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Loading…';
    fetch('api/connect-link.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ driver_id: id })
    }).then(function(r) { return r.json(); }).then(function(d) {
      btn.disabled = false;
      btn.textContent = 'Get link';
      if (d.url) {
        document.getElementById('connect-link-url').value = d.url;
        document.getElementById('connect-link-section').classList.remove('hidden');
        if (d.temp_password) alert('Temporary login password: ' + d.temp_password + '\nShare with the driver.');
      } else {
        alert(d.error || 'Failed');
      }
    }).catch(function() { btn.disabled = false; btn.textContent = 'Get link'; alert('Network error'); });
  });

  document.getElementById('btn-copy-connect-link').addEventListener('click', function() {
    var inp = document.getElementById('connect-link-url');
    if (inp.value) {
      inp.select();
      document.execCommand('copy');
      this.textContent = 'Copied';
      var t = this;
      setTimeout(function() { t.textContent = 'Copy'; }, 1500);
    }
  });

  loadDrivers();
  loadJobs();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
