<?php
/**
 * Proxy for CheckCarDetails API – vehicle data by VRM.
 * Keeps API key server-side and avoids CORS.
 * GET ?vrm=XX00XXX
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, max-age=0');

$vrm = isset($_GET['vrm']) ? preg_replace('/\s+/', '', trim((string) $_GET['vrm'])) : '';
if ($vrm === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing VRM']);
  exit;
}

$apiKey = getenv('VRM_API_TOKEN');
if (!$apiKey || trim($apiKey) === '') {
  $configPath = __DIR__ . '/dynamic.json';
  if (is_file($configPath)) {
    $config = @json_decode(file_get_contents($configPath), true);
    if (!empty($config['vrmApiToken'])) {
      $apiKey = trim((string) $config['vrmApiToken']);
    }
  }
}
if (!$apiKey) {
  $apiKey = '85dca976faf339b9d37f8b4f6e3219f7';
}
$url = 'https://api.checkcardetails.co.uk/vehicledata/vehicleregistration?apikey=' . urlencode($apiKey) . '&vrm=' . urlencode(strtoupper($vrm));

$ctx = stream_context_create([
  'http' => [
    'method' => 'GET',
    'header' => "Accept: application/json\r\n",
    'timeout' => 10,
  ],
]);

$response = @file_get_contents($url, false, $ctx);
if ($response === false) {
  http_response_code(502);
  echo json_encode(['ok' => false, 'error' => 'Service temporarily unavailable']);
  exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
  http_response_code(502);
  echo json_encode(['ok' => false, 'error' => 'Invalid response from vehicle data service']);
  exit;
}

if (empty($data['registrationNumber'])) {
  echo json_encode(['ok' => false, 'error' => 'Vehicle not found']);
  exit;
}

echo json_encode($data);
