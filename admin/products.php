<?php
$pageTitle = 'Products';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8 flex items-center gap-3">
  <svg class="w-8 h-8 text-safety" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
  Products
</h1>

<div class="flex justify-between items-center mb-6">
  <p class="text-zinc-500">Manage products (tyres, parts, etc.). Add SKU, price, stock, and details like WooCommerce.</p>
  <button type="button" id="btn-add-product" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] text-sm">+ Add product</button>
</div>

<div class="w-full rounded-xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-zinc-800">
        <tr class="border-b border-zinc-700">
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">SKU</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Name</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Category</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Price</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Stock</th>
          <th class="text-left py-4 px-4 text-zinc-400 font-medium">Status</th>
          <th class="text-right py-4 px-4 text-zinc-400 font-medium">Actions</th>
        </tr>
      </thead>
      <tbody id="products-list">
        <tr><td colspan="7" class="py-12 text-center text-zinc-500">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Product modal -->
<div id="product-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 overflow-y-auto" style="display: none;">
  <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-800 p-6 my-8">
    <h3 id="product-modal-title" class="text-lg font-bold text-white mb-4">Add product</h3>
    <form id="product-form" class="space-y-4">
      <input type="hidden" id="product-id">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="product-sku" class="block text-sm font-medium text-zinc-300 mb-1">SKU *</label>
          <input type="text" id="product-sku" required class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="e.g. TYRE-195-65-R15">
        </div>
        <div>
          <label for="product-name" class="block text-sm font-medium text-zinc-300 mb-1">Name *</label>
          <input type="text" id="product-name" required class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Product name">
        </div>
      </div>

      <div>
        <label for="product-description" class="block text-sm font-medium text-zinc-300 mb-1">Description</label>
        <textarea id="product-description" rows="3" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Product description"></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="product-price" class="block text-sm font-medium text-zinc-300 mb-1">Price (£) *</label>
          <input type="number" id="product-price" required min="0" step="0.01" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="0.00">
        </div>
        <div>
          <label for="product-category" class="block text-sm font-medium text-zinc-300 mb-1">Category *</label>
          <select id="product-category" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
            <option value="Tyre">Tyre</option>
            <option value="Part">Part</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="product-stock" class="block text-sm font-medium text-zinc-300 mb-1">Stock / Quantity</label>
          <input type="number" id="product-stock" min="0" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="0" value="0">
        </div>
        <div>
          <label for="product-status" class="block text-sm font-medium text-zinc-300 mb-1">Status</label>
          <select id="product-status" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div>
        <label for="product-image-url" class="block text-sm font-medium text-zinc-300 mb-1">Image URL</label>
        <input type="url" id="product-image-url" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="https://...">
      </div>

      <div class="flex gap-2 pt-2">
        <button type="submit" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Save</button>
        <button type="button" id="product-modal-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  var listEl = document.getElementById('products-list');
  var modal = document.getElementById('product-modal');
  var form = document.getElementById('product-form');

  function escape(s) { if (s == null || s === '') return ''; var x = String(s); return x.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

  function load() {
    fetch('api/products.php')
      .then(function(r) { return r.json(); })
      .then(function(products) {
        if (!Array.isArray(products)) { listEl.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-red-400">Failed to load</td></tr>'; return; }
        if (products.length === 0) {
          listEl.innerHTML = '<tr><td colspan="7" class="py-12 text-center text-zinc-500">No products. Click Add product.</td></tr>';
          return;
        }
        listEl.innerHTML = products.map(function(p) {
          var statusBadge = (p.status || 'active') === 'active'
            ? '<span class="px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-300">Active</span>'
            : '<span class="px-2 py-0.5 rounded text-xs font-medium bg-zinc-700 text-zinc-400">Inactive</span>';
          var price = Number(p.price) || 0;
          var actionBtns = '<div class="flex flex-wrap gap-1 justify-end">' +
            '<button type="button" class="btn-edit px-2 py-1 rounded bg-zinc-700 text-zinc-300 text-xs" data-id="' + escape(p.id||'') + '">Edit</button>' +
            '<button type="button" class="btn-delete px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs" data-id="' + escape(p.id||'') + '">Delete</button>' +
          '</div>';
          return '<tr class="product-row border-b border-zinc-700/50 hover:bg-zinc-800/50 cursor-pointer" data-id="' + escape(p.id||'') + '" role="button" tabindex="0">' +
            '<td class="py-3 px-4 font-mono text-zinc-300 text-xs">' + escape(p.sku||'—') + '</td>' +
            '<td class="py-3 px-4 font-semibold text-white">' + escape(p.name||'—') + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + escape(p.category||'Other') + '</td>' +
            '<td class="py-3 px-4 text-safety font-medium">£' + price.toFixed(2) + '</td>' +
            '<td class="py-3 px-4 text-zinc-400">' + (p.stock != null ? p.stock : '—') + '</td>' +
            '<td class="py-3 px-4">' + statusBadge + '</td>' +
            '<td class="py-3 px-4 text-right" onclick="event.stopPropagation()">' + actionBtns + '</td>' +
          '</tr>';
        }).join('');

        listEl.querySelectorAll('.product-row').forEach(function(row) {
          row.addEventListener('click', function(e) { if (e.target.closest('button')) return; openModal(row.getAttribute('data-id')); });
          row.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(row.getAttribute('data-id')); } });
        });
        listEl.querySelectorAll('.btn-edit').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); openModal(b.getAttribute('data-id')); });
        });
        listEl.querySelectorAll('.btn-delete').forEach(function(b) {
          b.addEventListener('click', function(e) { e.stopPropagation(); if (confirm('Delete this product?')) deleteProduct(b.getAttribute('data-id')); });
        });
      })
      .catch(function() {
        listEl.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-red-400">Failed to load products.</td></tr>';
      });
  }

  function openModal(id) {
    document.getElementById('product-modal-title').textContent = id ? 'Edit product' : 'Add product';
    document.getElementById('product-id').value = id || '';
    if (id) {
      fetch('api/products.php').then(function(r) { return r.json(); }).then(function(products) {
        var p = Array.isArray(products) ? products.find(function(x) { return (x.id||'') === id; }) : null;
        if (p) {
          document.getElementById('product-sku').value = p.sku || '';
          document.getElementById('product-name').value = p.name || '';
          document.getElementById('product-description').value = p.description || '';
          document.getElementById('product-price').value = p.price ?? '';
          document.getElementById('product-category').value = p.category || 'Other';
          document.getElementById('product-stock').value = p.stock != null ? p.stock : 0;
          document.getElementById('product-image-url').value = p.image_url || '';
          document.getElementById('product-status').value = p.status || 'active';
        }
      });
    } else {
      form.reset();
      document.getElementById('product-stock').value = 0;
    }
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }

  function deleteProduct(id) {
    fetch('api/products.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (d.ok) load(); else alert(d.error || 'Failed');
    });
  }

  document.getElementById('btn-add-product').addEventListener('click', function() { openModal(); });
  document.getElementById('product-modal-cancel').addEventListener('click', closeModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var payload = {
      action: 'save',
      id: document.getElementById('product-id').value || undefined,
      sku: document.getElementById('product-sku').value.trim(),
      name: document.getElementById('product-name').value.trim(),
      description: document.getElementById('product-description').value.trim(),
      price: parseFloat(document.getElementById('product-price').value) || 0,
      category: document.getElementById('product-category').value || 'Other',
      stock: parseInt(document.getElementById('product-stock').value, 10) || 0,
      image_url: document.getElementById('product-image-url').value.trim(),
      status: document.getElementById('product-status').value || 'active'
    };
    fetch('api/products.php', {
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
