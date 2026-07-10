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

$to = 'e.jimenez@gruposegel.com';
$subject = 'Nuevo mensaje desde Casa Glick';

function clean_field($value) {
  return trim(strip_tags((string) $value));
}

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

$body = "Nuevo mensaje desde el formulario de Casa Glick:

";
$body .= "Nombre: " . $name . "
";
$body .= "Correo: " . $email . "
";
$body .= "Teléfono: " . ($phone !== '' ? $phone : 'No especificado') . "
";
$body .= "Tipo de proyecto: " . ($project !== '' ? $project : 'No especificado') . "

";
$body .= "Mensaje:
" . $message . "

";
$body .= "---
";
$body .= "Enviado desde casaglick.com";

$headers = "From: Casa Glick <no-reply@casaglick.com>
";
$headers .= "Reply-To: " . $email . "
";
$headers .= "Content-Type: text/plain; charset=UTF-8
";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
  echo json_encode([
    'ok' => true,
    'message' => 'Mensaje enviado correctamente.'
  ]);
} else {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'No se pudo enviar el mensaje. Intenta de nuevo.'
  ]);
}
?>
