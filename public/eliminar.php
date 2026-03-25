<?php
// /vcm/public/eliminar.php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();                 // o usa: require_user('alanda');
if(($_SESSION['rol']??'')==='observador'){ header("Location: index.php"); exit; }
require_once __DIR__ . '/../config/db.php';

if (!function_exists('csrf_check')) {
  function csrf_check(string $t): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php?msg=metodo');
  exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$csrf = (string)($_POST['csrf'] ?? '');

if ($id <= 0 || !csrf_check($csrf)) {
  header('Location: index.php?msg=csrf');
  exit;
}

$conn->begin_transaction();

try {
  $hasEv = $conn->query("SHOW TABLES LIKE 'evidencias'");
  if ($hasEv && $hasEv->num_rows) {
    $stR = $conn->prepare("SELECT ruta FROM evidencias WHERE actividad_id = ?");
    if ($stR) {
      $stR->bind_param('i', $id);
      $stR->execute();
      $res = $stR->get_result();

      $uploadsBase = realpath(__DIR__ . '/uploads');
      while ($res && ($row = $res->fetch_assoc())) {
        $rel = ltrim((string)$row['ruta'], '/');
        if ($uploadsBase && is_dir($uploadsBase)) {
          $candidate = realpath($uploadsBase . '/' . $rel);
          if ($candidate && strpos($candidate, $uploadsBase) === 0 && is_file($candidate)) {
            @unlink($candidate);
          }
        } else {
          $abs = (string)$row['ruta'];
          if (is_file($abs)) { @unlink($abs); }
        }
      }
      $stR->close();
    }

    $stE = $conn->prepare("DELETE FROM evidencias WHERE actividad_id = ?");
    if ($stE) {
      $stE->bind_param('i', $id);
      $stE->execute();
      $stE->close();
    }
  }

  $stA = $conn->prepare("DELETE FROM actividades WHERE id = ?");
  if ($stA) {
    $stA->bind_param('i', $id);
    $stA->execute();
    $stA->close();
  }

  $conn->commit();
  header('Location: index.php?msg=eliminada');
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  header('Location: index.php?msg=error');
  exit;
}
