<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> | No 5 Tyre Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } }</script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
  <header class="sticky top-0 z-50 bg-zinc-900/95 backdrop-blur border-b border-zinc-700 shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14">
      <a href="dashboard.php" class="font-bold text-white">No 5 Tyre Admin</a>
      <nav class="flex items-center gap-2">
        <a href="dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'dashboard' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Dashboard</a>
        <a href="services.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'services' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Services</a>
        <a href="drivers.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'drivers' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Drivers</a>
        <a href="jobs.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'jobs' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Jobs</a>
        <a href="reports.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'reports' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Reports</a>
        <a href="settings.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'settings' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Settings</a>
        <a href="profile.php" class="px-3 py-2 rounded-lg text-sm font-medium <?php echo $currentPage === 'profile' ? 'bg-zinc-800 text-safety' : 'text-zinc-400 hover:text-white hover:bg-zinc-800'; ?>">Profile</a>
        <a href="logout.php" class="px-3 py-2 rounded-lg text-sm font-medium text-zinc-500 hover:text-red-400">Logout</a>
      </nav>
    </div>
  </header>
  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
