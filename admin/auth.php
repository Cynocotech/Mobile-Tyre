<?php
/**
 * Admin authentication – session-based. Include at top of all admin pages.
 * Default password: password (change in config or use php -r "echo password_hash('yourpass', PASSWORD_DEFAULT);")
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => 1,
  ]);
}

require_once __DIR__ . '/includes/security.php';
security_headers();

$configPath = __DIR__ . '/config.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$hash = $config['passwordHash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$timeout = (int) ($config['sessionTimeout'] ?? 86400);

if (isset($_SESSION['admin_time']) && (time() - $_SESSION['admin_time']) > $timeout) {
  unset($_SESSION['admin_ok'], $_SESSION['admin_time']);
}

if (empty($_SESSION['admin_ok'])) {
  if (basename($_SERVER['PHP_SELF']) === 'login.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('validate_csrf') || !validate_csrf()) {
      unset($_SESSION['csrf_token']);
    } else {
      $pass = $_POST['password'] ?? '';
      if (is_string($pass) && strlen($pass) <= 256 && password_verify($pass, $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        $_SESSION['admin_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
      }
    }
  }
  if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
  }
} else {
  $_SESSION['admin_time'] = time();
}
