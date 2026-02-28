<?php
/**
 * Send email via Zoho SMTP using PHPMailer.
 * Config: host, port, encryption (ssl|tls), user, pass in dynamic.json "smtp" object.
 *
 * @param string $to Recipient email
 * @param string $subject Subject line
 * @param string $bodyHtml HTML body
 * @param string|null $fromEmail Sender email (default from config)
 * @param string|null $fromName Sender name (default from config)
 * @param array|null $config Override config (host, port, user, pass)
 * @return bool True on success
 */

require_once __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendSmtpMail($to, $subject, $bodyHtml, $fromEmail = null, $fromName = null, $config = null) {

  if ($config === null) {
    $configPath = __DIR__ . '/dynamic.json';
    if (!is_file($configPath)) return false;
    $cfg = @json_decode(file_get_contents($configPath), true);
    $config = isset($cfg['smtp']) ? $cfg['smtp'] : [];
  }
  $host = $config['host'] ?? 'smtppro.zoho.eu';
  $port = (int) ($config['port'] ?? 465);
  $encryption = strtolower($config['encryption'] ?? 'ssl');
  $user = $config['user'] ?? '';
  $pass = $config['pass'] ?? '';
  if ($user === '' || $pass === '') return false;

  $fromEmail = $fromEmail ?? $user;
  $fromName = $fromName ?? 'No 5 Tyre & MOT';

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    $mail->SMTPSecure = ($encryption === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $bodyHtml;

    $mail->send();
    return true;
  } catch (Exception $e) {
    $logPath = __DIR__ . '/.smtp-error.log';
    @file_put_contents($logPath, date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    return false;
  }
}
