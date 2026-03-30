<?php
/**
 * Plataforma VCM - Vinculación con el Medio
 * @author Andrés Landa Figueroa <andres.landa.f@gmail.com>
 * @version 2.1.0-prod
 */
declare(strict_types=1);
session_name('vcm_sess');
session_set_cookie_params([
  'path'     => '/vcm/public',
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../config/db.php';

// Si ya está logueado, al dashboard (respeta next si viene)
if (!empty($_SESSION['uid'])) {
  $next = $_GET['next'] ?? '/vcm/public/index.php';
  if (strpos($next, '/vcm/') !== 0) { $next = '/vcm/public/index.php'; }
  header('Location: ' . $next);
  exit;
}

// Rate limit por sesión/IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$_SESSION['login_attempts'][$ip] = $_SESSION['login_attempts'][$ip] ?? ['n'=>0,'t'=>time()];
$lim = &$_SESSION['login_attempts'][$ip];
$cooldown = 60; // 60s
$maxAttempts = 6;

$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if ($lim['n'] >= $maxAttempts && time()-$lim['t'] < $cooldown) {
    $err = 'Demasiados intentos. Intenta de nuevo en 1 minuto.';
  } else {
    // CSRF
    if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
      $err = 'Solicitud inválida (CSRF).';
    } else {
      $user = trim($_POST['email'] ?? '');
      $pass = (string)($_POST['password'] ?? '');

      // VCM usa 'email' para login
      $st = $conn->prepare("SELECT id, usuario, nombre, rol, password_hash, activo FROM usuarios WHERE email = ? LIMIT 1");
      $st->bind_param('s', $user);
      $st->execute();
      $res = $st->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $st->close();

      if ($row && (int)$row['activo'] === 1 && password_verify($pass, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$row['id'];
        $_SESSION['uname'] = $row['nombre'] ?: $row['usuario'];
        $_SESSION['rol'] = $row['rol'] ?: 'observador';
        $lim = ['n'=>0,'t'=>time()];
        unset($_SESSION['csrf']);
        $next = $_GET['next'] ?? '/vcm/public/index.php';
        if (strpos($next, '/vcm/') !== 0) { $next = '/vcm/public/index.php'; }
        header('Location: ' . $next);
        exit;
      } else {
        $err = 'Credenciales incorrectas o acceso denegado.';
        $lim['n']++;
        $lim['t'] = time();
      }
    }
  }
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso VCM - Instituto Piloto Pardo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body, html { height: 100%; font-family: 'Roboto', sans-serif; overflow: hidden; }
        :root { --ipp-blue: #0b1d3a; --ipp-red: #d32f2f; }
        .login-container { height: 100vh; }
        
        /* IZQUIERDA */
        .login-sidebar { background-color: white; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2; box-shadow: 5px 0 15px rgba(0,0,0,0.05); }
        .login-content { width: 100%; max-width: 400px; padding: 40px; }
        .logo-img { max-height: 80px; margin-bottom: 30px; }
        
        /* DERECHA */
        /* Usamos un color sólido o imagen genérica si no existe fondo_login.png */
        .login-image { background-color: #0b1d3a; background-image: url('/vcm/img/fondo_login.png'); background-size: cover; background-position: center; position: relative; }
        .login-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(11, 29, 58, 0.9) 0%, rgba(11, 29, 58, 0.6) 100%); }
        .login-caption { position: absolute; bottom: 50px; left: 50px; color: white; z-index: 3; max-width: 80%; }
        
        /* FORMULARIO */
        .form-floating > .form-control:focus ~ label { color: var(--ipp-blue); }
        .form-control:focus { border-color: var(--ipp-blue); box-shadow: 0 0 0 0.25rem rgba(11, 29, 58, 0.15); }
        .btn-login { background-color: var(--ipp-blue); border: none; padding: 12px; font-weight: 500; letter-spacing: 0.5px; transition: all 0.3s; }
        .btn-login:hover { background-color: #001a36; transform: translateY(-2px); }
        .border-left-brand { border-left: 4px solid var(--ipp-red); padding-left: 15px; }
    </style>
</head>
<body>

    <div class="container-fluid login-container">
        <div class="row h-100">
            
            <div class="col-md-5 col-lg-4 login-sidebar">
                <div class="login-content">
                    <div class="text-center">
                        <img src="/vcm/img/logo_IPP_PNG.png" alt="Logo IPP" class="logo-img" onerror="this.src='https://via.placeholder.com/200x80?text=LOGO+IPP'">
                    </div>

                    <div class="mb-5 border-left-brand">
                        <h3 class="fw-bold text-dark mb-1">Bienvenido</h3>
                        <p class="text-muted mb-0">Plataforma de Vinculación con el Medio</p>
                    </div>

                    <?php if($err): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4 border-0 bg-danger bg-opacity-10 text-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <small><?= htmlspecialchars($err) ?></small>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php<?= isset($_GET['next']) ? '?next='.urlencode($_GET['next']) : '' ?>">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" placeholder="nombre.apellido@ippilotopardo.cl" required autofocus>
                            <label>Correo Electrónico</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                            <label>Contraseña</label>
                        </div>
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-login btn-lg shadow-sm">INGRESAR AL SISTEMA</button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="mailto:andres.landa@ippilotopardo.cl?subject=Restaurar%20contrase%C3%B1a%20VCM" class="text-decoration-none text-secondary small">
                                <i class="fas fa-lock me-1"></i> ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </form>

                    <div class="text-center mt-5 pt-4 border-top">
                        <small class="text-muted" style="font-size: 0.75rem;">&copy; <?= date('Y') ?> Instituto Profesional Piloto Pardo.<br>Desarrollado por <a href="mailto:andres.landa.f@gmail.com" class="text-decoration-none text-muted fw-bold">Andrés Landa Figueroa</a></small>
                    </div>
                </div>
            </div>

            <div class="col-md-7 col-lg-8 login-image d-none d-md-block">
                <div class="login-overlay"></div>
                <div class="login-caption">
                    <h1 class="display-4 fw-bold mb-3">Vinculación con el Medio</h1>
                    <p class="lead text-white-50">Plataforma unificada para la gestión de actividades, convenios y proyectos institucionales.</p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
