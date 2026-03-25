<?php
// archivo: public/convenios/editar_convenio.php
require_once __DIR__ . '/../auth.php';
require_login();
if(($_SESSION['rol']??'')==='observador'){ header("Location: ../index.php"); exit; }
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/db.php';
// header.php se incluye DESPUÉS de la lógica de redirección

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* =====================================================
   1. Si viene por POST → actualizar datos y archivos
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['delete_convenio'])) {
  $id            = (int)($_POST['id'] ?? 0);
  $nombre        = trim($_POST['nombre'] ?? '');
  $tipo          = trim($_POST['tipo'] ?? '');
  $contraparte   = trim($_POST['contraparte'] ?? '');
  $responsable   = trim($_POST['responsable'] ?? '');
  $fecha_inicio  = trim($_POST['fecha_inicio'] ?? '');
  $fecha_fin     = trim($_POST['fecha_fin'] ?? '');
  $fecha_fin     = trim($_POST['fecha_fin'] ?? '');
  $observaciones = trim($_POST['observaciones'] ?? '');

  if ($id > 0 && $nombre && $tipo && $contraparte && $fecha_inicio) {
    $upd = $conn->prepare("UPDATE convenios SET nombre=?, tipo=?, contraparte=?, responsable=?, fecha_inicio=?, fecha_fin=?, observaciones=? WHERE id=?");
    $upd->bind_param("sssssssi", $nombre, $tipo, $contraparte, $responsable, $fecha_inicio, $fecha_fin, $observaciones, $id);
    $upd->execute();
    $upd->close();

    // ====== Subir nuevo archivo PDF (opcional) ======
    if (!empty($_FILES['archivo']['tmp_name'])) {
      $uploadDir = __DIR__ . '/../uploads/convenios/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
      $fileName = time() . '_' . basename($_FILES['archivo']['name']);
      $dest = $uploadDir . $fileName;
      if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
        $stmt = $conn->prepare("INSERT INTO evidencias_convenios (convenio_id, nombre, ruta, created_at)
                                VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('iss', $id, $_FILES['archivo']['name'], $fileName);
        $stmt->execute();
        $stmt->close();
      }
    }

    header("Location: editar_convenio.php?id=$id&msg=ok");
    exit;
  }
}

/* =====================================================
   2. Eliminar archivo PDF (si viene ?delete=ID)
   ===================================================== */
if (isset($_GET['delete']) && isset($_GET['id'])) {
  $del_id = (int)$_GET['delete'];
  $id     = (int)$_GET['id'];

  $sel = $conn->prepare("SELECT ruta FROM evidencias_convenios WHERE id=? AND convenio_id=?");
  $sel->bind_param('ii', $del_id, $id);
  $sel->execute();
  $r = $sel->get_result()->fetch_assoc();
  $sel->close();

  if ($r) {
    $path = __DIR__ . '/../uploads/convenios/' . $r['ruta'];
    if (is_file($path)) @unlink($path);
    $del = $conn->prepare("DELETE FROM evidencias_convenios WHERE id=? AND convenio_id=?");
    $del->bind_param('ii', $del_id, $id);
    $del->execute();
    $del->close();
  }

  header("Location: editar_convenio.php?id=$id&msg=archivo_eliminado");
  exit;
}

/* =====================================================
   3. Eliminar convenio completo (POST delete_convenio)
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_convenio'])) {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    // Eliminar archivos asociados
    $res = $conn->prepare("SELECT ruta FROM evidencias_convenios WHERE convenio_id=?");
    $res->bind_param('i', $id);
    $res->execute();
    $rows = $res->get_result();
    while ($row = $rows->fetch_assoc()) {
      $filePath = __DIR__ . '/../uploads/convenios/' . $row['ruta'];
      if (is_file($filePath)) @unlink($filePath);
    }
    $res->close();

    // Eliminar registros de evidencias
    $conn->query("DELETE FROM evidencias_convenios WHERE convenio_id=$id");

    // Eliminar archivo principal
    $sel = $conn->prepare("SELECT archivo FROM convenios WHERE id=?");
    $sel->bind_param('i', $id);
    $sel->execute();
    $pdf = $sel->get_result()->fetch_assoc()['archivo'] ?? '';
    $sel->close();
    if ($pdf && is_file(__DIR__ . '/../uploads/convenios/' . $pdf)) {
      @unlink(__DIR__ . '/../uploads/convenios/' . $pdf);
    }

    // Eliminar convenio
    $del = $conn->prepare("DELETE FROM convenios WHERE id=?");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();

    header("Location: ../index.php?msg=convenio_eliminado");
    exit;
  }
}

/* =====================================================
   4. Mostrar formulario con datos del convenio
   ===================================================== */
$id = isset($_GET['id']) ? (int)$_GET['id'] : ($id ?? 0);
if ($id <= 0) {
  header("Location: ver_convenios.php?msg=error_id");
  exit;
}

$stmt = $conn->prepare("SELECT * FROM convenios WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$convenio = $res->fetch_assoc();
$stmt->close();

if (!$convenio) {
  echo "<p style='color:red;text-align:center;margin-top:2rem'>Convenio no encontrado.</p>";
  exit;
}

/* =====================================================
   5. Consultar archivos asociados
   ===================================================== */
$evidencias = [];
$q = $conn->prepare("SELECT id, nombre, ruta, created_at FROM evidencias_convenios WHERE convenio_id=? ORDER BY id DESC");
$q->bind_param('i', $id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $evidencias[] = $row;
$q->close();

require_once __DIR__ . '/../header.php';
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar Convenio</title>

  <link rel="stylesheet" href="../assets/style.css?v=18">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
  <style>
    .alert {margin:1rem 0;padding:0.8rem 1rem;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5rem;}
    .alert-success {background:#ecfdf5;color:#065f46;border-left:5px solid #10b981;}
    .alert-warning {background:#fef9c3;color:#92400e;border-left:5px solid #fbbf24;}
    .btn {display:inline-flex;align-items:center;justify-content:center;gap:4px;min-width:120px;}
    .file-item {display:flex;justify-content:space-between;align-items:center;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:8px;background:#fff;}
    .file-info {display:flex;align-items:center;gap:10px;}
    .file-icon {color:#1d4ed8;}
    .danger-zone {border:1px solid #fee2e2;background:#fef2f2;padding:1.5rem;border-radius:10px;text-align:center;}
    .danger-zone h4 {color:#b91c1c;margin-bottom:1rem;}
  </style>
</head>
<body>

  <div class="layout">
    <div class="layout-inner">

      <header class="page-header">
        <h1 class="page-title"><span class="material-icons">edit</span> Editar Convenio</h1>
        <p class="page-subtitle">Modifica la información, gestiona archivos y elimina convenios</p>
      </header>

      <?php if (isset($_GET['msg']) && $_GET['msg']==='ok'): ?>
        <div class="alert alert-success" id="alert-msg">
          <span class="material-icons">check_circle</span> Convenio actualizado correctamente.
        </div>
      <?php elseif (isset($_GET['msg']) && $_GET['msg']==='archivo_eliminado'): ?>
        <div class="alert alert-warning" id="alert-msg">
          <span class="material-icons">delete</span> Archivo eliminado correctamente.
        </div>
      <?php endif; ?>

      <div class="card form-card">
        <form method="POST" enctype="multipart/form-data" class="form-body">
          <input type="hidden" name="id" value="<?= (int)$convenio['id'] ?>">

          <section class="form-section">
            <h3 class="section-title"><span class="material-icons">info</span> Información del Convenio</h3>

            <div class="grid-2">
              <div class="form-group"><label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" value="<?= h($convenio['nombre']) ?>" required></div>

              <div class="form-group"><label class="form-label">Tipo *</label>
                <select name="tipo" class="form-select" required>
                  <?php
                    $tipos = ['Académico','Colaboración','Práctica Profesional','Extensión','Otro'];
                    foreach ($tipos as $t) {
                      $sel = ($t == $convenio['tipo']) ? 'selected' : '';
                      echo "<option value='".h($t)."' $sel>".h($t)."</option>";
                    }
                  ?>
                </select>
              </div>

              <div class="form-group"><label class="form-label">Contraparte *</label>
                <input type="text" name="contraparte" class="form-control" value="<?= h($convenio['contraparte']) ?>" required></div>

              <div class="form-group"><label class="form-label">Responsable Interno</label>
                <input type="text" name="responsable" class="form-control" value="<?= h($convenio['responsable']) ?>"></div>

              <div class="form-group"><label class="form-label">Fecha Inicio *</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= h($convenio['fecha_inicio']) ?>" required></div>

              <div class="form-group"><label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= h($convenio['fecha_fin']) ?>"></div>



              <div class="form-group col-span-2"><label class="form-label">Observaciones</label>
                <textarea name="observaciones" rows="3" class="form-control"><?= h($convenio['observaciones']) ?></textarea></div>

              <div class="form-group col-span-2"><label class="form-label">Agregar nuevo PDF (opcional)</label>
                <input type="file" name="archivo" accept="application/pdf" class="form-control"></div>
            </div>
          </section>

          <section class="form-section">
            <h3 class="section-title"><span class="material-icons">picture_as_pdf</span> Archivo Principal</h3>
            <?php if (!empty($convenio['archivo'])): ?>
              <div class="file-item">
                <div class="file-info">
                  <span class="material-icons file-icon">picture_as_pdf</span>
                  <div><?= h($convenio['archivo']) ?><br><small>PDF principal</small></div>
                </div>
                <a href="../uploads/convenios/<?= h($convenio['archivo']) ?>" target="_blank" class="btn btn-secondary">
                  <span class="material-icons">open_in_new</span> Abrir
                </a>
              </div>
            <?php else: ?><p class="form-hint">No hay PDF principal.</p><?php endif; ?>
          </section>

          <section class="form-section">
            <h3 class="section-title"><span class="material-icons">attach_file</span> Evidencias</h3>
            <?php if ($evidencias): foreach ($evidencias as $e): ?>
              <div class="file-item">
                <div class="file-info"><span class="material-icons">description</span>
                  <div><strong><?= h($e['nombre']) ?></strong><br><small><?= h($e['created_at']) ?></small></div>
                </div>
                <div style="display:flex;gap:8px;">
                  <a href="../uploads/convenios/<?= h($e['ruta']) ?>" target="_blank" class="btn btn-secondary">
                    <span class="material-icons">open_in_new</span> Abrir</a>
                  <a href="editar_convenio.php?id=<?= $id ?>&delete=<?= (int)$e['id'] ?>"
                     class="btn btn-danger"
                     onclick="return confirm('¿Seguro que deseas eliminar este archivo?');">
                    <span class="material-icons">delete</span> Eliminar</a>
                </div>
              </div>
            <?php endforeach; else: ?><p class="form-hint">No hay archivos asociados.</p><?php endif; ?>
          </section>

          <div class="form-actions">
            <a href="ver_convenios.php" class="btn btn-secondary">
              <span class="material-icons">arrow_back</span> Volver</a>
            <button type="submit" class="btn btn-primary">
              <span class="material-icons">save</span> Guardar Cambios</button>
          </div>
        </form>


      </div>

    </div>
  </div>

  <script>
    const alertBox = document.getElementById('alert-msg');
    if (alertBox) setTimeout(()=>alertBox.style.display='none', 3000);
  </script>
</body>
<?php require_once __DIR__ . '/../footer.php'; ?>
</html>
