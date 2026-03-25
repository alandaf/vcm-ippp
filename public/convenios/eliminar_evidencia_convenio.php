<?php
// ================================================
// eliminar_evidencia_convenio.php
// Elimina una evidencia asociada a un convenio
// y redirige con mensaje visual de confirmación
// ================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../auth.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$convenio_id = isset($_GET['convenio_id']) ? (int)$_GET['convenio_id'] : 0;

// Validar parámetros
if ($id <= 0 || $convenio_id <= 0) {
  header("Location: ver_convenios.php?id={$convenio_id}&msg=error");
  exit;
}

// Buscar evidencia
$stmt = $conn->prepare("SELECT ruta FROM evidencias_convenios WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$file = $res->fetch_assoc();
$stmt->close();

if ($file) {
  // Ruta física del archivo
  $filePath = __DIR__ . '/../../public/uploads/convenios/' . basename($file['ruta']);

  // Eliminar archivo físico si existe
  if (file_exists($filePath)) {
    unlink($filePath);
  }

  // Eliminar registro en base de datos
  $del = $conn->prepare("DELETE FROM evidencias_convenios WHERE id = ?");
  $del->bind_param('i', $id);
  $del->execute();
  $del->close();

  // Redirigir con mensaje de éxito
  header("Location: ver_convenios.php?id={$convenio_id}&msg=evidencia_ok");
  exit;
}

// Si algo falla
header("Location: ver_convenios.php?id={$convenio_id}&msg=error");
exit;
?>
