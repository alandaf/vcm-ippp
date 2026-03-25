<?php
// /vcm/public/403.php
declare(strict_types=1);
http_response_code(403);

// Opcional: mensaje dinámico desde la sesión, si quieres mostrar por qué se bloqueó
session_name('vcm_sess');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$why = $_SESSION['forbidden_reason'] ?? '';
unset($_SESSION['forbidden_reason']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>403 — Acceso denegado</title>
  <link rel="stylesheet" href="/vcm/public/assets/style.css?v=12">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    .error-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f7fb;padding:24px}
    .error-card{background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.08);padding:32px;max-width:720px;width:100%;text-align:center;border:2px solid #e5e7eb}
    .error-icon{display:inline-flex;width:72px;height:72px;border-radius:50%;align-items:center;justify-content:center;background:#fff3f3;border:2px solid #fecaca;color:#b91c1c;margin-bottom:12px}
    .error-title{margin:0 0 6px;color:#1f3559;font-size:1.9rem;font-weight:800}
    .error-sub{margin:0 0 20px;color:#6b7280}
    .why{margin:8px 0 0;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:8px 12px;display:inline-block}
    .actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 16px;border:none;border-radius:10px;font-weight:700;cursor:pointer;text-decoration:none}
    .btn-primary{background:#26416e;color:#fff}
    .btn-secondary{background:#eef2f7;border:2px solid #d6dde9;color:#1f3559}
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="error-card">
      <div class="error-icon"><span class="material-icons">block</span></div>
      <h1 class="error-title">403 — Acceso denegado</h1>
      <p class="error-sub">No tienes permisos para acceder a esta sección.</p>
      <?php if ($why): ?>
        <div class="why"><?= htmlspecialchars($why, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="actions">
        <a href="/vcm/public/index.php" class="btn btn-secondary">
          <span class="material-icons">home</span> Volver al inicio
        </a>
        <a href="/vcm/public/logout.php" class="btn btn-primary">
          <span class="material-icons">logout</span> Cerrar sesión
        </a>
      </div>
    </div>
  </div>
</body>
</html>
