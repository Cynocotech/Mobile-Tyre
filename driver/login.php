<?php
require_once __DIR__ . '/config.php';
session_start();
if (!empty($_SESSION[DRIVER_SESSION_KEY])) {
  header('Location: dashboard.php');
  exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $pin = $_POST['pin'] ?? '';
  if ($email && $password) {
    $driver = getDriverByEmail($email);
    if ($driver && password_verify($password, $driver['password_hash'] ?? '')) {
      $_SESSION[DRIVER_SESSION_KEY] = $driver['id'];
      $_SESSION['driver_time'] = time();
      header('Location: dashboard.php');
      exit;
    }
  }
  if ($pin && strlen($pin) >= 4) {
    $db = getDriverDb();
    foreach ($db as $d) {
      if (!empty($d['pin_hash']) && password_verify($pin, $d['pin_hash'])) {
        $_SESSION[DRIVER_SESSION_KEY] = $d['id'];
        $_SESSION['driver_time'] = time();
        header('Location: dashboard.php');
        exit;
      }
    }
  }
  $error = 'Invalid email/password or PIN.';
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Driver login | No 5 Tyre</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
</head>
<body class="bg-zinc-900 text-zinc-200 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm rounded-2xl border border-zinc-700 bg-zinc-800/80 p-8">
    <h1 class="text-xl font-bold text-white mb-6 text-center">Driver login</h1>
    <?php if ($error): ?><p class="text-red-400 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-zinc-300 mb-1">Email</label>
        <input id="email" type="email" name="email" class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="you@example.com" autocomplete="email">
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-zinc-300 mb-1">Password</label>
        <input id="password" type="password" name="password" class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="••••••••" autocomplete="current-password">
      </div>
      <div class="relative">
        <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-zinc-600"></span></div>
        <div class="relative flex justify-center text-xs"><span class="px-2 bg-zinc-800 text-zinc-500">or</span></div>
      </div>
      <div>
        <label for="pin" class="block text-sm font-medium text-zinc-300 mb-1">PIN (quick login)</label>
        <input id="pin" type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="w-full px-4 py-3 rounded-lg bg-zinc-700 border border-zinc-600 text-white focus:border-safety focus:outline-none" placeholder="4–6 digits">
      </div>
      <button type="submit" class="w-full px-4 py-3 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety">Log in</button>
    </form>
    <p class="text-zinc-500 text-sm mt-6 text-center"><a href="onboarding.html" class="text-safety hover:underline">New driver? Sign up</a></p>
  </div>
</body>
</html>
