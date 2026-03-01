<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/auth.php';
$driver = getDriverById($_SESSION[DRIVER_SESSION_KEY]);
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
    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 overflow-hidden">
      <dl class="divide-y divide-zinc-700">
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Name</dt><dd class="text-white font-medium"><?php echo htmlspecialchars($driver['name'] ?? '—'); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Email</dt><dd class="text-white"><?php echo htmlspecialchars($driver['email'] ?? '—'); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Phone</dt><dd class="text-white"><a href="tel:<?php echo htmlspecialchars(preg_replace('/\D/', '', $driver['phone'] ?? '')); ?>" class="text-safety"><?php echo htmlspecialchars($driver['phone'] ?? '—'); ?></a></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Licence</dt><dd class="text-white font-mono"><?php echo htmlspecialchars($driver['license_number'] ?? '—'); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Van</dt><dd class="text-white"><?php echo htmlspecialchars($driver['van_make'] ?? '—'); ?> <?php echo htmlspecialchars($driver['van_reg'] ?? ''); ?></dd></div>
        <div class="px-4 py-4 flex justify-between"><dt class="text-zinc-500">Stripe payouts</dt><dd class="text-white"><?php echo !empty($driver['stripe_onboarding_complete']) ? '<span class="text-green-400">Connected</span>' : '<span class="text-amber-400">Pending</span>'; ?></dd></div>
      </dl>
    </div>
  </main>
</body>
</html>
