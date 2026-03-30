<?php require_once __DIR__ . '/auth.php'; ?>
<?php require_login(); ?>
<?php if(($_SESSION['rol']??'')==='observador'){ header("Location: index.php"); exit; } ?>

<?php require_once __DIR__ . '/header.php'; ?>
<?php
require_once __DIR__ . '/../config/db.php';
$uploadDir = __DIR__ . '/uploads'; if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
$errores = []; $ok = false;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Listas de opciones
$areas_responsables = [
    'Rectoría', 'Académica', 'Calidad', 'VcM', 'Adm y Fzas', 
    'Asuntos Estudiantiles', 'Informática', 'Estudiantes', 
    'Egresados', 'Docentes', 'Formación Contínua', 'Comité Consultivo'
];
$tipos_contraparte = [
    'Empresa', 'Empleador', 'Organismo Público', 'Gremio', 
    'Organización', 'Comunidad', 'Educación', 'Otro'
];
$etapas = [
    'Planificación', 'Ejecución', 'Seguimiento', 'Evaluación', 'Retroalimentación de Mejora'
];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    // Módulo 1: Institucional
    $titulo = trim($_POST['nombre'] ?? '');
    $tipo_v = $_POST['tipo_vinculacion'] ?? '';
    $es_proyecto = isset($_POST['es_proyecto']) ? 1 : 0;
    $area_resp = $_POST['area_responsable'] ?? '';
    $carrera = trim($_POST['carrera_ciclo'] ?? '');
    $rep_inst = trim($_POST['representante_institucion'] ?? '');

    // Módulo 2: Contraparte
    $razon_social = trim($_POST['razon_social'] ?? '');
    $tipo_contra = $_POST['tipo_contraparte'] ?? '';
    $rut_contra = trim($_POST['rut_contraparte'] ?? '');
    $tiene_convenio = isset($_POST['tiene_convenio']) ? 1 : 0;
    $convenio_id = !empty($_POST['convenio_id']) ? (int)$_POST['convenio_id'] : null;
    $rep_contra = trim($_POST['representante_contraparte'] ?? '');

    // Módulo 3: Actividad
    $tiene_resol = isset($_POST['tiene_resolucion']) ? 1 : 0;
    $n_resol = trim($_POST['n_resolucion'] ?? '');
    $rep_act_inst = trim($_POST['representante_actividad_inst'] ?? '');
    $rep_act_contra = trim($_POST['representante_actividad_contra'] ?? '');
    $ini = $_POST['fecha_inicio'] ?? '';
    $fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $dur = isset($_POST['duracion_horas']) ? (int)$_POST['duracion_horas'] : 0;
    $lugar = trim($_POST['lugar'] ?? '');
    $objetivo = trim($_POST['objetivo'] ?? '');
    $desc = trim($_POST['descripcion'] ?? '');
    $benef_inst = trim($_POST['beneficios_inst'] ?? '');
    $benef_contra = trim($_POST['beneficios_contra'] ?? '');
    $equipo = trim($_POST['equipo_trabajo'] ?? '');

    // Módulo 4: Estado y Avance
    $etapa = $_POST['etapa'] ?? 'Planificación';
    $hitos_esp = trim($_POST['hitos_esperados'] ?? '');
    $hitos_log = trim($_POST['hitos_logrados'] ?? '');
    $conclusiones = trim($_POST['conclusiones'] ?? '');
    $avance = isset($_POST['porcentaje_avance']) ? (int)$_POST['porcentaje_avance'] : 0;

    // Validaciones básicas
    if ($titulo === '') $errores[] = 'El nombre de la actividad es obligatorio.';
    if ($tipo_v === '') $errores[] = 'El tipo de vinculación es obligatorio.';
    if ($ini === '') $errores[] = 'La fecha de inicio es obligatoria.';
    if ($area_resp === '') $errores[] = 'El área responsable es obligatoria.';

    if (!$errores) {
        $anio = (int)date('Y', strtotime($ini));
        
        $sql = "INSERT INTO actividades (
            titulo, tipo_vinculacion, tipo, area_responsable, carrera_ciclo, representante_institucion,
            razon_social, tipo_contraparte, rut_contraparte, convenio_id, representante_contraparte,
            tiene_resolucion, n_resolucion, representante_actividad_inst, representante_actividad_contra,
            fecha_inicio, fecha_fin, duracion_horas, lugar, objetivo, descripcion,
            beneficios_inst, beneficios_contra, equipo_trabajo,
            etapa, hitos_esperados, hitos_logrados, conclusiones, porcentaje_avance,
            anio, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $st = $conn->prepare($sql);
        if ($st) {
            $tipo_enum = $es_proyecto ? 'Proyecto' : 'Actividad'; // Ajustado según ENUM correcto de DB
            $st->bind_param(
                'sssssssssisssssssissssssssssii',
                $titulo, $tipo_v, $tipo_enum, $area_resp, $carrera, $rep_inst,
                $razon_social, $tipo_contra, $rut_contra, $convenio_id, $rep_contra,
                $tiene_resol, $n_resol, $rep_act_inst, $rep_act_contra,
                $ini, $fin, $dur, $lugar, $objetivo, $desc,
                $benef_inst, $benef_contra, $equipo,
                $etapa, $hitos_esp, $hitos_log, $conclusiones, $avance,
                $anio
            );
            
            if ($st->execute()) {
                $ok = true;
                $new_id = $st->insert_id;
                // Manejo de archivos (Evidencias) - Mismo código anterior
                if (!empty($_FILES['evidencias']['name'][0])) {
                    $total = count($_FILES['evidencias']['name']);
                    for ($i = 0; $i < $total; $i++) {
                        if ($_FILES['evidencias']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp = $_FILES['evidencias']['tmp_name'][$i];
                            $name = basename($_FILES['evidencias']['name'][$i]);
                            $safe = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
                            $dest = $uploadDir . '/' . $safe;
                            if (move_uploaded_file($tmp, $dest)) {
                                $ruta_rel = 'uploads/' . $safe;
                                $conn->query("INSERT INTO evidencias (actividad_id, tipo, ruta, created_at) VALUES ($new_id, 'otro', '$ruta_rel', NOW())");
                            }
                        }
                    }
                }
            } else {
                $errores[] = "Error al guardar: " . $st->error;
            }
            $st->close();
        } else {
            $errores[] = "Error de preparación: " . $conn->error;
        }
    }
}

// Cargar convenios para el select
$convenios_opts = [];
$rc = $conn->query("SELECT id, nombre FROM convenios ORDER BY nombre ASC");
if ($rc) while($row = $rc->fetch_assoc()) $convenios_opts[] = $row;
?>

<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva Actividad VCM</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head><body>
<div class="layout"><div class="layout-inner">
  <div class="form-header">
    <h1 class="form-title"><span class="material-icons">add_circle</span>Nueva Actividad / Proyecto</h1>
    <p class="form-subtitle">Complete los 4 módulos de información requeridos</p>
  </div>

  <?php if($ok): ?>
    <div class="form-section" style="border-color:#bbf7d0;background:#ecfdf5">
        <div style="display:flex; align-items:center; gap:10px; color:#166534;">
            <span class="material-icons">check_circle</span>
            <strong>¡Registro exitoso!</strong>
        </div>
        <p style="margin:10px 0 0 34px; color:#15803d;">La actividad ha sido guardada correctamente.</p>
        <div class="form-actions"><a class="btn btn-primary" href="index.php">Volver al Dashboard</a></div>
    </div>
  <?php elseif($errores): ?>
    <div class="form-section" style="border-color:#fecaca;background:#fef2f2">
      <strong style="color:#991b1b;">Por favor corrija los siguientes errores:</strong>
      <ul style="color:#b91c1c; margin-top:10px;"><?php foreach($errores as $e){ echo '<li>'.h($e).'</li>'; } ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-grid">
    
    <!-- MÓDULO 1: INFORMACIÓN INSTITUCIONAL -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">account_balance</span>Módulo 1: Información Institucional</h2>
      <div class="form-grid">
        <div class="form-group full-width">
            <label class="form-label">Nombre de la Actividad <span class="required">*</span></label>
            <input class="form-input" type="text" name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo de Vinculación <span class="required">*</span></label>
                <select class="form-select" name="tipo_vinculacion" required>
                    <option value="">Seleccione...</option>
                    <option value="Extensión" <?= (($_POST['tipo_vinculacion']??'')=='Extensión')?'selected':'' ?>>Extensión</option>
                    <option value="Difusión" <?= (($_POST['tipo_vinculacion']??'')=='Difusión')?'selected':'' ?>>Difusión</option>
                    <option value="Académica" <?= (($_POST['tipo_vinculacion']??'')=='Académica')?'selected':'' ?>>Académica</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Área Responsable <span class="required">*</span></label>
                <select class="form-select" name="area_responsable" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($areas_responsables as $ar): ?>
                        <option value="<?= $ar ?>" <?= (($_POST['area_responsable']??'')==$ar)?'selected':'' ?>><?= $ar ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Carrera / Ciclo</label>
                <input class="form-input" type="text" name="carrera_ciclo" value="<?= h($_POST['carrera_ciclo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Representante Institución (Nombre y Contacto)</label>
                <input class="form-input" type="text" name="representante_institucion" value="<?= h($_POST['representante_institucion'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-checkbox">
                <input type="checkbox" name="es_proyecto" value="1" <?= isset($_POST['es_proyecto'])?'checked':'' ?>>
                <span>¿Es un Proyecto Bidireccional?</span>
            </label>
        </div>
      </div>
    </div>

    <!-- MÓDULO 2: INFORMACIÓN CONTRAPARTE -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">business</span>Módulo 2: Información Contraparte – Actor Externo</h2>
      <div class="form-grid">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Razón Social / Organización</label>
                <input class="form-input" type="text" name="razon_social" value="<?= h($_POST['razon_social'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Contraparte</label>
                <select class="form-select" name="tipo_contraparte">
                    <option value="">Seleccione...</option>
                    <?php foreach($tipos_contraparte as $tc): ?>
                        <option value="<?= $tc ?>" <?= (($_POST['tipo_contraparte']??'')==$tc)?'selected':'' ?>><?= $tc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">RUT / Identificación</label>
                <input class="form-input" type="text" name="rut_contraparte" value="<?= h($_POST['rut_contraparte'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Representante Contraparte (Nombre y Contacto)</label>
                <input class="form-input" type="text" name="representante_contraparte" value="<?= h($_POST['representante_contraparte'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-checkbox" onclick="document.getElementById('div_convenio').style.display = this.querySelector('input').checked ? 'block' : 'none'">
                <input type="checkbox" name="tiene_convenio" value="1" <?= isset($_POST['tiene_convenio'])?'checked':'' ?>>
                <span>¿Tiene Convenio asociado?</span>
            </label>
        </div>
        
        <div class="form-group" id="div_convenio" style="display: <?= isset($_POST['tiene_convenio'])?'block':'none' ?>;">
            <label class="form-label">Seleccionar Convenio</label>
            <select class="form-select" name="convenio_id">
                <option value="">-- Buscar Convenio --</option>
                <?php foreach($convenios_opts as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (($_POST['convenio_id']??'')==$c['id'])?'selected':'' ?>><?= h($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
      </div>
    </div>

    <!-- MÓDULO 3: ACTIVIDAD -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">event</span>Módulo 3: Detalles de la Actividad</h2>
      <div class="form-grid">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Fecha Inicio <span class="required">*</span></label>
                <input class="form-input" type="date" name="fecha_inicio" required value="<?= h($_POST['fecha_inicio'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha Término</label>
                <input class="form-input" type="date" name="fecha_fin" value="<?= h($_POST['fecha_fin'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Duración Estimada (Horas)</label>
                <input class="form-input" type="number" name="duracion_horas" min="0" value="<?= h($_POST['duracion_horas'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Lugar de Ejecución</label>
            <input class="form-input" type="text" name="lugar" value="<?= h($_POST['lugar'] ?? '') ?>">
        </div>

        <div class="form-group full-width">
            <label class="form-label">Objetivo General</label>
            <textarea class="form-textarea" name="objetivo" rows="2"><?= h($_POST['objetivo'] ?? '') ?></textarea>
        </div>
        <div class="form-group full-width">
            <label class="form-label">Descripción General</label>
            <textarea class="form-textarea" name="descripcion" rows="3"><?= h($_POST['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Beneficios Institucionales</label>
                <textarea class="form-textarea" name="beneficios_inst" rows="3"><?= h($_POST['beneficios_inst'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Beneficios Contraparte</label>
                <textarea class="form-textarea" name="beneficios_contra" rows="3"><?= h($_POST['beneficios_contra'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Equipo de Trabajo (Nombre - Cargo - Contacto)</label>
            <textarea class="form-textarea" name="equipo_trabajo" rows="2" placeholder="Ej: Juan Pérez - Coordinador - jperez@email.com"><?= h($_POST['equipo_trabajo'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-checkbox" onclick="document.getElementById('div_resol').style.display = this.querySelector('input').checked ? 'block' : 'none'">
                    <input type="checkbox" name="tiene_resolucion" value="1" <?= isset($_POST['tiene_resolucion'])?'checked':'' ?>>
                    <span>¿Tiene Resolución Asociada?</span>
                </label>
            </div>
            <div class="form-group" id="div_resol" style="display: <?= isset($_POST['tiene_resolucion'])?'block':'none' ?>;">
                <label class="form-label">N° Resolución</label>
                <input class="form-input" type="text" name="n_resolucion" value="<?= h($_POST['n_resolucion'] ?? '') ?>">
            </div>
        </div>
      </div>
    </div>

    <!-- MÓDULO 4: ESTADO Y AVANCE -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">analytics</span>Módulo 4: Estado y Avance</h2>
      <div class="form-grid">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Etapa Actual</label>
                <select class="form-select" name="etapa">
                    <?php foreach($etapas as $e): ?>
                        <option value="<?= $e ?>" <?= (($_POST['etapa']??'')==$e)?'selected':'' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">% Avance</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="range" class="form-range" name="porcentaje_avance" min="0" max="100" value="<?= (int)($_POST['porcentaje_avance']??0) ?>" oninput="this.nextElementSibling.value = this.value + '%'" style="flex:1">
                    <output style="font-weight:bold; width:40px; text-align:right;"><?= (int)($_POST['porcentaje_avance']??0) ?>%</output>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Hitos y Resultados Esperados</label>
                <textarea class="form-textarea" name="hitos_esperados" rows="3"><?= h($_POST['hitos_esperados'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hitos y Resultados Logrados</label>
                <textarea class="form-textarea" name="hitos_logrados" rows="3"><?= h($_POST['hitos_logrados'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Conclusiones y/o Recomendaciones</label>
            <textarea class="form-textarea" name="conclusiones" rows="3"><?= h($_POST['conclusiones'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- EVIDENCIAS -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">attach_file</span>Evidencias</h2>
      <div class="file-upload-section">
        <span class="material-icons file-upload-icon">cloud_upload</span>
        <p>Arrastre archivos aquí o haga clic para seleccionar</p>
        <label class="file-upload-label">
          Seleccionar Archivos
          <input type="file" name="evidencias[]" class="file-upload-input" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
        </label>
        <div class="file-list" id="fileList"></div>
      </div>
      <div class="form-hint" style="margin-top:.75rem">Actas, Listados, Fotos, Videos, Audios, etc.</div>
    </div>

    <div class="form-actions">
      <a class="btn btn-secondary" href="index.php"><span class="material-icons">close</span>Cancelar</a>
      <button class="btn btn-primary" type="submit"><span class="material-icons">save</span>Guardar Actividad</button>
    </div>
  </form>
</div></div>

<script>
// Drag & Drop + lista simple visual
document.addEventListener('DOMContentLoaded', function(){
  const input=document.querySelector('.file-upload-input');
  const list=document.getElementById('fileList');
  const box=document.querySelector('.file-upload-section');
  if(!input||!list||!box) return;

  function formatSize(bytes){ if(bytes<1024) return bytes+' B'; if(bytes<1048576) return Math.round(bytes/1024)+' KB'; return Math.round(bytes/1048576)+' MB'; }
  function iconFrom(file){ const t=file.type||''; if(t.startsWith('image/')) return 'image'; if(t.includes('pdf')) return 'picture_as_pdf'; if(t.includes('sheet')||t.includes('excel')) return 'table_chart'; if(t.includes('word')||t.includes('doc')) return 'description'; return 'attach_file'; }
  function render(files){
    list.innerHTML='';
    Array.from(files).forEach((f,i)=>{
      const div=document.createElement('div'); div.className='file-item fade-in';
      div.innerHTML=`<div class="file-info"><span class="material-icons file-icon">${iconFrom(f)}</span><div><div class="file-name">${f.name}</div><div class="file-type">${formatSize(f.size)}</div></div></div>`;
      list.appendChild(div);
    });
  }
  input.addEventListener('change', e=>render(e.target.files));
  box.addEventListener('dragover', e=>{ e.preventDefault(); box.style.borderColor='#26416e'; box.style.background='#f3f4f6'; });
  box.addEventListener('dragleave', e=>{ e.preventDefault(); box.style.borderColor='#e5e7eb'; box.style.background='#fafafa'; });
  box.addEventListener('drop', e=>{ e.preventDefault(); box.style.borderColor='#e5e7eb'; box.style.background='#fafafa'; input.files=e.dataTransfer.files; render(input.files); });
});
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
</body></html>
