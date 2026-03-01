<?php
/**
 * Contact form: forwards to same Telegram bot as send-quote.php.
 * Validates math captcha then sends full name, number and message.
 * Uses dynamic.json for telegramBotToken and telegramChatIds.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$configPath = __DIR__ . '/dynamic.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$BOT_TOKEN = !empty($config['telegramBotToken']) ? trim((string) $config['telegramBotToken']) : '';
$CHAT_IDS = [];
if (!empty($config['telegramChatIds']) && is_array($config['telegramChatIds'])) {
  foreach ($config['telegramChatIds'] as $id) {
    $id = trim((string) $id);
    if ($id !== '') $CHAT_IDS[] = $id;
  }
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sanitizeContact($v, $max = 500) {
  $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $v);
  return substr(trim($v), 0, $max);
}

$fullName = isset($input['full_name']) ? sanitizeContact($input['full_name'], 200) : '';
$number   = isset($input['number'])   ? preg_replace('/[^0-9+\s\-]/', '', substr(trim((string)($input['number'] ?? '')), 0, 30) : '';
$message  = isset($input['message'])  ? sanitizeContact($input['message'], 2000) : '';
$captchaNum1  = isset($input['captcha_num1'])  ? (int)$input['captcha_num1']  : 0;
$captchaNum2  = isset($input['captcha_num2'])  ? (int)$input['captcha_num2']  : 0;
$captchaAnswer = isset($input['captcha_answer']) ? (int)$input['captcha_answer'] : null;

if ($fullName === '' || $number === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Please fill in name, number and message.']);
  exit;
}

$expected = $captchaNum1 + $captchaNum2;
if ($captchaAnswer === null || $captchaAnswer !== $expected) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Incorrect captcha. Please answer the sum correctly.']);
  exit;
}

$lines = [
  "📩 New contact form message",
  "👤 Full name: " . $fullName,
  "📱 Number: " . $number,
  "💬 Message:",
  $message,
];
$text = implode("\n", $lines);

if (!$BOT_TOKEN || empty($CHAT_IDS)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server config missing. Set telegramBotToken and telegramChatIds in dynamic.json.']);
  exit;
}

$sent = false;
$lastError = 'Telegram error';
foreach ($CHAT_IDS as $CHAT_ID) {
  $url = "https://api.telegram.org/bot" . urlencode($BOT_TOKEN) . "/sendMessage";
  $body = json_encode(['chat_id' => $CHAT_ID, 'text' => $text]);
  $ctx = stream_context_create([
    'http' => [
      'method'  => 'POST',
      'header'  => "Content-Type: application/json\r\n",
      'content' => $body,
    ],
  ]);
  $res = @file_get_contents($url, false, $ctx);
  $data = $res ? json_decode($res, true) : null;
  if (!empty($data['ok'])) {
    $sent = true;
    break;
  }
  $lastError = $data['description'] ?? 'Telegram error';
}

if ($sent) {
  http_response_code(200);
  echo json_encode(['ok' => true]);
} else {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $lastError]);
}
