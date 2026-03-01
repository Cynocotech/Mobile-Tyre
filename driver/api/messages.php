<?php
/**
 * Driver messages API – GET messages for logged-in driver, POST mark as read.
 */
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
if (!$driverId) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

require_once dirname(__DIR__, 2) . '/includes/messages.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $messages = messagesGetByDriver($driverId);
  echo json_encode(['messages' => $messages]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = $_POST;
if (empty($input)) {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?: [];
}
$action = trim($input['action'] ?? '');

if ($action === 'mark_read') {
  $messageId = trim($input['message_id'] ?? '');
  if (!$messageId) {
    http_response_code(400);
    echo json_encode(['error' => 'message_id required']);
    exit;
  }
  messagesMarkRead($driverId, $messageId);
  echo json_encode(['ok' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
