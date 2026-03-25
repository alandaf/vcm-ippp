<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../auth.php';
require_login();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../index.php'); exit; }

// Obtener convenio
$stmt = $conn->prepare("SELECT * FROM convenios WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$conv = $res->fetch_assoc();
$stmt->close();

if (!$conv) {
  header('Location: ../index.php?err=not_found');
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ver Convenio</title>
</head>
<body>
<?php require_once __DIR__ . '/../header.php'; ?>

<div class="layout">
  <div class="layout-inner">

    <div class="form-header">
      <div style="display:flex; justify-content:space-between; align-items:center;">
          <div>
              <h1 class="form-title"><span class="material-icons">visibility</span> Ver Convenio</h1>
              <p class="form-subtitle">Visualización completa del convenio institucional</p>
          </div>
          <div>
              <button class="btn btn-secondary" onclick="exportPDF()">
                  <span class="material-icons">picture_as_pdf</span> Exportar PDF
              </button>
          </div>
      </div>
    </div>

    <!-- 🔔 MENSAJE DE ALERTA -->
    <?php if (isset($_GET['msg'])): ?>
      <?php
        $msg = $_GET['msg'];
        $map = [
          'evidencia_ok' => ['✅ Evidencia eliminada correctamente.', 'success'],
          'error'        => ['❌ Ocurrió un error al eliminar el registro.', 'error']
        ];
        if (isset($map[$msg])):
          [$texto, $tipo] = $map[$msg];
          $bg = $tipo === 'success' ? '#ecfdf5' : '#fee2e2';
          $fg = $tipo === 'success' ? '#065f46' : '#991b1b';
          $bd = $tipo === 'success' ? '#10b981' : '#ef4444';
      ?>
        <div style="padding:12px 16px; border-radius:10px; margin-bottom:16px; font-weight:600; background:<?= $bg ?>; color:<?= $fg ?>; border:2px solid <?= $bd ?>;">
          <?= $texto ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div id="printableArea">
        <div id="pdf-logo" style="text-align:center; margin-bottom:20px; display:none;">
            <img src="../../img/logo_ipp_con_nombre.png" alt="Logo IPP" style="max-width:300px;">
        </div>
        <!-- Información principal -->
        <div class="form-section">
          <h2 class="section-title"><span class="material-icons">info</span> Información del Convenio</h2>
          <div class="form-grid">
            <div class="form-row">
              <div class="form-group"><label class="form-label">Nombre:</label><div class="form-hint"><?= h($conv['nombre']) ?></div></div>
              <div class="form-group"><label class="form-label">Tipo:</label><div class="form-hint"><?= h($conv['tipo']) ?></div></div>
              <div class="form-group"><label class="form-label">Contraparte:</label><div class="form-hint"><?= h($conv['contraparte']) ?></div></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Responsable:</label><div class="form-hint"><?= h($conv['responsable']) ?></div></div>
              <div class="form-group"><label class="form-label">Inicio:</label><div class="form-hint"><?= $conv['fecha_inicio'] ? date('d-m-Y', strtotime($conv['fecha_inicio'])) : '—' ?></div></div>
              <div class="form-group"><label class="form-label">Fin:</label><div class="form-hint"><?= $conv['fecha_fin'] ? date('d-m-Y', strtotime($conv['fecha_fin'])) : '—' ?></div></div>
            </div>
            <div class="form-group full-width">
              <label class="form-label">Observaciones:</label>
              <div class="form-hint"><?= nl2br(h($conv['observaciones'])) ?></div>
            </div>
          </div>
        </div>

        <!-- Archivos -->
        <div class="form-section">
          <h2 class="section-title"><span class="material-icons">description</span> Archivo Principal</h2>
          <?php if (!empty($conv['archivo'])): ?>
            <?php
              $fileName = basename($conv['archivo']);
              $fileUrl = '/vcm/public/uploads/convenios/' . rawurlencode($fileName);
            ?>
            <div class="file-list">
              <div class="file-item">
                <div class="file-info">
                  <span class="material-icons file-icon">picture_as_pdf</span>
                  <div><div class="file-name"><?= h($fileName) ?></div><div class="file-type">PDF principal</div></div>
                </div>
                <a class="btn btn-secondary" href="<?= $fileUrl ?>" target="_blank">
                  <span class="material-icons">open_in_new</span> Abrir
                </a>
              </div>
            </div>
          <?php else: ?>
            <p>No hay archivo principal.</p>
          <?php endif; ?>
        </div>

        <!-- Evidencias adicionales -->
        <?php
        $res = $conn->query("SELECT id, nombre, ruta, created_at FROM evidencias_convenios WHERE convenio_id = {$id} ORDER BY id DESC");
        if ($res && $res->num_rows > 0):
        ?>
          <div class="form-section">
            <h2 class="section-title"><span class="material-icons">attach_file</span> Evidencias</h2>
            <div class="file-list">
              <?php while($e = $res->fetch_assoc()): ?>
                <?php $fileUrl = '/vcm/public/uploads/convenios/' . rawurlencode(basename($e['ruta'])); ?>
                <div class="file-item">
                  <div class="file-info">
                    <span class="material-icons file-icon">insert_drive_file</span>
                    <div><div class="file-name"><?= h($e['nombre']) ?></div><div class="file-type"><?= h($e['created_at']) ?></div></div>
                  </div>
                  <div style="display:flex;gap:8px;">
                    <a class="btn btn-secondary" href="<?= $fileUrl ?>" target="_blank"><span class="material-icons">open_in_new</span> Abrir</a>
                    <a class="btn btn-danger"
                       href="eliminar_evidencia_convenio.php?id=<?= (int)$e['id'] ?>&convenio_id=<?= (int)$conv['id'] ?>"
                       onclick="return confirm('¿Seguro que deseas eliminar esta evidencia?');">
                       <span class="material-icons">delete</span> Eliminar
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
      <a class="btn btn-secondary" href="../index.php"><span class="material-icons">arrow_back</span> Volver</a>
      <a class="btn btn-primary" href="editar_convenio.php?id=<?= (int)$conv['id'] ?>"><span class="material-icons">edit</span> Editar</a>
    </div>

  </div>
</div>

<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    // Clone the element
    const element = document.getElementById('printableArea').cloneNode(true);
    
    // Show logo in clone
    const logo = element.querySelector('#pdf-logo');
    if(logo) logo.style.display = 'block';

    const opt = {
      margin:       0.5,
      filename:     'Convenio_VCM_<?= (int)$conv['id'] ?>.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

// Auto-print if requested
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('auto_print') === '1') {
        exportPDF();
    }
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
</body>
</html>
