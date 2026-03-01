<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/auth.php';
$driver = getDriverForProfile($_SESSION[DRIVER_SESSION_KEY]);
$vd = $driver['vehicleData'] ?? null;
$licenseNum = $driver['license_number'] ?? $driver['kyc']['licenceNumber'] ?? '';
$insuranceUrl = $driver['insurance_url'] ?? null;
$insuranceUploadedAt = $driver['insurance_uploaded_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | No 5 Tyre Driver</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
  <header class="bg-zinc-900 border-b border-zinc-700">
    <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="dashboard.php" class="text-zinc-400 hover:text-white text-sm">← Back</a>
    </div>
  </header>

  <main class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-white mb-6">My profile</h1>

    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 overflow-hidden mb-6">
      <h2 class="px-4 py-3 text-sm font-semibold text-zinc-400 border-b border-zinc-700 flex justify-between items-center">
        <span>Personal</span>
        <button type="button" id="btn-edit-details" class="text-safety text-xs font-medium hover:underline">Edit details</button>
      </h2>
      <div id="personal-view" class="divide-y divide-zinc-700">
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Name</dt><dd id="disp-name" class="text-white font-medium"><?php echo htmlspecialchars($driver['name'] ?? '—'); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Email</dt><dd class="text-white"><?php echo htmlspecialchars($driver['email'] ?? '—'); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Phone</dt><dd id="disp-phone" class="text-white"><a href="tel:<?php echo htmlspecialchars(preg_replace('/\D/', '', $driver['phone'] ?? '')); ?>" class="text-safety"><?php echo htmlspecialchars($driver['phone'] ?? '—'); ?></a></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Licence</dt><dd class="text-white font-mono"><?php echo htmlspecialchars($licenseNum ?: '—'); ?></dd></div>
      </div>
      <form id="personal-edit-form" class="hidden p-4 space-y-4">
        <div>
          <label for="edit-name" class="block text-sm text-zinc-500 mb-1">Name</label>
          <input type="text" id="edit-name" required value="<?php echo htmlspecialchars($driver['name'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
        </div>
        <div>
          <label for="edit-phone" class="block text-sm text-zinc-500 mb-1">Phone</label>
          <input type="tel" id="edit-phone" value="<?php echo htmlspecialchars($driver['phone'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none">
        </div>
        <p class="text-zinc-500 text-xs">Email cannot be changed. Contact the office if it needs updating.</p>
        <div class="flex gap-2">
          <button type="submit" class="px-4 py-2 bg-safety text-zinc-900 font-bold rounded-lg text-sm">Save</button>
          <button type="button" id="edit-cancel" class="px-4 py-2 border border-zinc-600 text-zinc-400 rounded-lg text-sm hover:bg-zinc-700">Cancel</button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 overflow-hidden mb-6">
      <h2 class="px-4 py-3 text-sm font-semibold text-zinc-400 border-b border-zinc-700">Vehicle details</h2>
      <div class="p-4">
        <?php if ($vd && is_array($vd) && !empty($vd['registrationNumber'] . $vd['make'] . $vd['model'])): ?>
          <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <?php if (!empty($vd['registrationNumber'])): ?><dt class="text-zinc-500">Registration</dt><dd class="text-white font-mono"><?php echo htmlspecialchars($vd['registrationNumber']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['make'])): ?><dt class="text-zinc-500">Make</dt><dd class="text-white"><?php echo htmlspecialchars($vd['make']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['model'])): ?><dt class="text-zinc-500">Model</dt><dd class="text-white"><?php echo htmlspecialchars($vd['model']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['colour'])): ?><dt class="text-zinc-500">Colour</dt><dd class="text-white"><?php echo htmlspecialchars($vd['colour']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['fuelType'])): ?><dt class="text-zinc-500">Fuel</dt><dd class="text-white"><?php echo htmlspecialchars($vd['fuelType']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['yearOfManufacture'])): ?><dt class="text-zinc-500">Year</dt><dd class="text-white"><?php echo htmlspecialchars($vd['yearOfManufacture']); ?></dd><?php endif; ?>
            <?php if (!empty($vd['mot']['motStatus'])): ?><dt class="text-zinc-500">MOT</dt><dd class="text-white"><?php echo htmlspecialchars($vd['mot']['motStatus']); ?><?php if (!empty($vd['mot']['motDueDate'])): ?> – <?php echo htmlspecialchars($vd['mot']['motDueDate']); endif; ?></dd><?php endif; ?>
            <?php if (!empty($vd['tax']['taxStatus'])): ?><dt class="text-zinc-500">Tax</dt><dd class="text-white"><?php echo htmlspecialchars($vd['tax']['taxStatus']); ?><?php if (!empty($vd['tax']['taxDueDate'])): ?> – <?php echo htmlspecialchars($vd['tax']['taxDueDate']); endif; ?></dd><?php endif; ?>
          </dl>
        <?php else: ?>
          <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-zinc-500">Van</dt><dd class="text-white"><?php echo htmlspecialchars($driver['van_make'] ?? '—'); ?></dd>
            <dt class="text-zinc-500">Registration</dt><dd class="text-white font-mono"><?php echo htmlspecialchars($driver['van_reg'] ?? '—'); ?></dd>
          </dl>
          <p class="text-zinc-500 text-xs mt-3">Vehicle details are filled by the office when you’re added. Contact them if these need updating.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 overflow-hidden mb-6">
      <h2 class="px-4 py-3 text-sm font-semibold text-zinc-400 border-b border-zinc-700">Insurance</h2>
      <div class="p-4">
        <?php if ($insuranceUrl): ?>
          <p class="text-green-400 text-sm mb-2">✓ Insurance document uploaded <?php echo $insuranceUploadedAt ? date('j M Y', strtotime($insuranceUploadedAt)) : ''; ?></p>
          <div class="flex flex-wrap gap-2">
            <a href="api/insurance-view.php" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-700 text-zinc-200 text-sm hover:bg-zinc-600">View document</a>
            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-safety text-zinc-900 font-medium text-sm cursor-pointer hover:bg-[#e5c900]">
              <input type="file" id="insurance-input" accept=".pdf,.jpg,.jpeg,.png" class="hidden"> Replace document
            </label>
          </div>
        <?php else: ?>
          <p class="text-amber-400 text-sm mb-3">Upload your insurance document (PDF, JPG or PNG).</p>
          <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-safety text-zinc-900 font-bold text-sm cursor-pointer hover:bg-[#e5c900]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Upload insurance
            <input type="file" id="insurance-input" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
          </label>
        <?php endif; ?>
        <p id="insurance-status" class="text-sm mt-2 hidden"></p>
      </div>
    </div>

    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
      <h2 class="px-4 py-3 text-sm font-semibold text-zinc-400 border-b border-zinc-700">Payouts & verification</h2>
      <dl class="divide-y divide-zinc-700">
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Stripe payouts</dt><dd class="text-white"><?php echo !empty($driver['stripe_onboarding_complete']) ? '<span class="text-green-400">Connected</span>' : '<a href="onboarding.html" class="text-amber-400 hover:underline">Complete setup</a>'; ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">ID / licence verified</dt><dd class="text-white"><?php echo !empty($driver['identity_verified']) ? '<span class="text-green-400">Verified</span>' : '<a href="dashboard.php" class="text-amber-400 hover:underline">Verify now</a>'; ?></dd></div>
      </dl>
    </div>
  </main>

  <script>
  (function() {
    var btnEdit = document.getElementById('btn-edit-details');
    var personalView = document.getElementById('personal-view');
    var personalForm = document.getElementById('personal-edit-form');
    var editCancel = document.getElementById('edit-cancel');
    if (btnEdit && personalView && personalForm) {
      btnEdit.addEventListener('click', function() {
        personalView.classList.add('hidden');
        personalForm.classList.remove('hidden');
      });
      editCancel.addEventListener('click', function() {
        personalForm.classList.add('hidden');
        personalView.classList.remove('hidden');
      });
      personalForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var name = document.getElementById('edit-name').value.trim();
        var phone = document.getElementById('edit-phone').value.trim();
        if (!name) { alert('Name is required'); return; }
        var fd = new FormData();
        fd.append('name', name);
        fd.append('phone', phone);
        fetch('api/profile.php', { method: 'POST', body: fd })
          .then(function(r) { return r.json(); })
          .then(function(d) {
            if (d.ok) {
              document.getElementById('disp-name').textContent = name;
              var phoneEl = document.getElementById('disp-phone');
              phoneEl.innerHTML = phone ? '<a href="tel:' + phone.replace(/\D/g,'') + '" class="text-safety">' + phone + '</a>' : '—';
              personalForm.classList.add('hidden');
              personalView.classList.remove('hidden');
            } else alert(d.error || 'Failed');
          })
          .catch(function() { alert('Update failed.'); });
      });
    }

    var input = document.getElementById('insurance-input');
    if (input) {
      input.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        var fd = new FormData();
        fd.append('insurance', this.files[0]);
        var status = document.getElementById('insurance-status');
        status.textContent = 'Uploading…';
        status.classList.remove('hidden', 'text-green-400', 'text-red-400');
        status.classList.add('text-zinc-400');
        fetch('api/insurance.php', { method: 'POST', body: fd })
          .then(function(r) { return r.json(); })
          .then(function(d) {
            if (d.ok) window.location.reload();
            else { status.textContent = d.error || 'Upload failed'; status.classList.add('text-red-400'); }
          })
          .catch(function() { status.textContent = 'Upload failed. Check connection.'; status.classList.add('text-red-400'); });
        this.value = '';
      });
    }
  })();
  </script>
</body>
</html>
