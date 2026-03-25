<?php
/**
 * config/db.php
 * Conexión MySQL/MariaDB + utilidades de esquema seguras.
 * ⚠️ Ajusta $user / $pass / $db si fuera necesario.
 */

// Cargar credenciales desde archivo separado
if (file_exists(__DIR__ . '/credentials.php')) {
    require_once __DIR__ . '/credentials.php';
} else {
    // Fallback o error si no existe el archivo
    die("Error: No se encuentra el archivo de configuración de base de datos (credentials.php).");
}

// Log de errores local
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../public/errors.log');

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  error_log('[VCM] DB connect error: ' . $conn->connect_error);
  die('Error de conexión a Base de Datos');
}
$conn->set_charset('utf8mb4');

/**
 * Devuelve true si existe la columna $column en la tabla $table dentro de la BD actual.
 * (Usa INFORMATION_SCHEMA — NO usar SHOW COLUMNS con placeholders)
 */
function col_exists(mysqli $conn, string $table, string $column): bool {
  $sql = "SELECT COUNT(*) AS c
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = ?
             AND COLUMN_NAME  = ?";
  $st = $conn->prepare($sql);
  if (!$st) return false;
  $st->bind_param('ss', $table, $column);
  $st->execute();
  $res = $st->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $st->close();
  return $row && (int)$row['c'] > 0;
}

/**
 * Mapa de columnas disponibles de una tabla: ['campo' => true, ...]
 */
function get_cols(mysqli $conn, string $table): array {
  $cols = [];
  $sql = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = ?";
  $st = $conn->prepare($sql);
  if (!$st) return $cols;
  $st->bind_param('s', $table);
  $st->execute();
  $res = $st->get_result();
  while ($res && ($r = $res->fetch_assoc())) {
    $cols[$r['COLUMN_NAME']] = true;
  }
  $st->close();
  return $cols;
}
