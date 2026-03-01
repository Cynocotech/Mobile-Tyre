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
    <div id="driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
      <div class="w-full max-w-md rounded-2xl border border-zinc-700 bg-zinc-800 p-6">
        <h3 id="driver-modal-title" class="text-lg font-bold text-white mb-4">Add driver</h3>
        <form id="driver-form" class="space-y-3">
          <input type="hidden" id="driver-id">
          <input type="hidden" id="driver-vehicleData" value="">
          <div>
            <label for="driver-name" class="block text-sm font-medium text-zinc-300 mb-1">Name *</label>
            <input type="text" id="driver-name" required class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
          </div>
          <div>
            <label for="driver-phone" class="block text-sm font-medium text-zinc-300 mb-1">Phone</label>
            <input type="tel" id="driver-phone" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
          </div>
          <div>
            <label for="driver-vanReg" class="block text-sm font-medium text-zinc-300 mb-1">Van registration</label>
            <div class="flex gap-2">
              <input type="text" id="driver-vanReg" class="flex-1 px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="AB12 CDE">
              <button type="button" id="driver-lookup-vrm" class="px-3 py-2 rounded-lg bg-zinc-600 text-zinc-200 text-sm whitespace-nowrap hover:bg-zinc-600/80">Look up</button>
            </div>
          </div>
          <div>
            <label for="driver-van" class="block text-sm font-medium text-zinc-300 mb-1">Van / vehicle</label>
            <input type="text" id="driver-van" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="e.g. Ford Transit (auto-filled from lookup)">
          </div>
          <div>
            <label for="driver-notes" class="block text-sm font-medium text-zinc-300 mb-1">Notes</label>
            <textarea id="driver-notes" rows="2" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none"></textarea>
          </div>
          <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Save</button>
            <button type="button" id="driver-modal-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div>
    <h2 class="text-lg font-semibold text-white mb-4">Jobs</h2>
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

<script>
(function() {
  var driversList = document.getElementById('drivers-list');
  var jobsTbody = document.getElementById('jobs-tbody');
  var modal = document.getElementById('driver-modal');
  var form = document.getElementById('driver-form');

  function loadDrivers() {
    fetch('api/drivers.php?action=drivers')
      .then(function(r) { return r.json(); })
      .then(function(drivers) {
        if (!Array.isArray(drivers)) { driversList.innerHTML = '<p class="text-red-400">Failed to load</p>'; return; }
        if (drivers.length === 0) {
          driversList.innerHTML = '<p class="text-zinc-500">No drivers. Click Add driver.</p>';
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
        driversList.innerHTML = drivers.map(function(d) {
          var phone = (d.phone||'').trim();
          var van = (d.van||'').trim();
          var vanReg = (d.vanReg||'').trim();
          var notes = (d.notes||'').trim();
          var v = d.vehicleData;
          var vehicleSection = v && typeof v === 'object' && (v.registrationNumber || v.make || v.model)
            ? '<div class="mt-3 pt-3 border-t border-zinc-700"><p class="text-zinc-500 text-xs font-medium mb-2">Vehicle details</p><dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' + vehicleRows(v) + '</dl></div>'
            : ('<dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' +
                '<dt class="text-zinc-500">Van</dt><dd class="text-zinc-300">' + escape(van||'—') + '</dd>' +
                '<dt class="text-zinc-500">Reg</dt><dd class="text-zinc-300 font-mono">' + escape(vanReg||'—') + '</dd>' +
              '</dl>');
          return '<div class="rounded-lg border border-zinc-700 bg-zinc-800/50 p-4" data-id="' + (d.id||'') + '">' +
            '<div class="flex justify-between items-start gap-3 mb-3">' +
              '<p class="font-semibold text-white">' + escape(d.name||'—') + '</p>' +
              '<div class="flex gap-1 shrink-0">' +
                '<button type="button" class="btn-edit-driver px-2 py-1 rounded bg-zinc-700 text-zinc-300 text-xs" data-id="' + (d.id||'') + '">Edit</button>' +
                '<button type="button" class="btn-delete-driver px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + (d.id||'') + '">Delete</button>' +
              '</div>' +
            '</div>' +
            '<dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">' +
              '<dt class="text-zinc-500">Phone</dt><dd class="text-zinc-300">' + escape(phone||'—') + '</dd>' +
            '</dl>' +
            vehicleSection +
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
          return '<tr class="border-b border-zinc-700/50 hover:bg-zinc-800/50">' +
            '<td class="py-3 px-4 font-mono text-safety">' + (j.reference||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-300">' + v + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + (j.postcode||'—') + '</td>' +
            '<td class="py-3 px-4 text-right font-semibold text-white">' + (j.amount_paid||j.estimate_total||'—') + '</td>' +
          '</tr>';
        }).join('');
      });
  }

  function openDriverModal(id) {
    document.getElementById('driver-modal-title').textContent = id ? 'Edit driver' : 'Add driver';
    document.getElementById('driver-id').value = id || '';
    if (id) {
      fetch('api/drivers.php?action=drivers').then(function(r) { return r.json(); }).then(function(drivers) {
        var d = drivers.find(function(x) { return (x.id||'') === id; });
        if (d) {
          document.getElementById('driver-name').value = d.name || '';
          document.getElementById('driver-phone').value = d.phone || '';
          document.getElementById('driver-van').value = d.van || '';
          document.getElementById('driver-vanReg').value = d.vanReg || '';
          document.getElementById('driver-notes').value = d.notes || '';
          document.getElementById('driver-vehicleData').value = (d.vehicleData && typeof d.vehicleData === 'object') ? JSON.stringify(d.vehicleData) : '';
        }
      });
    } else {
      form.reset();
      document.getElementById('driver-id').value = '';
      document.getElementById('driver-vehicleData').value = '';
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

  document.getElementById('btn-add-driver').addEventListener('click', function() { openDriverModal(); });
  document.getElementById('driver-modal-cancel').addEventListener('click', closeDriverModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeDriverModal(); });

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
      phone: document.getElementById('driver-phone').value,
      van: document.getElementById('driver-van').value,
      vanReg: document.getElementById('driver-vanReg').value,
      notes: document.getElementById('driver-notes').value
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
      if (d.ok) { closeDriverModal(); loadDrivers(); } else alert(d.error || 'Failed');
    });
  });

  loadDrivers();
  loadJobs();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
