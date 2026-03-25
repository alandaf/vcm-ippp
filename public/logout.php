<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

// Destruye la sesión y cookies
destroy_session_and_cookies();

// Mensaje opcional en login
header('Location: /vcm/public/login.php?msg=logout');
exit;
