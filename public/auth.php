<?php
// /vcm/public/auth.php — Manejo de sesión y seguridad
declare(strict_types=1);

/* ----------------- CONFIG SESIÓN SEGURA ----------------- */
// ✅ Se inicia la sesión solo si aún no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('vcm_sess');
  session_set_cookie_params([
    // Use current directory or root for cookie path to allow subdirectory isolation
    'path'     => '/', // Allow cookie on root prevents path mismatch in deep dirs like vcm_production
    'httponly' => true,          // cookies no accesibles por JS
    'samesite' => 'Lax',         // mitiga CSRF básico
  ]);
  session_start();
}

/* ----------------- HEADERS ANTI-CACHE ----------------- */
function send_no_cache_headers(): void {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Cache-Control: post-check=0, pre-check=0', false);
  header('Pragma: no-cache');
  header('Expires: 0');
}

/* ----------------- ESTADO SESIÓN ----------------- */
function is_logged_in(): bool {
  return !empty($_SESSION['uid']);
}

function current_user(): ?string {
  return $_SESSION['uname'] ?? null;
}

/* ----------------- RESTRICCIONES ----------------- */
function require_login(): void {
  if (!is_logged_in()) {
    $here = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?next=' . urlencode($here));
    exit;
  }
  send_no_cache_headers(); // evita “Atrás” tras logout
}

function require_user(string $username): void {
  require_login();
  if (current_user() !== $username) {
    $_SESSION['forbidden_reason'] = 'Esta área es solo para el usuario autorizado.';
    header('Location: /vcm/public/403.php');
    exit;
  }
}

/* ----------------- CSRF ----------------- */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
  return $token && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ----------------- POST ONLY ----------------- */
function require_post_or_redirect(string $redirect = 'index.php'): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect?msg=metodo");
    exit;
  }
}

/* ----------------- LOGOUT HELPERS ----------------- */
function destroy_session_and_cookies(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000,
      $p['path'] ?? '/', $p['domain'] ?? '',
      !empty($p['secure']), true
    );
  }
  session_destroy();
}
