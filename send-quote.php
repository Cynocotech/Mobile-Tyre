<?php
/**
 * Forwards quote form to Telegram bot. Use on any PHP hosting.
 * Edit BOT_TOKEN and CHAT_ID below (get token from @BotFather, chat_id from @userinfobot).
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
$vrm      = isset($input['vrm'])       ? trim((string)$input['vrm'])       : '';
$location = isset($input['location']) ? trim((string)$input['location'])   : '';
$mobile   = isset($input['mobile'])   ? trim((string)$input['mobile'])     : '';
$carMake  = isset($input['car_make']) ? trim((string)$input['car_make'])   : '';
$carModel = isset($input['car_model'])? trim((string)$input['car_model'])   : '';
$tyreSize = isset($input['tyre_size'])? trim((string)$input['tyre_size'])  : '';

$lines = [
  "🛞 New quote request",
  "🚗 VRM: " . ($vrm ?: '—'),
  "📍 Location: " . ($location ?: '—'),
  "📱 Mobile: " . ($mobile ?: '—'),
  "🏭 Make: " . ($carMake ?: '—'),
  "🚙 Model: " . ($carModel ?: '—'),
  "🛞 Tyre size: " . ($tyreSize ?: '—'),
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
