<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

// SEGURIDAD: Solo Admin entra aquí
require_login();
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg = '';

// LÓGICA: CREAR O EDITAR
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // Usamos el email como 'usuario' interno si es necesario, o lo dejamos igual.
    // Para compatibilidad, guardaremos el email en el campo usuario también, o la parte antes del @.
    $usuario = explode('@', $email)[0]; 
    
    $rol = $_POST['rol'] ?? 'observador';
    $password = $_POST['password'] ?? '';
    
    $roles_validos = ['admin', 'editor', 'observador'];
    if (!in_array($rol, $roles_validos)) $rol = 'observador';

    if (!empty($_POST['user_id'])) {
        // EDICIÓN
        $id = (int)$_POST['user_id'];
        
        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_BCRYPT);
            // Actualizamos usuario = email (o parte) para mantener consistencia
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, rol=?, password_hash=? WHERE id=?");
            $stmt->bind_param("sssssi", $nombre, $email, $email, $rol, $passHash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, rol=? WHERE id=?");
            $stmt->bind_param("ssssi", $nombre, $email, $email, $rol, $id);
        }
        
        if ($stmt->execute()) {
            $msg = "Usuario actualizado correctamente.";
        } else {
            $msg = "Error al actualizar: " . $conn->error;
        }
        $stmt->close();
    } else {
        // CREACIÓN
        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)");
            // Guardamos email en campo usuario también para evitar problemas de unique key si existe
            $stmt->bind_param("sssss", $nombre, $email, $email, $passHash, $rol);
            
            if ($stmt->execute()) {
                $msg = "Usuario creado exitosamente.";
            } else {
                $msg = "Error al crear: " . $conn->error;
            }
            $stmt->close();
        } else {
            $msg = "La contraseña es obligatoria para nuevos usuarios.";
        }
    }
}

// LÓGICA: ELIMINAR
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['uid']) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Usuario eliminado.";
        } else {
            $msg = "Error al eliminar.";
        }
        $stmt->close();
    } else {
        $msg = "Error: No puedes eliminar tu propia cuenta.";
    }
}

// Obtener usuarios
$res = $conn->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = [];
while ($row = $res->fetch_assoc()) {
    $usuarios[] = $row;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administración de Usuarios</title>
</head>
<body>
<?php require_once __DIR__ . '/header.php'; ?>

<div class="layout">
  <div class="layout-inner">

    <div class="form-header" style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h1 class="form-title"><span class="material-icons">manage_accounts</span> Control de Usuarios</h1>
        <p class="form-subtitle">Gestión de accesos y roles del sistema</p>
      </div>
      <button class="btn btn-primary shadow-sm" onclick="abrirModal()">
        <span class="material-icons">person_add</span> Nuevo Usuario
      </button>
    </div>

    <?php if($msg): ?>
        <div style="padding:12px; border-radius:8px; background:#ecfdf5; color:#065f46; border:1px solid #10b981; margin-bottom:20px;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
      <table class="vcm-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($usuarios as $u): ?>
          <tr>
            <td>
                <div style="font-weight:600;"><?= htmlspecialchars($u['nombre'] ?? '') ?></div>
            </td>
            <td>
                <div><?= htmlspecialchars($u['email'] ?? '') ?></div>
            </td>
            <td>
                <?php 
                    $rol = $u['rol'] ?? 'observador';
                    $bg = '#e5e7eb'; $fg = '#374151';
                    if($rol == 'admin') { $bg = '#1f2937'; $fg = '#fff'; }
                    if($rol == 'editor') { $bg = '#3b82f6'; $fg = '#fff'; }
                ?>
                <span style="background:<?= $bg ?>; color:<?= $fg ?>; padding:4px 8px; border-radius:4px; font-size:0.8em; text-transform:uppercase;"><?= $rol ?></span>
            </td>
            <td>
                <?= (int)$u['activo'] ? '<span class="badge ok">Activo</span>' : '<span class="badge off">Inactivo</span>' ?>
            </td>
            <td style="display:flex; gap:5px;">
                <button class="btn-action btn-edit" 
                    onclick="editarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre']??'') ?>', '<?= htmlspecialchars($u['email']??'') ?>', '<?= $u['rol']??'observador' ?>')">
                    <span class="material-icons">edit</span>
                </button>
                <?php if($u['id'] != $_SESSION['uid']): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn-action btn-del" onclick="return confirm('¿Eliminar usuario?');">
                        <span class="material-icons">delete</span>
                    </a>
                <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- MODAL (Bootstrap 5) -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header" style="background:var(--azul); color:white;">
                    <h5 class="modal-title" id="modalTitulo">Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" id="userNombre" class="form-control" required placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" id="userEmail" class="form-control" required placeholder="nombre.apellido@ippilotopardo.cl">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol" id="userRol" class="form-select">
                                <option value="observador">Observador</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="******">
                        </div>
                    </div>
                    <div class="form-text text-end" id="passHelp">Obligatoria para nuevos usuarios.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    var myModal = new bootstrap.Modal(document.getElementById('modalUser'));

    function abrirModal() {
        document.getElementById('modalTitulo').innerText = "Nuevo Usuario";
        document.getElementById('userId').value = "";
        document.getElementById('userNombre').value = "";
        document.getElementById('userEmail').value = "";
        document.getElementById('userRol').value = "observador";
        document.getElementById('passHelp').innerText = "Obligatoria para crear usuario.";
        myModal.show();
    }

    function editarUsuario(id, nombre, email, rol) {
        document.getElementById('modalTitulo').innerText = "Editar Usuario";
        document.getElementById('userId').value = id;
        document.getElementById('userNombre').value = nombre;
        document.getElementById('userEmail').value = email;
        document.getElementById('userRol').value = rol;
        document.getElementById('passHelp').innerText = "Dejar en blanco para mantener la clave actual.";
        myModal.show();
    }
</script>
</body>
</html>
