<?php
/**
 * Plataforma VCM - Vinculación con el Medio
 * @author Andrés Landa Figueroa <andres.landa.f@gmail.com>
 * @version 2.1.0-prod
 */
require_once __DIR__ . '/auth.php';
require_login();
if(($_SESSION['rol']??'')==='observador'){ header("Location: index.php"); exit; }
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function map_tipo_v($k){ $k=strtolower($k); if($k==='extension')return 'Extensión'; if($k==='difusion')return 'Difusión'; if($k==='academica')return 'Académica'; return $k; }
function map_estado($k){ $k=strtolower(trim((string)$k)); if($k==='planificada')return 'Planificada'; if($k==='en_curso'||$k==='en ejecución'||$k==='en ejecucion')return 'En ejecución'; if($k==='finalizada')return 'Finalizada'; return ucfirst($k); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

$errores = []; $ok = false;

/* === Cargar actividad existente === */
$sql = "SELECT * FROM actividades WHERE id=? LIMIT 1";
$st = $conn->prepare($sql);
$st->bind_param('i',$id);
$st->execute();
$res = $st->get_result();
$act = $res->fetch_assoc();
$st->close();
if(!$act){ header('Location: index.php'); exit; }

/* === Eliminar evidencia === */
if(isset($_POST['del_evidencia'])){
  $eid = (int)$_POST['del_evidencia'];
  $st = $conn->prepare("SELECT ruta FROM evidencias WHERE id=? AND actividad_id=?");
  $st->bind_param('ii',$eid,$id);
  $st->execute();
  $r=$st->get_result()->fetch_assoc();
  $st->close();
  if($r && is_file(__DIR__.'/'.$r['ruta'])) @unlink(__DIR__.'/'.$r['ruta']);
  $st = $conn->prepare("DELETE FROM evidencias WHERE id=? AND actividad_id=?");
  $st->bind_param('ii',$eid,$id);
  $st->execute();
  $st->close();
  $ok = true;
}

/* === Guardar cambios === */
if($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['del_evidencia'])){
  $titulo=trim($_POST['nombre']??'');
  $resp=trim($_POST['responsable']??'');
  $tipo_v=map_tipo_v($_POST['tipo_vinculacion']??'');
  $tipo=isset($_POST['es_proyecto'])?'Proyecto':'Actividad';
  $institucion=trim($_POST['institucion_text']??'');
  $carrera=trim($_POST['carrera_ciclo']??'');
  $ini = $_POST['fecha_inicio'] ?? '';
  $fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
  $dur=(int)($_POST['duracion_horas']??1);
  $objetivo=trim($_POST['objetivo']??'');
  $desc=trim($_POST['descripcion']??'');
  $result_e=trim($_POST['resultados_esperados']??'');
  $result_a=trim($_POST['resultados_alcanzados']??'');
  $aporte=trim($_POST['aporte_perfil']??'');
  $benef=trim($_POST['beneficiario']??'');
  $lugar=trim($_POST['lugar']??'');
  $estado=map_estado($_POST['estado']??'Planificada');
  $resol=trim($_POST['n_resolucion']??'');
  $obs=trim($_POST['observaciones']??'');
  $anio=(int)($_POST['anio']??date('Y'));
  $etapa=$_POST['etapa']??'Planificación';
  $avance=(int)($_POST['porcentaje_avance']??0);

  // Campos Contraparte
  $razon_social=trim($_POST['razon_social']??'');
  $tipo_contraparte=trim($_POST['tipo_contraparte']??'');
  $rut_contraparte=trim($_POST['rut_contraparte']??'');
  $rep_contraparte=trim($_POST['representante_contraparte']??'');

  if($titulo==='')$errores[]='El nombre/título es obligatorio.';
  if($tipo_v==='')$errores[]='El tipo de vinculación es obligatorio.';
  if($ini==='')$errores[]='La fecha de inicio es obligatoria.';
  if($dur<=0)$errores[]='La duración debe ser mayor a 0.';
  if($objetivo==='')$errores[]='El objetivo general es obligatorio.';

  if(!$errores){
    $sqlU="UPDATE actividades SET 
      titulo=?, nombre=?, responsable=?, tipo_vinculacion=?, tipo=?, carrera_ciclo=?,
      fecha_inicio=?, fecha_fin=?, duracion_horas=?, objetivo=?, descripcion=?, resultados_esperados=?,
      resultados_alcanzados=?, aporte_perfil=?, beneficiario=?, lugar=?, estado=?, n_resolucion=?, observaciones=?, anio=?,
      etapa=?, porcentaje_avance=?,
      razon_social=?, tipo_contraparte=?, rut_contraparte=?, representante_contraparte=?
      WHERE id=?";
    
    $st=$conn->prepare($sqlU);

    if(!$st){
      $errores[] = "Error en prepare(): ".h($conn->error);
    } else {
      $st->bind_param('ssssssssissssssssssiisssssi',
        $titulo,$titulo,$resp,$tipo_v,$tipo,$carrera,
        $ini,$fin,$dur,$objetivo,$desc,$result_e,$result_a,$aporte,
        $benef,$lugar,$estado,$resol,$obs,$anio,$etapa,$avance,
        $razon_social, $tipo_contraparte, $rut_contraparte, $rep_contraparte,
        $id
      );

      if(!$st->execute()){
        $errores[] = "Error al ejecutar UPDATE: ".h($st->error);
      } else {
        $ok = true;
      }
      $st->close();
    }

    /* === Subida de nuevas evidencias === */
    if(!empty($_FILES['evidencias']['name'][0])){
      $total=count($_FILES['evidencias']['name']);
      for($i=0;$i<$total;$i++){
        if($_FILES['evidencias']['error'][$i]===UPLOAD_ERR_OK){
          $tmp=$_FILES['evidencias']['tmp_name'][$i];
          $name=basename($_FILES['evidencias']['name'][$i]);
          $safe=time().'_'.preg_replace('/[^a-zA-Z0-9_\.-]/','_',$name);
          $dest=$uploadDir.'/'.$safe;
          if(move_uploaded_file($tmp,$dest)){
            $tipo_evi=$_POST['tipo_evidencia'][$i]??'otro';
            $ruta_rel='uploads/'.$safe;
            $st2=$conn->prepare("INSERT INTO evidencias (actividad_id, tipo, ruta, created_at) VALUES (?,?,?,NOW())");
            $st2->bind_param('iss',$id,$tipo_evi,$ruta_rel);
            $st2->execute(); $st2->close();
          }
        }
      }
    }
    
    // Recargar datos actualizados
    $st = $conn->prepare("SELECT * FROM actividades WHERE id=? LIMIT 1");
    $st->bind_param('i',$id);
    $st->execute();
    $act = $st->get_result()->fetch_assoc();
    $st->close();
  }
}

/* === Cargar evidencias existentes === */
$ev_list = [];
$q=$conn->prepare("SELECT id, tipo, ruta FROM evidencias WHERE actividad_id=? ORDER BY id DESC");
$q->bind_param('i',$id);
$q->execute();
$r=$q->get_result();
while($r && ($row=$r->fetch_assoc())) $ev_list[]=$row;
$q->close();
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editar Actividad VCM</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head><body>
<div class="layout"><div class="layout-inner">
  <div class="form-header">
    <h1 class="form-title"><span class="material-icons">edit</span>Editar Actividad VCM</h1>
    <p class="form-subtitle">Actualiza los datos de la actividad o proyecto de vinculación</p>
  </div>

  <?php if($ok): ?>
  <div class="form-section" style="border-color:#bbf7d0;background:#ecfdf5">
    <div style="display:flex; align-items:center; gap:10px; color:#166534;">
        <span class="material-icons">check_circle</span>
        <strong>¡Cambios guardados correctamente!</strong>
    </div>
  </div>
  <?php elseif($errores): ?>
  <div class="form-section" style="border-color:#fecaca;background:#fef2f2">
    <strong style="color:#991b1b;">Corrige lo siguiente:</strong>
    <ul style="color:#b91c1c; margin-top:10px;"><?php foreach($errores as $e){ echo "<li>".h($e)."</li>"; } ?></ul>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-grid">
    
    <!-- Módulo 1: Información Básica -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">info</span>Información Básica</h2>
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Nombre de la Actividad <span class="required">*</span></label>
          <input class="form-input" type="text" name="nombre" required value="<?= h($act['titulo'] ?? $act['nombre'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo de Vinculación <span class="required">*</span></label>
            <?php $tv=strtolower($act['tipo_vinculacion'] ?? ''); ?>
            <select class="form-select" name="tipo_vinculacion" required>
              <option value="extension" <?= (strpos($tv,'ext')===0)?'selected':'' ?>>Extensión</option>
              <option value="difusion"  <?= (strpos($tv,'dif')===0)?'selected':'' ?>>Difusión</option>
              <option value="academica" <?= (strpos($tv,'acad')===0)?'selected':'' ?>>Académica</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Institución</label>
            <input class="form-input" type="text" name="institucion_text" value="<?= h($act['institucion'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Responsable <span class="required">*</span></label>
            <input class="form-input" type="text" name="responsable" required value="<?= h($act['responsable'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Carrera/Ciclo</label>
            <input class="form-input" type="text" name="carrera_ciclo" value="<?= h($act['carrera_ciclo'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
            <label class="form-checkbox">
              <input type="checkbox" name="es_proyecto" value="1" <?= (isset($act['tipo']) && strtolower($act['tipo'])==='proyecto')?'checked':'' ?>>
              <span>¿Es un proyecto?</span>
            </label>
        </div>
      </div>
    </div>

    <!-- Módulo 1.5: Información Contraparte -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">business</span>Información Contraparte</h2>
      <div class="form-grid">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Razón Social</label>
                <input class="form-input" type="text" name="razon_social" value="<?= h($act['razon_social'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tipo Contraparte</label>
                <select class="form-select" name="tipo_contraparte">
                    <option value="">Seleccione...</option>
                    <?php $tc = $act['tipo_contraparte'] ?? ''; ?>
                    <option value="Empresa" <?= $tc=='Empresa'?'selected':'' ?>>Empresa</option>
                    <option value="Fundación" <?= $tc=='Fundación'?'selected':'' ?>>Fundación</option>
                    <option value="Gobierno" <?= $tc=='Gobierno'?'selected':'' ?>>Gobierno</option>
                    <option value="ONG" <?= $tc=='ONG'?'selected':'' ?>>ONG</option>
                    <option value="Otro" <?= $tc=='Otro'?'selected':'' ?>>Otro</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">RUT</label>
                <input class="form-input" type="text" name="rut_contraparte" value="<?= h($act['rut_contraparte'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Representante Contraparte</label>
                <input class="form-input" type="text" name="representante_contraparte" value="<?= h($act['representante_contraparte'] ?? '') ?>">
            </div>
        </div>
      </div>
    </div>

    <!-- Módulo 2: Detalles y Tiempos -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">event</span>Detalles y Tiempos</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fecha de Inicio <span class="required">*</span></label>
            <input class="form-input" type="date" name="fecha_inicio" required value="<?= h($act['fecha_inicio'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Fecha de Término</label>
            <input class="form-input" type="date" name="fecha_fin" value="<?= h($act['fecha_fin'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Duración (horas) <span class="required">*</span></label>
            <input class="form-input" type="number" name="duracion_horas" min="1" required value="<?= h($act['duracion_horas'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Objetivo General <span class="required">*</span></label>
          <input class="form-input" type="text" name="objetivo" required value="<?= h($act['objetivo'] ?? '') ?>">
        </div>
        <div class="form-group full-width">
          <label class="form-label">Descripción</label>
          <textarea class="form-textarea" name="descripcion" rows="4"><?= h($act['descripcion'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Módulo 3: Resultados y Participantes -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">analytics</span>Resultados y Participantes</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Resultados Esperados</label>
            <textarea class="form-textarea" name="resultados_esperados" rows="3"><?= h($act['resultados_esperados'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Resultados Alcanzados</label>
            <textarea class="form-textarea" name="resultados_alcanzados" rows="3"><?= h($act['resultados_alcanzados'] ?? '') ?></textarea>
          </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Beneficiario</label>
                <input class="form-input" type="text" name="beneficiario" value="<?= h($act['beneficiario'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Lugar</label>
                <input class="form-input" type="text" name="lugar" value="<?= h($act['lugar'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Aporte al Perfil de Egreso</label>
          <textarea class="form-textarea" name="aporte_perfil" rows="2"><?= h($act['aporte_perfil'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Módulo 4: Estado y Avance -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">assignment_turned_in</span>Estado y Avance</h2>
      <div class="form-grid">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Estado</label>
            <?php $es=strtolower($act['estado'] ?? ''); ?>
            <select class="form-select" name="estado">
              <option value="planificada" <?= (strpos($es,'plan')===0)?'selected':'' ?>>Planificada</option>
              <option value="en_curso" <?= ((strpos($es,'ejec')!==false)||strpos($es,'curso')!==false)?'selected':'' ?>>En Curso</option>
              <option value="finalizada" <?= (strpos($es,'final')===0)?'selected':'' ?>>Finalizada</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Etapa Actual</label>
            <?php $etapas = ['Planificación', 'Ejecución', 'Seguimiento', 'Evaluación', 'Retroalimentación de Mejora']; ?>
            <select class="form-select" name="etapa">
                <?php foreach($etapas as $e): ?>
                    <option value="<?= $e ?>" <?= (($act['etapa']??'')==$e)?'selected':'' ?>><?= $e ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">% Avance</label>
            <div style="display:flex; align-items:center; gap:10px;">
                <input type="range" class="form-range" name="porcentaje_avance" min="0" max="100" value="<?= (int)($act['porcentaje_avance']??0) ?>" oninput="this.nextElementSibling.value = this.value + '%'" style="flex:1">
                <output style="font-weight:bold; width:40px; text-align:right;"><?= (int)($act['porcentaje_avance']??0) ?>%</output>
            </div>
          </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">N° de Resolución</label>
                <input class="form-input" type="text" name="n_resolucion" value="<?= h($act['n_resolucion'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <input class="form-input" type="text" name="observaciones" value="<?= h($act['observaciones'] ?? '') ?>">
            </div>
        </div>
      </div>
    </div>

    <!-- Evidencias -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">attach_file</span>Evidencias</h2>
      <?php if($ev_list): ?>
        <?php foreach($ev_list as $ev): ?>
        <div class="file-item" style="display:flex;justify-content:space-between;align-items:center;border:1px solid #e5e7eb;padding:8px;border-radius:6px;margin-bottom:8px">
          <div><span class="material-icons">insert_drive_file</span> <?= h(basename($ev['ruta'])) ?></div>
          <div>
            <a class="btn btn-secondary" href="<?= h($ev['ruta']) ?>" target="_blank"><span class="material-icons">visibility</span> Ver</a>
            <button class="btn btn-danger" type="submit" name="del_evidencia" value="<?= (int)$ev['id'] ?>" formnovalidate onclick="return confirm('¿Eliminar esta evidencia?');">
              <span class="material-icons">delete</span>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="file-upload-section" style="margin-top:1rem">
        <span class="material-icons file-upload-icon">cloud_upload</span>
        <p>Agregar nuevas evidencias</p>
        <label class="file-upload-label">
          Seleccionar Archivos
          <input type="file" name="evidencias[]" class="file-upload-input" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
        </label>
        <input type="hidden" name="tipo_evidencia[]" value="otro">
      </div>
    </div>

    <div class="form-actions">
      <a class="btn btn-secondary" href="ver.php?id=<?= (int)$id ?>"><span class="material-icons">visibility</span>Cancelar</a>
      <button class="btn btn-primary" type="submit"><span class="material-icons">save</span>Guardar cambios</button>
    </div>
  </form>
</div></div>
<?php require_once __DIR__ . '/footer.php'; ?>
</body></html>
