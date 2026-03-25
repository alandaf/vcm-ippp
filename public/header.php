<?php
// /vcm/public/partials/header.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('vcm_sess');
  session_start();
}
if (!defined('APP_BASE')) define('APP_BASE', '/vcm/public');
$uname = htmlspecialchars($_SESSION['uname'] ?? 'Usuario');
$rol = $_SESSION['rol'] ?? 'Admin'; 
$page = basename($_SERVER['PHP_SELF']);
?>
<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts (Roboto) -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Material Icons (Keep for legacy compatibility if needed, but prefer FontAwesome) -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<nav class="site-header fixed-top">
    <div class="header-shell">
        <a href="<?= APP_BASE ?>/index.php" class="header-brand">
            <img src="/vcm/img/logo_IPP_PNG.png" alt="Logo IPP" class="header-logo">
            <div class="header-text d-none d-md-block">
                <strong>I.P. Escuela de Marina Mercante Piloto Pardo</strong><br>
                <small>Plataforma de Vinculación con el Medio</small>
            </div>
        </a>

        <div class="header-right">
            <ul class="nav-menu d-none d-md-flex">
                <li><a href="<?= APP_BASE ?>/index.php" class="<?= $page == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="<?= APP_BASE ?>/estadisticas.php" class="<?= $page == 'estadisticas.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Estadísticas</a></li>
                <?php if($rol !== 'observador'): ?>
                    <li><a href="<?= APP_BASE ?>/form.php" class="<?= $page == 'form.php' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Nueva Actividad</a></li>
                    <li><a href="<?= APP_BASE ?>/convenios/registro_convenio.php" class="<?= strpos($_SERVER['PHP_SELF'], 'convenio') !== false ? 'active' : '' ?>"><i class="fas fa-handshake"></i> Nuevo Convenio</a></li>
                <?php endif; ?>
                <?php if($rol === 'admin'): ?>
                    <li><a href="<?= APP_BASE ?>/usuarios.php" class="<?= $page == 'usuarios.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Usuarios</a></li>
                <?php endif; ?>
            </ul>

            <div class="vr mx-3 text-white opacity-25 d-none d-md-block" style="border-left: 1px solid rgba(255,255,255,0.2); height: 30px; margin: 0 15px;"></div>

            <div class="dropdown" style="position: relative;">
                <a href="#" class="user-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff;">
                    <div class="user-avatar" style="width: 38px; height: 38px; background: #fff; color: #0b1d3a; border: 2px solid #facc15; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">
                        <?= strtoupper(substr($uname, 0, 1)) ?>
                    </div>
                    <span class="user-name d-none d-lg-block" style="font-size: 0.9rem;"><?= $uname ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow mt-2 border-0">
                    <li><span class="dropdown-header" style="display: block; padding: 0.5rem 1rem; font-size: 0.8rem; color: #6c757d;">Rol: <?= ucfirst($rol) ?></span></li>
                    <li><hr class="dropdown-divider" style="margin: 0.5rem 0; border-top: 1px solid #e9ecef;"></li>
                    <li><a class="dropdown-item text-danger" href="<?= APP_BASE ?>/logout.php" style="display: block; padding: 0.5rem 1rem; color: #dc3545; text-decoration: none;"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
    /* =========================
       GLOBAL STYLES (Migrated from style.css)
       ========================= */
    :root {
        --azul: #0b1d3a;
        --warn: #facc15;
        --bg: #f5f7fb;
        --card: #fff;
    }
    body {
        margin: 0;
        background: var(--bg);
        font-family: 'Roboto', system-ui, -apple-system, sans-serif;
        color: #0f172a;
        padding-top: 75px; /* Space for fixed header */
    }
    * { box-sizing: border-box; }

    /* Layout Containers */
    .layout { display: flex; justify-content: center; align-items: flex-start; padding: 2rem; }
    .layout-inner { width: 100%; max-width: 1400px; background: var(--card); border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 1.5rem; }
    
    /* Page Headers */
    .page-header { display: flex; flex-direction: column; gap: .5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.25rem; }
    .page-title { margin: 0; font-size: 2rem; color: var(--azul); display: flex; align-items: center; gap: .6rem; font-weight: 800; }
    .page-subtitle { margin: 0; color: #64748b; }

    /* ESTILOS NAVBAR (Copiados de PGD) */
    .site-header { background: #0b1d3a; color: #d9e2f2; border-bottom: 4px solid #facc15; height: 75px; display: flex; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; width: 100%; z-index: 1000; }
    .header-shell { width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .header-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
    .header-logo { height: 48px; width: auto; object-fit: contain; }
    .header-text { line-height: 1.2; } .header-text strong { color: #fff; font-size: 0.85rem; } .header-text small { color: #cbd5e1; font-size: 0.7rem; text-transform: uppercase; }
    .header-right { display: flex; align-items: center; }
    .nav-menu { list-style: none; margin: 0; padding: 0; display: flex; gap: 5px; }
    .nav-menu a { color: #d9e2f2; text-decoration: none; font-weight: 500; font-size: 0.9rem; padding: 8px 15px; border-radius: 50px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
    .nav-menu a:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
    .nav-menu a.active { background: rgba(250, 204, 21, 0.2); color: #facc15; font-weight: 700; }
    
    /* Dropdown simple CSS override */
    .dropdown:hover .dropdown-menu { display: block; }
    .dropdown-item:hover { background: #f8f9fa; }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .header-text { display: none; }
        .nav-menu { display: none; }
        .layout { padding: 1rem; }
        .layout-inner { padding: 1rem; }
    }

    /* Tables (Global) */
    .vcm-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .vcm-table th, .vcm-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .vcm-table th { background-color: #f8fafc; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .vcm-table tr:hover { background-color: #f1f5f9; }
    
    /* Action Buttons */
    .btn-action { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .btn-view { background: #fff; border-color: #cbd5e1; color: #475569; }
    .btn-edit { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
    .btn-del { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .btn-view:hover { background: #f1f5f9; border-color: #94a3b8; }
    .btn-edit:hover { background: #dbeafe; border-color: #93c5fd; }
    .btn-del:hover { background: #fee2e2; border-color: #fca5a5; }
    
    /* General Buttons */
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 500; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; font-size: 0.9rem; }
    .btn-primary { background: var(--azul); color: #fff; }
    .btn-primary:hover { background: #1e3a8a; box-shadow: 0 4px 12px rgba(11, 29, 58, 0.2); }
    .btn-secondary { background: #fff; color: #64748b; border: 1px solid #cbd5e1; }
    .btn-secondary:hover { background: #f8fafc; color: #475569; border-color: #94a3b8; }

    /* FORM STYLES (PGD Style) */
    .form-header { margin-bottom: 2rem; }
    .form-title { font-size: 1.75rem; font-weight: 700; color: var(--azul); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 10px; }
    .form-subtitle { color: #64748b; margin: 0; font-size: 1rem; }
    
    .form-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; position: relative; }
    .section-title { font-size: 1.1rem; font-weight: 600; color: var(--azul); margin: 0 0 1.5rem 0; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
    .section-title .material-icons { font-size: 1.2rem; color: #94a3b8; }

    .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-group.full-width { grid-column: 1 / -1; }

    .form-label { font-size: 0.9rem; font-weight: 500; color: #334155; }
    .required { color: #ef4444; margin-left: 2px; }
    .form-hint { font-size: 0.8rem; color: #94a3b8; margin-top: 0.25rem; }

    .form-input, .form-select, .form-textarea {
        width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1;
        font-family: inherit; font-size: 0.95rem; color: #1e293b; background: #fff;
        transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: var(--azul); outline: none; box-shadow: 0 0 0 3px rgba(11, 29, 58, 0.1);
    }
    .form-textarea { resize: vertical; min-height: 100px; }

    .form-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
    .form-checkbox input { width: 18px; height: 18px; accent-color: var(--azul); cursor: pointer; }
    .form-checkbox span { font-size: 0.95rem; color: #334155; }

    .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; }

    /* File Upload */
    .file-upload-section { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 2rem; text-align: center; background: #f8fafc; transition: all 0.2s; position: relative; }
    .file-upload-section:hover { border-color: #cbd5e1; background: #f1f5f9; }
    .file-upload-icon { font-size: 3rem; color: #94a3b8; margin-bottom: 1rem; display: block; }
    .file-upload-label { display: inline-block; background: #fff; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-weight: 500; color: #475569; cursor: pointer; transition: all 0.2s; margin-top: 1rem; }
    .file-upload-label:hover { background: #f1f5f9; border-color: #94a3b8; }
    .file-upload-input { display: none; }
    
    .file-list { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; text-align: left; }
    .file-item { background: #fff; border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem; animation: fadeIn 0.3s ease; }
    .file-item .file-icon { color: var(--azul); }
    .file-info { flex: 1; }
    .file-name { font-weight: 500; color: #334155; font-size: 0.9rem; }
    .file-type { font-size: 0.75rem; color: #94a3b8; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
