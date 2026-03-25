<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

// Fetch all columns
$sql = "SELECT * FROM actividades WHERE id=? LIMIT 1";
$st = $conn->prepare($sql); $st->bind_param('i', $id); $st->execute(); $res = $st->get_result(); $act = $res->fetch_assoc(); $st->close();
if(!$act){ header('Location: index.php'); exit; }

// Fetch institution name if needed
$inst_name = $act['institucion'] ?? '';
if(!$inst_name && isset($act['institucion_id'])){
  $iid = (int)$act['institucion_id']; $r2 = $conn->prepare("SELECT nombre FROM instituciones WHERE id=?"); if($r2){ $r2->bind_param('i',$iid); $r2->execute(); $r3=$r2->get_result(); $row=$r3->fetch_assoc(); $inst_name = $row ? $row['nombre'] : ''; $r2->close(); }
}

// Evidencias
$evid = [];
$chk = $conn->query("SHOW TABLES LIKE 'evidencias'");
if($chk && $chk->num_rows){
  $st2 = $conn->prepare("SELECT id, tipo, ruta, created_at FROM evidencias WHERE actividad_id=? ORDER BY id DESC");
  if($st2){ $st2->bind_param('i',$id); $st2->execute(); $r4=$st2->get_result(); while($r4 && $e=$r4->fetch_assoc()){ $evid[]=$e; } $st2->close(); }
}

include __DIR__ . '/header.php';
?>

<div class="layout">
    <div class="layout-inner">
        <header class="page-header">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1 class="page-title"><span class="material-icons">visibility</span> Detalle de Actividad</h1>
                    <p class="page-subtitle">ID #<?= (int)$act['id'] ?> - <?= h($act['titulo'] ?? $act['nombre'] ?? '') ?></p>
                </div>
                <div>
                    <button class="btn btn-secondary" onclick="exportPDF()">
                        <span class="material-icons">picture_as_pdf</span> Exportar PDF
                    </button>
                </div>
            </div>
        </header>

        <div id="printableArea">
            <div id="pdf-logo" style="text-align:center; margin-bottom:20px; display:none;">
                <img src="../img/logo_ipp_con_nombre.png" alt="Logo IPP" style="max-width:300px;">
            </div>
            <!-- Módulo 1: Institucional -->
            <div class="form-section">
                <h2 class="section-title"><span class="material-icons">account_balance</span> Información Institucional</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre Actividad</label>
                            <div class="form-hint"><?= h($act['titulo'] ?? $act['nombre'] ?? '') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo Vinculación</label>
                            <div class="form-hint"><?= h($act['tipo_vinculacion'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Área Responsable</label>
                            <div class="form-hint"><?= h($act['area_responsable'] ?? '—') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Carrera/Ciclo</label>
                            <div class="form-hint"><?= h($act['carrera_ciclo'] ?? '—') ?></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Representante Institución</label>
                            <div class="form-hint"><?= h($act['representante_institucion'] ?? '—') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">¿Es Proyecto?</label>
                            <div class="form-hint"><?= (isset($act['tipo']) && strtolower($act['tipo'])=='proyecto') ? 'Sí' : 'No' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo 2: Contraparte -->
            <div class="form-section">
                <h2 class="section-title"><span class="material-icons">business</span> Información Contraparte</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Razón Social</label>
                            <div class="form-hint"><?= h($act['razon_social'] ?? '—') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo Contraparte</label>
                            <div class="form-hint"><?= h($act['tipo_contraparte'] ?? '—') ?></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RUT</label>
                            <div class="form-hint"><?= h($act['rut_contraparte'] ?? '—') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Representante Contraparte</label>
                            <div class="form-hint"><?= h($act['representante_contraparte'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Módulo 3: Detalles -->
            <div class="form-section">
                <h2 class="section-title"><span class="material-icons">event</span> Detalles de la Actividad</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Fecha Inicio</label>
                            <div class="form-hint"><?= $act['fecha_inicio'] ? date('d-m-Y', strtotime($act['fecha_inicio'])) : '—' ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha Término</label>
                            <div class="form-hint"><?= $act['fecha_fin'] ? date('d-m-Y', strtotime($act['fecha_fin'])) : '—' ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duración (hrs)</label>
                            <div class="form-hint"><?= h($act['duracion_horas'] ?? '—') ?></div>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Lugar</label>
                        <div class="form-hint"><?= h($act['lugar'] ?? '—') ?></div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Objetivo General</label>
                        <div class="form-hint"><?= h($act['objetivo'] ?? '—') ?></div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Descripción</label>
                        <div class="form-hint"><?= nl2br(h($act['descripcion'] ?? '—')) ?></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Beneficios Institucionales</label>
                            <div class="form-hint"><?= nl2br(h($act['beneficios_inst'] ?? '—')) ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Beneficios Contraparte</label>
                            <div class="form-hint"><?= nl2br(h($act['beneficios_contra'] ?? '—')) ?></div>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Equipo de Trabajo</label>
                        <div class="form-hint"><?= nl2br(h($act['equipo_trabajo'] ?? '—')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Módulo 4: Estado y Avance -->
            <div class="form-section">
                <h2 class="section-title"><span class="material-icons">analytics</span> Estado y Avance</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Etapa Actual</label>
                            <div class="form-hint"><?= h($act['etapa'] ?? 'Planificación') ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">% Avance</label>
                            <div class="form-hint">
                                <div style="background:#e5e7eb; border-radius:4px; height:20px; width:100%; position:relative; overflow:hidden;">
                                    <div style="background:#10b981; height:100%; width:<?= (int)($act['porcentaje_avance']??0) ?>%;"></div>
                                    <span style="position:absolute; top:0; left:50%; transform:translateX(-50%); font-size:12px; line-height:20px; color:#000;">
                                        <?= (int)($act['porcentaje_avance']??0) ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Hitos Esperados</label>
                            <div class="form-hint"><?= nl2br(h($act['hitos_esperados'] ?? '—')) ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Hitos Logrados</label>
                            <div class="form-hint"><?= nl2br(h($act['hitos_logrados'] ?? '—')) ?></div>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Conclusiones</label>
                        <div class="form-hint"><?= nl2br(h($act['conclusiones'] ?? '—')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Evidencias -->
            <div class="form-section">
                <h2 class="section-title"><span class="material-icons">attach_file</span> Evidencias</h2>
                <?php if($evid): ?>
                    <div class="file-list">
                        <?php foreach($evid as $e): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <span class="material-icons file-icon">insert_drive_file</span>
                                    <div>
                                        <div class="file-name"><?= h(basename($e['ruta'])) ?></div>
                                        <div class="file-type"><?= h($e['created_at']) ?></div>
                                    </div>
                                </div>
                                <a class="btn btn-secondary btn-sm" href="<?= h($e['ruta']) ?>" target="_blank">
                                    <span class="material-icons">visibility</span> Ver
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="form-hint">No hay evidencias adjuntas.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-secondary" href="index.php"><span class="material-icons">arrow_back</span> Volver</a>
            <?php if(($_SESSION['rol']??'')!=='observador'): ?>
            <a class="btn btn-primary" href="editar.php?id=<?= (int)$act['id'] ?>"><span class="material-icons">edit</span> Editar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    // Clone the element to not affect the view
    const element = document.getElementById('printableArea').cloneNode(true);
    
    // Show the logo in the clone
    const logo = element.querySelector('#pdf-logo');
    if(logo) logo.style.display = 'block';

    const opt = {
      margin:       0.5,
      filename:     'Actividad_VCM_<?= (int)$act['id'] ?>.pdf',
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

<?php require_once __DIR__ . '/footer.php'; ?>
