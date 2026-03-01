<?php
$pageTitle = 'Services';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Services</h1>
<p class="text-zinc-400 mb-6">Manage estimate services: price, description, SEO, icon. Disabled services are hidden on the estimate form.</p>

<div class="flex justify-end mb-6">
  <button type="button" id="btn-add-service" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">
    + Add service
  </button>
</div>

<div id="services-list" class="space-y-3">
  <p class="text-zinc-500">Loading…</p>
</div>

<!-- Modal -->
<div id="service-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70" style="display: none;">
  <div class="w-full max-w-lg rounded-2xl border border-zinc-700 bg-zinc-800 p-6 max-h-[90vh] overflow-y-auto">
    <h2 id="modal-title" class="text-lg font-bold text-white mb-6">Add service</h2>
    <form id="service-form" class="space-y-4">
      <input type="hidden" id="service-id" name="id">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="service-key" class="block text-sm font-medium text-zinc-300 mb-1">Key (e.g. punctureRepair) *</label>
          <input type="text" id="service-key" name="key" required pattern="[a-zA-Z0-9]+" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="punctureRepair">
        </div>
        <div>
          <label for="service-label" class="block text-sm font-medium text-zinc-300 mb-1">Label *</label>
          <input type="text" id="service-label" name="label" required class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Puncture repair">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="service-price" class="block text-sm font-medium text-zinc-300 mb-1">Price (£) *</label>
          <input type="number" id="service-price" name="price" required min="0" step="0.01" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="25">
        </div>
        <div>
          <label for="service-icon" class="block text-sm font-medium text-zinc-300 mb-1">Icon</label>
          <select id="service-icon" name="icon" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
            <option value="wrench">Wrench</option>
            <option value="circle">Circle (tyre)</option>
            <option value="bolt">Bolt</option>
            <option value="truck">Truck</option>
            <option value="tool">Tool</option>
          </select>
        </div>
      </div>
      <div>
        <label for="service-description" class="block text-sm font-medium text-zinc-300 mb-1">Description</label>
        <textarea id="service-description" name="description" rows="3" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Brief description for the service"></textarea>
      </div>
      <div class="border-t border-zinc-700 pt-4">
        <p class="text-sm font-medium text-zinc-300 mb-2">SEO</p>
        <div class="space-y-2">
          <input type="text" id="service-seo-title" name="seo_title" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm" placeholder="Meta title">
          <input type="text" id="service-seo-description" name="seo_description" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm" placeholder="Meta description">
          <input type="url" id="service-seo-ogImage" name="seo_ogImage" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none text-sm" placeholder="OG image URL">
        </div>
      </div>
      <div class="flex items-center gap-3 pt-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" id="service-enabled" name="enabled" checked class="rounded border-zinc-600 bg-zinc-700 text-safety focus:ring-safety">
          <span class="text-zinc-300 text-sm">Enabled (show on estimate)</span>
        </label>
      </div>
      <div class="flex gap-3 pt-4">
        <button type="submit" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">Save</button>
        <button type="button" id="modal-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-300 rounded-lg hover:bg-zinc-700 focus:outline-none">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  var listEl = document.getElementById('services-list');
  var modal = document.getElementById('service-modal');
  var form = document.getElementById('service-form');

  function load() {
    fetch('api/services.php')
      .then(function(r) { return r.json(); })
      .then(function(services) {
        if (!Array.isArray(services)) { listEl.innerHTML = '<p class="text-red-400">Failed to load</p>'; return; }
        if (services.length === 0) {
          listEl.innerHTML = '<p class="text-zinc-500">No services. Click Add service.</p>';
          return;
        }
        listEl.innerHTML = services.map(function(s) {
          var status = s.enabled ? '<span class="text-green-400 text-xs">Enabled</span>' : '<span class="text-zinc-500 text-xs">Disabled</span>';
          return '<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-zinc-700 bg-zinc-800/50 p-4" data-id="' + (s.id||'') + '">' +
            '<div class="flex-1 min-w-0">' +
              '<p class="font-semibold text-white">' + (s.label||'') + ' <span class="font-mono text-zinc-500 text-sm">' + (s.key||'') + '</span></p>' +
              '<p class="text-safety font-medium mt-0.5">£' + (Number(s.price)||0).toFixed(2) + '</p>' +
              '<p class="text-zinc-500 text-xs mt-1">' + status + '</p>' +
            '</div>' +
            '<div class="flex items-center gap-2">' +
              '<button type="button" class="btn-edit px-3 py-1.5 rounded bg-zinc-700 text-zinc-200 text-sm hover:bg-zinc-600" data-id="' + (s.id||'') + '">Edit</button>' +
              '<button type="button" class="btn-delete px-3 py-1.5 rounded bg-red-900/50 text-red-300 text-sm hover:bg-red-900/70" data-id="' + (s.id||'') + '">Delete</button>' +
            '</div>' +
          '</div>';
        }).join('');
        listEl.querySelectorAll('.btn-edit').forEach(function(b) {
          b.addEventListener('click', function() { openModal(b.getAttribute('data-id')); });
        });
        listEl.querySelectorAll('.btn-delete').forEach(function(b) {
          b.addEventListener('click', function() {
            if (confirm('Delete this service?')) deleteService(b.getAttribute('data-id'));
          });
        });
      })
      .catch(function() { listEl.innerHTML = '<p class="text-red-400">Failed to load services</p>'; });
  }

  function openModal(id) {
    document.getElementById('modal-title').textContent = id ? 'Edit service' : 'Add service';
    document.getElementById('service-id').value = id || '';
    if (id) {
      fetch('api/services.php').then(function(r) { return r.json(); }).then(function(services) {
        var s = services.find(function(x) { return (x.id||'') === id; });
        if (s) {
          document.getElementById('service-key').value = s.key || '';
          document.getElementById('service-key').readOnly = true;
          document.getElementById('service-label').value = s.label || '';
          document.getElementById('service-price').value = s.price ?? '';
          document.getElementById('service-description').value = s.description || '';
          document.getElementById('service-icon').value = s.icon || 'wrench';
          document.getElementById('service-seo-title').value = (s.seo&&s.seo.title) || '';
          document.getElementById('service-seo-description').value = (s.seo&&s.seo.description) || '';
          document.getElementById('service-seo-ogImage').value = (s.seo&&s.seo.ogImage) || '';
          document.getElementById('service-enabled').checked = s.enabled !== false;
        }
      });
    } else {
      document.getElementById('service-key').readOnly = false;
      form.reset();
      document.getElementById('service-enabled').checked = true;
    }
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }

  function deleteService(id) {
    fetch('api/services.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) load(); else alert(d.error || 'Failed');
    });
  }

  document.getElementById('btn-add-service').addEventListener('click', function() { openModal(); });
  document.getElementById('modal-cancel').addEventListener('click', closeModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var payload = {
      action: 'save',
      id: document.getElementById('service-id').value || undefined,
      key: document.getElementById('service-key').value,
      label: document.getElementById('service-label').value,
      price: parseFloat(document.getElementById('service-price').value) || 0,
      description: document.getElementById('service-description').value,
      icon: document.getElementById('service-icon').value,
      enabled: document.getElementById('service-enabled').checked,
      seo_title: document.getElementById('service-seo-title').value,
      seo_description: document.getElementById('service-seo-description').value,
      seo_ogImage: document.getElementById('service-seo-ogImage').value
    };
    if (payload.id) payload.id = document.getElementById('service-id').value;
    fetch('api/services.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) { closeModal(); load(); } else alert(d.error || 'Failed to save');
    });
  });

  load();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
