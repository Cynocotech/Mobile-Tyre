<?php
/**
 * Forwards quote form to Telegram bot. Use on any PHP hosting.
 * Telegram token and chat IDs are read from dynamic.json (same directory).
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

// Load Telegram config from dynamic.json (same directory as this script)
$configPath = __DIR__ . '/dynamic.json';
$BOT_TOKEN = null;
$CHAT_IDS = [];
if (is_file($configPath)) {
  $config = @json_decode(file_get_contents($configPath), true);
  if (!empty($config['telegramBotToken'])) {
    $BOT_TOKEN = trim((string)$config['telegramBotToken']);
  }
  if (!empty($config['telegramChatIds']) && is_array($config['telegramChatIds'])) {
    foreach ($config['telegramChatIds'] as $id) {
      $id = trim((string)$id);
      if ($id !== '') {
        $CHAT_IDS[] = $id;
      }
    }
  }
}
if (!$BOT_TOKEN || empty($CHAT_IDS)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server config missing. Set telegramBotToken and at least one telegramChatIds in dynamic.json.']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$vrm      = isset($input['vrm'])       ? trim((string)$input['vrm'])       : '';
$location = isset($input['location']) ? trim((string)$input['location'])   : '';
$mobile   = isset($input['mobile'])   ? trim((string)$input['mobile'])     : '';
$carMake  = isset($input['car_make']) ? trim((string)$input['car_make'])   : '';
$carModel = isset($input['car_model'])? trim((string)$input['car_model'])   : '';
$tyreSize = isset($input['tyre_size'])? trim((string)$input['tyre_size'])  : '';
$wheels   = isset($input['wheels'])  ? trim((string)$input['wheels'])     : '';

$lines = [
  "🛞 New quote request",
  "🚗 VRM: " . ($vrm ?: '—'),
  "🛞 Wheels (tyres): " . ($wheels ? $wheels : '—'),
  "📍 Location: " . ($location ?: '—'),
  "📱 Mobile: " . ($mobile ?: '—'),
  "🏭 Make: " . ($carMake ?: '—'),
  "🚙 Model: " . ($carModel ?: '—'),
  "🛞 Tyre size: " . ($tyreSize ?: '—'),
];
$text = implode("\n", $lines);

$url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
$ctx = stream_context_create([
  'http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/json\r\n",
  ],
]);

$anyOk = false;
$lastError = null;
foreach ($CHAT_IDS as $chatId) {
  $body = json_encode(['chat_id' => $chatId, 'text' => $text]);
  stream_context_set_option($ctx, 'http', 'content', $body);
  $res = @file_get_contents($url, false, $ctx);
  $data = $res ? json_decode($res, true) : null;
  if (!empty($data['ok'])) {
    $anyOk = true;
  } else {
    $lastError = $data['description'] ?? 'Telegram error';
  }
}

if ($anyOk) {
  http_response_code(200);
  echo json_encode(['ok' => true]);
} else {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $lastError ?? 'Telegram error']);
}
