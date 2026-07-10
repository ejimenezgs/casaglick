<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode([
    'ok' => false,
    'message' => 'Método no permitido.'
  ]);
  exit;
}

function clean_field($value) {
  return trim(strip_tags((string) $value));
}

function load_env_file($path) {
  if (!is_readable($path)) {
    return [];
  }

  $values = [];
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

  foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }

    $parts = explode('=', $line, 2);

    if (count($parts) !== 2) {
      continue;
    }

    $key = trim($parts[0]);
    $value = trim($parts[1]);
    $value = trim($value, "\"'");

    if ($key !== '') {
      $values[$key] = $value;
    }
  }

  return $values;
}

function smtp_read($connection) {
  $data = '';

  while ($line = fgets($connection, 515)) {
    $data .= $line;

    if (strlen($line) >= 4 && $line[3] === ' ') {
      break;
    }
  }

  return $data;
}

function smtp_command($connection, $command, $expectedCodes = []) {
  if ($command !== null) {
    fwrite($connection, $command . "\r\n");
  }

  $response = smtp_read($connection);
  $code = substr($response, 0, 3);

  if (!in_array($code, $expectedCodes, true)) {
    throw new Exception('SMTP error: ' . trim($response));
  }

  return $response;
}

function smtp_send_mail($config, $fromEmail, $fromName, $toEmail, $replyToEmail, $replyToName, $subject, $plainBody) {
  $host = $config['SMTP_HOST'] ?? '';
  $port = (int) ($config['SMTP_PORT'] ?? 465);
  $username = $config['SMTP_USER'] ?? '';
  $password = $config['SMTP_PASS'] ?? '';
  $secure = strtolower($config['SMTP_SECURE'] ?? 'ssl');

  if ($host === '' || $username === '' || $password === '' || $toEmail === '') {
    throw new Exception('Faltan variables SMTP en el archivo .env.');
  }

  $target = ($secure === 'ssl' ? 'ssl://' : '') . $host;
  $connection = stream_socket_client($target . ':' . $port, $errno, $errstr, 20);

  if (!$connection) {
    throw new Exception('No se pudo conectar al servidor SMTP: ' . $errstr);
  }

  stream_set_timeout($connection, 20);

  smtp_command($connection, null, ['220']);
  smtp_command($connection, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'casaglick.com'), ['250']);

  if ($secure === 'tls') {
    smtp_command($connection, 'STARTTLS', ['220']);
    stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    smtp_command($connection, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'casaglick.com'), ['250']);
  }

  smtp_command($connection, 'AUTH LOGIN', ['334']);
  smtp_command($connection, base64_encode($username), ['334']);
  smtp_command($connection, base64_encode($password), ['235']);
  smtp_command($connection, 'MAIL FROM:<' . $fromEmail . '>', ['250']);
  smtp_command($connection, 'RCPT TO:<' . $toEmail . '>', ['250', '251']);
  smtp_command($connection, 'DATA', ['354']);

  $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
  $safeFromName = mb_encode_mimeheader($fromName, 'UTF-8');
  $safeReplyName = mb_encode_mimeheader($replyToName, 'UTF-8');

  $headers = [];
  $headers[] = 'From: ' . $safeFromName . ' <' . $fromEmail . '>';
  $headers[] = 'To: <' . $toEmail . '>';
  $headers[] = 'Reply-To: ' . $safeReplyName . ' <' . $replyToEmail . '>';
  $headers[] = 'Subject: ' . $encodedSubject;
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-Type: text/plain; charset=UTF-8';
  $headers[] = 'Content-Transfer-Encoding: 8bit';

  $message = implode("\r\n", $headers) . "\r\n\r\n" . $plainBody;
  $message = str_replace("\r\n.", "\r\n..", $message);

  fwrite($connection, $message . "\r\n.\r\n");
  smtp_command($connection, null, ['250']);
  smtp_command($connection, 'QUIT', ['221']);
  fclose($connection);

  return true;
}

$env = array_merge(
  load_env_file(__DIR__ . '/.env'),
  getenv() ?: []
);

$name = clean_field($_POST['name'] ?? $_POST['nombre'] ?? '');
$email = clean_field($_POST['email'] ?? $_POST['correo'] ?? '');
$phone = clean_field($_POST['phone'] ?? $_POST['telefono'] ?? '');
$project = clean_field($_POST['project'] ?? $_POST['proyecto'] ?? '');
$message = clean_field($_POST['message'] ?? $_POST['mensaje'] ?? '');

if ($name === '' || $email === '' || $message === '') {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'message' => 'Por favor completa nombre, correo y mensaje.'
  ]);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'message' => 'El correo no es válido.'
  ]);
  exit;
}

$to = $env['SMTP_TO'] ?? 'e.jimenez@gruposegel.com';
$fromEmail = $env['SMTP_FROM'] ?? ($env['SMTP_USER'] ?? 'form@gruposegel.com');
$fromName = $env['SMTP_FROM_NAME'] ?? 'Casa Glick Web';
$subject = $env['SMTP_SUBJECT'] ?? 'Nuevo lead desde Casa Glick';

$body = "Nuevo mensaje desde el formulario de Casa Glick:\n\n";
$body .= "Nombre: " . $name . "\n";
$body .= "Correo: " . $email . "\n";
$body .= "Teléfono: " . ($phone !== '' ? $phone : 'No especificado') . "\n";
$body .= "Tipo de proyecto: " . ($project !== '' ? $project : 'No especificado') . "\n\n";
$body .= "Mensaje:\n" . $message . "\n\n";
$body .= "---\n";
$body .= "Sitio: casaglick.com\n";
$body .= "Enviado desde el formulario web.";

try {
  smtp_send_mail($env, $fromEmail, $fromName, $to, $email, $name, $subject, $body);

  echo json_encode([
    'ok' => true,
    'message' => 'Mensaje enviado correctamente.'
  ]);
} catch (Throwable $error) {
  error_log('[Casa Glick Form] ' . $error->getMessage());

  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'No se pudo enviar el mensaje. Intenta de nuevo.'
  ]);
}
?>
