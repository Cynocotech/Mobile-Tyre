<?php
$pageTitle = 'Site Settings';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
?>
<h1 class="text-2xl font-bold text-white mb-8">Site Settings</h1>
<p class="text-zinc-400 mb-8">All fields from dynamic.json. Changes apply immediately to the live site.</p>

<div id="settings-loading" class="text-zinc-500">Loading…</div>
<form id="settings-form" class="hidden space-y-8">
  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">General</h2>
    <div class="space-y-4">
      <div>
        <label for="laborPrice" class="block text-sm font-medium text-zinc-300 mb-1">Labour / call-out price (£)</label>
        <input type="number" id="laborPrice" name="laborPrice" min="0" step="0.01" class="w-full max-w-xs px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
      </div>
      <div>
        <label for="images" class="block text-sm font-medium text-zinc-300 mb-1">Gallery images (one URL per line)</label>
        <textarea id="images" name="images" rows="4" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono text-sm" placeholder="https://..."></textarea>
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">Telegram</h2>
    <div class="space-y-4">
      <div>
        <label for="telegramBotToken" class="block text-sm font-medium text-zinc-300 mb-1">Bot token</label>
        <input type="password" id="telegramBotToken" name="telegramBotToken" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono text-sm" placeholder="123456:ABC...">
        <p class="text-zinc-500 text-xs mt-1">Leave blank to keep current</p>
      </div>
      <div>
        <label for="telegramChatIds" class="block text-sm font-medium text-zinc-300 mb-1">Chat IDs (comma-separated)</label>
        <input type="text" id="telegramChatIds" name="telegramChatIds" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="1819809453, 123456">
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">Stripe</h2>
    <div class="space-y-4">
      <div>
        <label for="stripePublishableKey" class="block text-sm font-medium text-zinc-300 mb-1">Publishable key</label>
        <input type="text" id="stripePublishableKey" name="stripePublishableKey" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono text-sm" placeholder="pk_...">
      </div>
      <div>
        <label for="stripeSecretKey" class="block text-sm font-medium text-zinc-300 mb-1">Secret key</label>
        <input type="password" id="stripeSecretKey" name="stripeSecretKey" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono text-sm" placeholder="sk_...">
        <p class="text-zinc-500 text-xs mt-1">Leave blank to keep current</p>
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">SMTP (Email)</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="smtp_host" class="block text-sm font-medium text-zinc-300 mb-1">Host</label>
        <input type="text" id="smtp_host" name="smtp_host" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
      </div>
      <div>
        <label for="smtp_port" class="block text-sm font-medium text-zinc-300 mb-1">Port</label>
        <input type="number" id="smtp_port" name="smtp_port" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="465">
      </div>
      <div>
        <label for="smtp_user" class="block text-sm font-medium text-zinc-300 mb-1">User</label>
        <input type="text" id="smtp_user" name="smtp_user" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
      </div>
      <div>
        <label for="smtp_pass" class="block text-sm font-medium text-zinc-300 mb-1">Password</label>
        <input type="password" id="smtp_pass" name="smtp_pass" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
        <p class="text-zinc-500 text-xs mt-1">Leave blank to keep current</p>
      </div>
      <div>
        <label for="smtp_encryption" class="block text-sm font-medium text-zinc-300 mb-1">Encryption</label>
        <select id="smtp_encryption" name="smtp_encryption" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
          <option value="ssl">SSL</option>
          <option value="tls">TLS</option>
          <option value="">None</option>
        </select>
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">VAT</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="vatNumber" class="block text-sm font-medium text-zinc-300 mb-1">VAT number</label>
        <input type="text" id="vatNumber" name="vatNumber" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="GB123456789">
      </div>
      <div>
        <label for="vatRate" class="block text-sm font-medium text-zinc-300 mb-1">VAT rate (%)</label>
        <input type="number" id="vatRate" name="vatRate" min="0" max="100" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="20">
      </div>
    </div>
  </div>

  <div class="rounded-xl border border-zinc-700 bg-zinc-800/50 p-6">
    <h2 class="text-lg font-semibold text-white mb-4">Driver & Misc</h2>
    <div class="space-y-4">
      <div>
        <label for="vrmApiToken" class="block text-sm font-medium text-zinc-300 mb-1">VRM API token (CheckCarDetails)</label>
        <input type="password" id="vrmApiToken" name="vrmApiToken" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none font-mono text-sm" placeholder="API key for vehicle lookup">
        <p class="text-zinc-500 text-xs mt-1">Used by Check vehicle and Add driver lookup. Leave blank to keep current.</p>
      </div>
      <div>
        <label for="driverScannerUrl" class="block text-sm font-medium text-zinc-300 mb-1">Driver scanner URL</label>
        <input type="url" id="driverScannerUrl" name="driverScannerUrl" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="https://.../driver-scanner.html">
      </div>
      <div>
        <label for="gtmContainerId" class="block text-sm font-medium text-zinc-300 mb-1">GTM container ID (optional)</label>
        <input type="text" id="gtmContainerId" name="gtmContainerId" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="GTM-XXXXXXX">
      </div>
    </div>
  </div>

  <div class="flex gap-3">
    <button type="submit" class="px-6 py-2.5 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">Save all</button>
    <span id="save-status" class="text-zinc-500 text-sm py-2"></span>
  </div>
</form>

<script>
(function() {
  var form = document.getElementById('settings-form');
  var status = document.getElementById('save-status');

  fetch('api/settings.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      document.getElementById('settings-loading').classList.add('hidden');
      form.classList.remove('hidden');
      if (data.laborPrice != null) document.getElementById('laborPrice').value = data.laborPrice;
      if (Array.isArray(data.images)) document.getElementById('images').value = data.images.join('\n');
      if (data.telegramBotToken) document.getElementById('telegramBotToken').value = data.telegramBotToken;
      if (Array.isArray(data.telegramChatIds)) document.getElementById('telegramChatIds').value = data.telegramChatIds.join(', ');
      if (data.stripePublishableKey) document.getElementById('stripePublishableKey').value = data.stripePublishableKey;
      if (data.stripeSecretKey) document.getElementById('stripeSecretKey').value = data.stripeSecretKey;
      var smtp = data.smtp || {};
      document.getElementById('smtp_host').value = smtp.host || '';
      document.getElementById('smtp_port').value = smtp.port || 465;
      document.getElementById('smtp_user').value = smtp.user || '';
      document.getElementById('smtp_pass').value = smtp.pass || '';
      document.getElementById('smtp_encryption').value = smtp.encryption || 'ssl';
      document.getElementById('vatNumber').value = data.vatNumber || '';
      document.getElementById('vatRate').value = data.vatRate ?? '';
      document.getElementById('vrmApiToken').value = data.vrmApiToken || '';
      document.getElementById('driverScannerUrl').value = data.driverScannerUrl || '';
      document.getElementById('gtmContainerId').value = data.gtmContainerId || '';
    })
    .catch(function() {
      document.getElementById('settings-loading').textContent = 'Failed to load.';
    });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var payload = {
      laborPrice: parseFloat(document.getElementById('laborPrice').value) || 0,
      images: document.getElementById('images').value.split(/\n/).map(function(s) { return s.trim(); }).filter(Boolean),
      telegramChatIds: document.getElementById('telegramChatIds').value,
      stripePublishableKey: document.getElementById('stripePublishableKey').value,
      vatNumber: document.getElementById('vatNumber').value,
      vatRate: parseInt(document.getElementById('vatRate').value, 10) || 0,
      driverScannerUrl: document.getElementById('driverScannerUrl').value,
      gtmContainerId: document.getElementById('gtmContainerId').value,
      vrmApiToken: document.getElementById('vrmApiToken').value,
      smtp: {
        host: document.getElementById('smtp_host').value,
        port: parseInt(document.getElementById('smtp_port').value, 10) || 465,
        user: document.getElementById('smtp_user').value,
        encryption: document.getElementById('smtp_encryption').value
      }
    };
    var tok = document.getElementById('telegramBotToken').value;
    if (tok) payload.telegramBotToken = tok;
    var sk = document.getElementById('stripeSecretKey').value;
    if (sk) payload.stripeSecretKey = sk;
    var pass = document.getElementById('smtp_pass').value;
    if (pass) payload.smtp.pass = pass;
    var vrm = document.getElementById('vrmApiToken').value;
    if (vrm) payload.vrmApiToken = vrm;
    status.textContent = 'Saving…';
    fetch('api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); }).then(function(d) {
      status.textContent = d.ok ? 'Saved.' : (d.error || 'Failed');
      status.classList.toggle('text-green-400', d.ok);
      status.classList.toggle('text-red-400', !d.ok);
    });
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
