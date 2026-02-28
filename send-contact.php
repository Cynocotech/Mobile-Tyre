<?php
/**
 * Contact form: forwards to same Telegram bot as send-quote.php.
 * Validates math captcha then sends full name, number and message.
 * Uses same BOT_TOKEN and CHAT_ID as send-quote.php.
 */
header('Content-Type: application/json');
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

$BOT_TOKEN = '8798625642:AAHaq1No3a4lcO3tdHDz2jrmWC24VUF_N3s';
$CHAT_ID   = '1819809453';

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

$url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
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
  http_response_code(200);
  echo json_encode(['ok' => true]);
} else {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $data['description'] ?? 'Telegram error']);
}
