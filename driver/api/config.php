<?php
/**
 * Public config for driver portal (publishable key only).
 */
header('Content-Type: application/json');
$configPath = dirname(__DIR__, 2) . '/dynamic.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$pk = $config['stripePublishableKey'] ?? getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
echo json_encode(['stripePublishableKey' => trim((string) $pk)]);
