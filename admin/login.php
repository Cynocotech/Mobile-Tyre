<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/security.php';
if (!empty($_SESSION['admin_ok'])) {
  header('Location: index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | No 5 Tyre</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
</head>
<body class="bg-zinc-900 text-zinc-200 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-700 bg-zinc-800/80 p-8 shadow-xl">
    <h1 class="text-xl font-bold text-white mb-6 text-center">Admin Login</h1>
    <?php if (isset($_POST['password'])): ?>
    <p class="text-red-400 text-sm mb-4">Invalid password.</p>
    <?php endif; ?>
    <form method="post" class="space-y-4" autocomplete="off">
      <?php echo csrf_field(); ?>
      <div>
        <label for="password" class="block text-sm font-medium text-zinc-300 mb-1">Password</label>
        <input id="password" type="password" name="password" required autofocus class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none focus:ring-2 focus:ring-safety/30">
      </div>
      <button type="submit" class="w-full px-4 py-3 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">
        Log in
      </button>
    </form>
  </div>
</body>
</html>
