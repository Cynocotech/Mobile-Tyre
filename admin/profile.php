<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Admin Profile</h1>

<div id="profile-loading" class="text-zinc-500">Loading…</div>
<form id="profile-form" class="hidden space-y-6 max-w-lg">
  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">Profile</h2>
    <div class="space-y-4">
      <div>
        <label for="profile-name" class="block text-sm font-medium text-zinc-300 mb-1">Name</label>
        <input type="text" id="profile-name" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Admin name">
      </div>
      <div>
        <label for="profile-email" class="block text-sm font-medium text-zinc-300 mb-1">Email</label>
        <input type="email" id="profile-email" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="admin@example.com">
      </div>
      <div>
        <label for="profile-password" class="block text-sm font-medium text-zinc-300 mb-1">New password (optional)</label>
        <input type="password" id="profile-password" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="Leave blank to keep current" autocomplete="new-password">
        <p class="text-zinc-500 text-xs mt-1">Min 8 characters. Leave blank to keep current password.</p>
      </div>
    </div>
  </div>
  <div class="flex gap-3">
    <button type="submit" class="px-6 py-2.5 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">Save</button>
    <span id="profile-status" class="text-zinc-500 text-sm py-2"></span>
  </div>
</form>

<script>
(function() {
  var form = document.getElementById('profile-form');
  var status = document.getElementById('profile-status');

  fetch('api/profile.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      document.getElementById('profile-loading').classList.add('hidden');
      form.classList.remove('hidden');
      document.getElementById('profile-name').value = data.name || '';
      document.getElementById('profile-email').value = data.email || '';
    })
    .catch(function() {
      document.getElementById('profile-loading').textContent = 'Failed to load.';
    });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var payload = {
      name: document.getElementById('profile-name').value.trim(),
      email: document.getElementById('profile-email').value.trim(),
      password: document.getElementById('profile-password').value
    };
    if (!payload.password) delete payload.password;
    status.textContent = 'Saving…';
    fetch('api/profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(d) {
      status.textContent = d.ok ? 'Saved.' : (d.error || 'Failed');
      status.classList.toggle('text-green-400', d.ok);
      status.classList.toggle('text-red-400', !d.ok);
      if (d.ok) document.getElementById('profile-password').value = '';
    });
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
