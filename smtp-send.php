<?php
/**
 * Send email via Zoho SMTP. Tries SSL port 465 first, then TLS port 587.
 * Config: host, port, user, pass in dynamic.json "smtp" object.
 *
 * @param string $to Recipient email
 * @param string $subject Subject line
 * @param string $bodyHtml HTML body
 * @param string|null $fromEmail Sender email (default from config)
 * @param string|null $fromName Sender name (default from config)
 * @param array|null $config Override config (host, port, user, pass)
 * @return bool True on success
 */
function sendSmtpMail($to, $subject, $bodyHtml, $fromEmail = null, $fromName = null, $config = null) {
  if ($config === null) {
    $configPath = __DIR__ . '/dynamic.json';
    if (!is_file($configPath)) return false;
    $cfg = @json_decode(file_get_contents($configPath), true);
    $config = isset($cfg['smtp']) ? $cfg['smtp'] : [];
  }
  $host = $config['host'] ?? 'smtppro.zoho.com';
  $user = $config['user'] ?? '';
  $pass = $config['pass'] ?? '';
  if ($user === '' || $pass === '') return false;

  $fromEmail = $fromEmail ?? $user;
  $fromName = $fromName ?? 'No 5 Tyre & MOT';

  $tryPorts = [(int) ($config['port'] ?? 465), 587, 465];
  $tryPorts = array_unique($tryPorts);

  foreach ($tryPorts as $port) {
    $fp = null;
    if ($port === 465) {
      $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
      $fp = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    } else {
      $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
      if ($fp && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
        fclose($fp);
        $fp = false;
      }
    }
    if (!$fp) continue;

    $read = function () use ($fp) { return fgets($fp, 515); };
    $send = function ($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

    $read();
    $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    while ($line = $read()) {
      if (strlen($line) < 4 || substr($line, 3, 1) === ' ') break;
    }
    $send('AUTH LOGIN');
    $read();
    $send(base64_encode($user));
    $read();
    $send(base64_encode($pass));
    $auth = $read();
    if (strpos($auth, '235') === false) {
      fclose($fp);
      continue;
    }
    $send("MAIL FROM:<{$fromEmail}>");
    $read();
    $send("RCPT TO:<{$to}>");
    $read();
    $send('DATA');
    $read();
    $subjEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: {$fromName} <{$fromEmail}>\r\nTo: {$to}\r\nSubject: {$subjEnc}\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n";
    $send($headers . $bodyHtml);
    $send('.');
    $dataResp = $read();
    fclose($fp);
    if (strpos($dataResp, '250') !== false) return true;
  }
  return false;
}
