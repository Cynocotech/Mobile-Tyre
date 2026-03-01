<?php
$pageTitle = 'Drivers';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Drivers & Vans</h1>

<div class="flex justify-between items-center mb-6">
  <p class="text-zinc-500">Manage drivers and their vehicles. Click a row to view details or Edit to update.</p>
  <button type="button" id="btn-add-driver" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] text-sm">+ Add driver</button>
</div>
<div class="w-full rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-zinc-800">
        <tr class="border-b border-zinc-700">
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Name</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Phone</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Van / Reg</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Status</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Source</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Blocked</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Rate</th>
          <th class="text-right py-4 px-4 text-zinc-400 font-medium">Actions</th>
        </tr>
      </thead>
      <tbody id="drivers-list">
        <tr><td colspan="8" class="py-12 text-center text-zinc-500">Loading…</td></tr>
      </tbody>
    </table>
  </div>
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
            <div id="driver-vehicle-summary" class="rounded-lg border border-zinc-600 bg-zinc-800/80 p-3 text-sm space-y-1">
              <div id="driver-vehicle-ok" class="hidden flex items-center gap-2 text-green-400"><svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Vehicle appears roadworthy</span></div>
              <div id="driver-vehicle-warn" class="hidden flex items-center gap-2 text-amber-400"><svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>Check MOT & tax – vehicle may not be suitable</span></div>
              <dl id="driver-vehicle-dl" class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-zinc-400"></dl>
              <p id="driver-vehicle-placeholder" class="text-zinc-500 text-sm">No vehicle details yet. Use Look up for full DVLA data.</p>
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
          <div id="driver-block-history-section" class="border-t border-zinc-700 pt-4 hidden">
            <h4 class="text-sm font-semibold text-white mb-2">Block history</h4>
            <p class="text-zinc-500 text-xs mb-2">This account has been blocked <strong id="driver-block-count" class="text-white">0</strong> time(s).</p>
            <div id="driver-block-history-list" class="rounded-lg border border-zinc-600 bg-zinc-800/80 p-3 space-y-2 max-h-40 overflow-y-auto"></div>
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

<!-- Message driver modal -->
<div id="message-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
  <div class="w-full max-w-lg rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-8">
    <h3 class="text-lg font-bold text-white mb-1">Message driver</h3>
    <p id="message-driver-name" class="text-zinc-400 text-sm mb-4">—</p>
    <input type="hidden" id="message-driver-id">
    <div id="message-history" class="rounded-lg border border-zinc-600 bg-zinc-800/80 p-4 mb-4 max-h-48 overflow-y-auto space-y-3 text-sm">
      <p class="text-zinc-500 text-center">Loading…</p>
    </div>
    <div class="space-y-2">
      <label for="message-body" class="block text-sm text-zinc-400">New message</label>
      <textarea id="message-body" rows="4" class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none placeholder-zinc-500" placeholder="Type your message to the driver..."></textarea>
    </div>
    <div class="flex gap-2 mt-4">
      <button type="button" id="message-send" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm hover:bg-[#e5c900]">Send</button>
      <button type="button" id="message-close" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Close</button>
    </div>
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

<script>
(function() {
  var driversList = document.getElementById('drivers-list');
  var modal = document.getElementById('driver-modal');
  var form = document.getElementById('driver-form');

  function loadDrivers() {
    Promise.all([
      fetch('api/drivers.php?action=all').then(function(r) { return r.json(); }),
      fetch('api/driver-messages.php?counts=1').then(function(r) { return r.json(); }).then(function(d) { return d.counts || {}; }).catch(function() { return {}; })
    ]).then(function(results) {
      var drivers = results[0];
      var unreadCounts = results[1];
        if (!Array.isArray(drivers)) { driversList.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-red-400">Failed to load</td></tr>'; return; }
        if (drivers.length === 0) {
          driversList.innerHTML = '<tr><td colspan="8" class="py-12 text-center text-zinc-500">No drivers. Click Add driver or they will appear after onboarding.</td></tr>';
          return;
        }
        function escape(s) { if (s == null || s === '') return ''; var x = String(s); return x.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
        driversList.innerHTML = drivers.map(function(d) {
          var phone = (d.phone||'').trim();
          var van = (d.van||'').trim();
          var vanReg = (d.vanReg||'').trim();
          var v = d.vehicleData;
          var active = d.active !== false;
          var blacklisted = !!d.blacklisted;
          var driverRate = d.driver_rate != null ? d.driver_rate : 80;
          var source = d.source || 'admin';
          var blockCount = d.block_count != null ? d.block_count : 0;
          var vanDisplay = (van || '').trim();
          var regDisplay = (vanReg || '').trim();
          if (v && typeof v === 'object' && (v.registrationNumber || v.make || v.model)) {
            if (!vanDisplay && (v.make || v.model)) vanDisplay = [v.make, v.model].filter(Boolean).join(' ');
            if (!regDisplay && v.registrationNumber) regDisplay = v.registrationNumber;
          }
          var vanRegStr = [vanDisplay, regDisplay].filter(Boolean).join(' / ');
          if (!vanRegStr) vanRegStr = '—';
          var statusBadge = blacklisted ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-red-900/60 text-red-300">Blocked</span>' : (active ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-300">Active</span>' : '<span class="px-2 py-0.5 rounded text-xs font-medium bg-zinc-700 text-zinc-400">Inactive</span>');
          var sourceBadge = source === 'connect' ? '<span class="px-2 py-0.5 rounded text-xs bg-safety/20 text-safety">Connect</span>' : '<span class="px-2 py-0.5 rounded text-xs bg-zinc-700 text-zinc-400">Admin</span>';
          var blockCountDisplay = blockCount > 0 ? '<span class="font-medium' + (blacklisted ? ' text-red-300' : ' text-zinc-400') + '">' + blockCount + '</span>' : '<span class="text-zinc-500">0</span>';
          var unread = unreadCounts[d.id] || 0;
          var msgBtn = '<button type="button" class="btn-message px-2 py-1 rounded bg-safety/20 text-safety text-xs relative inline-flex items-center gap-1" data-id="' + escape(d.id||'') + '" data-name="' + escape(d.name||'') + '">Message' + (unread > 0 ? '<span class="ml-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">' + (unread > 99 ? '99+' : unread) + '</span>' : '') + '</button>';
          var actionBtns = '<div class="flex flex-wrap gap-1 justify-end">' +
            msgBtn +
            '<button type="button" class="btn-edit-driver px-2 py-1 rounded bg-zinc-700 text-zinc-300 text-xs" data-id="' + escape(d.id||'') + '">Edit</button>' +
            (source === 'admin' ? '<button type="button" class="btn-delete-driver px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + escape(d.id||'') + '">Delete</button>' : '') +
            (blacklisted ? '<button type="button" class="btn-unblock px-2 py-1 rounded bg-zinc-600 text-zinc-200 text-xs" data-id="' + escape(d.id||'') + '">Unblock</button>' : '<button type="button" class="btn-block px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + escape(d.id||'') + '">Block</button>') +
            (active ? '<button type="button" class="btn-deactivate px-2 py-1 rounded bg-zinc-600 text-zinc-200 text-xs" data-id="' + escape(d.id||'') + '">Deactivate</button>' : '<button type="button" class="btn-activate px-2 py-1 rounded bg-green-900/50 text-green-300 text-xs" data-id="' + escape(d.id||'') + '">Activate</button>') +
          '</div>';
          return '<tr class="driver-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer' + (blacklisted ? ' opacity-80' : '') + '" data-id="' + escape(d.id||'') + '" role="button" tabindex="0">' +
            '<td class="py-3 px-4 font-semibold text-white">' + escape(d.name||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + escape(phone||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-300 font-mono text-xs">' + escape(vanRegStr.length > 24 ? vanRegStr.substring(0,21)+'…' : vanRegStr) + '</td>' +
            '<td class="py-3 px-4">' + statusBadge + '</td>' +
            '<td class="py-3 px-4">' + sourceBadge + '</td>' +
            '<td class="py-3 px-4">' + blockCountDisplay + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + driverRate + '%</td>' +
            '<td class="py-3 px-4 text-right" onclick="event.stopPropagation()">' + actionBtns + '</td>' +
          '</tr>';
        }).join('');
        driversList.querySelectorAll('.driver-row').forEach(function(row) {
          row.addEventListener('click', function(e) {
            if (e.target.closest('button')) return;
            openDriverModal(row.getAttribute('data-id'));
          });
          row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDriverModal(row.getAttribute('data-id')); } });
        });
        driversList.querySelectorAll('.btn-edit-driver').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); openDriverModal(b.getAttribute('data-id')); });
        });
        driversList.querySelectorAll('.btn-delete-driver').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); if (confirm('Delete this driver?')) deleteDriver(b.getAttribute('data-id')); });
        });
        driversList.querySelectorAll('.btn-activate').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); setDriverStatus(b.getAttribute('data-id'), 'activate'); });
        });
        driversList.querySelectorAll('.btn-deactivate').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); setDriverStatus(b.getAttribute('data-id'), 'deactivate'); });
        });
        driversList.querySelectorAll('.btn-block').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); openBlockModal(b.getAttribute('data-id')); });
        });
        driversList.querySelectorAll('.btn-unblock').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); setDriverStatus(b.getAttribute('data-id'), 'unblock'); });
        });
        driversList.querySelectorAll('.btn-message').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); openMessageModal(b.getAttribute('data-id'), b.getAttribute('data-name')); });
        });
    })
    .catch(function() {
      driversList.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-red-400">Failed to load drivers.</td></tr>';
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
          renderVehicleDetails(d.vehicleData, d.van || '', d.vanReg || '');
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
          var blockHist = document.getElementById('driver-block-history-section');
          var blockCountEl = document.getElementById('driver-block-count');
          var blockListEl = document.getElementById('driver-block-history-list');
          if (blockHist && blockCountEl && blockListEl) {
            var hist = Array.isArray(d.block_history) ? d.block_history : [];
            var cnt = d.block_count != null ? d.block_count : hist.length;
            blockCountEl.textContent = cnt;
            if (hist.length > 0) {
              blockHist.classList.remove('hidden');
              blockListEl.innerHTML = hist.slice().reverse().map(function(h) {
                var reason = (h.reason || '').trim() || 'No reason given';
                var at = h.blocked_at || '';
                return '<div class="rounded bg-zinc-700/50 p-2 text-sm"><p class="text-red-300">' + String(reason).replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</p>' + (at ? '<p class="text-zinc-500 text-xs mt-0.5">' + String(at).replace(/</g,'&lt;') + '</p>' : '') + '</div>';
              }).join('');
            } else {
              blockHist.classList.remove('hidden');
              blockListEl.innerHTML = cnt > 0 ? '<p class="text-zinc-500 text-sm">No detailed history (blocked before tracking was added)</p>' : '<p class="text-zinc-500 text-sm">Never blocked</p>';
            }
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
      var blockHist = document.getElementById('driver-block-history-section');
      if (blockHist) blockHist.classList.add('hidden');
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

  function escMsg(s) { if (s == null || s === '') return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function openMessageModal(driverId, driverName) {
    document.getElementById('message-driver-id').value = driverId || '';
    document.getElementById('message-driver-name').textContent = driverName || driverId || '—';
    document.getElementById('message-body').value = '';
    var hist = document.getElementById('message-history');
    hist.innerHTML = '<p class="text-zinc-500 text-center">Loading…</p>';
    var msgModal = document.getElementById('message-modal');
    msgModal.classList.remove('hidden');
    msgModal.style.display = 'flex';
    if (driverId) {
      fetch('api/driver-messages.php?driver_id=' + encodeURIComponent(driverId))
        .then(function(r) { return r.json(); })
        .then(function(d) {
          var msgs = (d.messages || []).slice(0, 20);
          if (msgs.length === 0) {
            hist.innerHTML = '<p class="text-zinc-500 text-center">No messages yet. Send one below.</p>';
          } else {
            hist.innerHTML = msgs.map(function(m) {
              var from = m.from === 'admin' ? 'You' : (m.from || '—');
              var date = (m.created_at || '').replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}).*/, '$3/$2/$1 $4:$5');
              return '<div class="border-b border-zinc-700/50 pb-2 last:border-0"><div class="flex justify-between text-xs text-zinc-500 mb-0.5"><span>' + escMsg(from) + '</span><span>' + escMsg(date) + '</span></div><p class="text-zinc-300 whitespace-pre-wrap">' + escMsg(m.body) + '</p></div>';
            }).join('');
          }
        })
        .catch(function() {
          hist.innerHTML = '<p class="text-zinc-500 text-center">Failed to load.</p>';
        });
    }
  }
  function closeMessageModal() {
    document.getElementById('message-modal').classList.add('hidden');
    document.getElementById('message-modal').style.display = 'none';
  }
  document.getElementById('message-close').addEventListener('click', closeMessageModal);
  document.getElementById('message-modal').addEventListener('click', function(e) { if (e.target.id === 'message-modal') closeMessageModal(); });
  document.getElementById('message-send').addEventListener('click', function() {
    var driverId = document.getElementById('message-driver-id').value.trim();
    var body = document.getElementById('message-body').value.trim();
    if (!driverId || !body) { alert('Enter a message.'); return; }
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Sending…';
    fetch('api/driver-messages.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ driver_id: driverId, body: body })
    }).then(function(r) { return r.json(); }).then(function(d) {
      btn.disabled = false;
      btn.textContent = 'Send';
      if (d.ok) {
        document.getElementById('message-body').value = '';
        openMessageModal(driverId, document.getElementById('message-driver-name').textContent);
        loadDrivers();
      } else {
        alert(d.error || 'Failed to send');
      }
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Send';
      alert('Network error');
    });
  });

  document.getElementById('btn-add-driver').addEventListener('click', function() { openDriverModal(); });
  document.getElementById('driver-modal-cancel').addEventListener('click', closeDriverModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeDriverModal(); });

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
  function renderVehicleDetails(vehicleData, van, vanReg) {
    var panel = document.getElementById('driver-vehicle-summary');
    var dl = document.getElementById('driver-vehicle-dl');
    var ok = document.getElementById('driver-vehicle-ok');
    var warn = document.getElementById('driver-vehicle-warn');
    var placeholder = document.getElementById('driver-vehicle-placeholder');
    if (!panel || !dl) return;
    ok.classList.add('hidden');
    warn.classList.add('hidden');
    var v = vehicleData && typeof vehicleData === 'object' ? vehicleData : null;
    var hasFullData = v && (v.registrationNumber || v.make || v.model);
    var hasBasicData = (van || '').trim() || (vanReg || '').trim();
    if (hasFullData) {
      var rows = [
        { label: 'Reg', value: v.registrationNumber, mono: true },
        { label: 'Make', value: v.make },
        { label: 'Model', value: v.model },
        { label: 'Year', value: v.yearOfManufacture },
        { label: 'Colour', value: v.colour },
        { label: 'Fuel', value: v.fuelType },
        { label: 'Engine', value: v.engineCapacity ? v.engineCapacity + ' cc' : '' },
        { label: 'MOT status', value: v.mot && v.mot.motStatus },
        { label: 'MOT due', value: v.mot && v.mot.motDueDate },
        { label: 'MOT days', value: v.mot && v.mot.days != null ? v.mot.days + ' days' : '' },
        { label: 'Tax status', value: v.tax && v.tax.taxStatus },
        { label: 'Tax due', value: v.tax && v.tax.taxDueDate },
        { label: 'V5C issued', value: v.dateOfLastV5CIssued }
      ];
      var html = '';
      rows.forEach(function(r) {
        if (r.value === undefined || r.value === null || r.value === '') return;
        html += '<dt class="text-zinc-500">' + (r.label||'').replace(/</g,'&lt;') + '</dt><dd class="text-zinc-300' + (r.mono ? ' font-mono' : '') + '">' + String(r.value).replace(/</g,'&lt;') + '</dd>';
      });
      dl.innerHTML = html;
      if (placeholder) placeholder.classList.add('hidden');
      var motExpired = isExpiredStatus(v.mot && v.mot.motStatus) || (v.mot && v.mot.motDueDate && isExpiredOrSoon(v.mot.motDueDate, 0));
      var taxExpired = isExpiredStatus(v.tax && v.tax.taxStatus) || (v.tax && v.tax.taxDueDate && isExpiredOrSoon(v.tax.taxDueDate, 0));
      if (motExpired || taxExpired) {
        warn.classList.remove('hidden');
      } else {
        ok.classList.remove('hidden');
      }
    } else if (hasBasicData) {
      var basicRows = [
        { label: 'Reg', value: (vanReg || '').trim(), mono: true },
        { label: 'Make / Model', value: (van || '').trim() }
      ].filter(function(r) { return r.value; });
      var html = '';
      basicRows.forEach(function(r) {
        html += '<dt class="text-zinc-500">' + (r.label||'').replace(/</g,'&lt;') + '</dt><dd class="text-zinc-300' + (r.mono ? ' font-mono' : '') + '">' + String(r.value).replace(/</g,'&lt;') + '</dd>';
      });
      dl.innerHTML = html;
      if (placeholder) placeholder.classList.add('hidden');
    } else {
      dl.innerHTML = '';
      if (placeholder) placeholder.classList.remove('hidden');
    }
    panel.classList.remove('hidden');
  }

  function renderVehicleSummary(data) {
    renderVehicleDetails(data, '', '');
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
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
