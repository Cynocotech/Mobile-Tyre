<?php
/**
 * Admin authentication – session-based. Include at top of all admin pages.
 * Default password: password (change in config or use php -r "echo password_hash('yourpass', PASSWORD_DEFAULT);")
 */
session_start();
$configPath = __DIR__ . '/config.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$hash = $config['passwordHash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$timeout = (int) ($config['sessionTimeout'] ?? 86400);

if (isset($_SESSION['admin_time']) && (time() - $_SESSION['admin_time']) > $timeout) {
  unset($_SESSION['admin_ok'], $_SESSION['admin_time']);
}

if (empty($_SESSION['admin_ok'])) {
  if (basename($_SERVER['PHP_SELF']) === 'login.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if (password_verify($pass, $hash)) {
      $_SESSION['admin_ok'] = true;
      $_SESSION['admin_time'] = time();
      header('Location: index.php');
      exit;
    }
  }
  if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
  }
} else {
  $_SESSION['admin_time'] = time();
}
