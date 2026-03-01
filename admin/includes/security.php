<?php
/**
 * Security utilities: CSRF, headers, sanitization.
 */

if (!function_exists('security_headers')) {
  function security_headers() {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }
}

if (!function_exists('csrf_token')) {
  function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }
}

if (!function_exists('csrf_field')) {
  function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
  }
}

if (!function_exists('validate_csrf')) {
  function validate_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return $token !== '' && hash_equals(csrf_token(), $token);
  }
}

if (!function_exists('safe_id')) {
  /** Alphanumeric IDs for driver_id, etc. - prevents injection in filenames */
  function safe_id($v, $maxLen = 64) {
    $s = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $v);
    return substr($s, 0, $maxLen);
  }
}

if (!function_exists('safe_ref')) {
  /** Numeric reference (6 digits) */
  function safe_ref($v) {
    $s = preg_replace('/[^0-9]/', '', (string) $v);
    return $s !== '' ? str_pad(substr($s, 0, 12), min(6, strlen($s)), '0', STR_PAD_LEFT) : '';
  }
}

if (!function_exists('safe_session_id')) {
  /** Stripe Checkout session ID format: cs_xxx */
  function safe_session_id($v) {
    $s = trim((string) $v);
    return preg_match('/^cs_[a-zA-Z0-9_]{20,}$/', $s) ? $s : '';
  }
}

if (!function_exists('safe_path_under')) {
  /** Ensure resolved path is under base (prevents path traversal) */
  function safe_path_under($base, $subpath) {
    $subpath = preg_replace('#\.\./|\.\.\\\\#', '', (string) $subpath);
    $subpath = ltrim($subpath, '/\\');
    $combined = rtrim($base, '/\\') . '/' . $subpath;
    $full = realpath($combined);
    $baseReal = realpath(rtrim($base, '/\\'));
    if (!$full || !$baseReal || strpos($full, $baseReal) !== 0) {
      return null;
    }
    return $full;
  }
}
