<?php
require_once __DIR__ . '/../auth.php';
require_login();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 1. Procesar Filtros
$inst = trim($_GET['institucion'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$anio = trim($_GET['anio'] ?? '');

$where = [];
$params = []; $types = '';

// Detectar columnas (optimización: hardcoded para VCM, pero mantenemos lógica similar)
$cols=[]; $r=$conn->query("SHOW COLUMNS FROM actividades"); while($r&&$c=$r->fetch_assoc()){ $cols[$c['Field']]=true; }
$col_inst = isset($cols['institucion'])?'institucion':null;
$has_inst_id = isset($cols['institucion_id']);

if($inst !== ''){
  if($col_inst){
    $where[] = "$col_inst=?"; $params[]=$inst; $types.='s';
  } elseif($has_inst_id){
    $stI = $conn->prepare("SELECT id FROM instituciones WHERE nombre=? LIMIT 1");
    if($stI){ $stI->bind_param('s',$inst); $stI->execute(); $resI=$stI->get_result(); $rowI=$resI->fetch_assoc(); $stI->close();
      if($rowI){ $where[]="institucion_id=?"; $params[]=(int)$rowI['id']; $types.='i'; } else { $where[]="1=0"; }
    }
  }
}
if($tipo!==''){ $where[]="tipo_vinculacion=?"; $params[]=$tipo; $types.='s'; }
if($estado!==''){ $where[]="estado=?"; $params[]=$estado; $types.='s'; }
if($anio!==''){ $where[]="anio=?"; $params[]=(int)$anio; $types.='i'; }

$where_sql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

// 2. Consultas

// Total Actividades
$sql_total = "SELECT COUNT(*) AS c FROM actividades $where_sql";
$st = $conn->prepare($sql_total); if($params){ $st->bind_param($types, ...$params); } $st->execute(); 
$total_actividades = $st->get_result()->fetch_assoc()['c'] ?? 0; $st->close();

// Desglose por Tipo
$sql_tipo = "SELECT tipo_vinculacion, COUNT(*) c FROM actividades $where_sql GROUP BY tipo_vinculacion";
$st = $conn->prepare($sql_tipo); if($params){ $st->bind_param($types, ...$params); } $st->execute(); $rt = $st->get_result();
$by_tipo = []; 
$counts_by_type = [];
while($rt && $row=$rt->fetch_assoc()){ 
    $by_tipo[]=$row; 
    $counts_by_type[strtoupper($row['tipo_vinculacion'])] = (int)$row['c'];
} 
$st->close();

// Desglose por Estado
$sql_estado = "SELECT estado, COUNT(*) c FROM actividades $where_sql GROUP BY estado ORDER BY 2 DESC";
$st = $conn->prepare($sql_estado); if($params){ $st->bind_param($types, ...$params); } $st->execute(); $re = $st->get_result();
$by_estado = []; while($re && $row=$re->fetch_assoc()){ $by_estado[]=$row; } $st->close();

// Desglose por Año
$sql_anio = "SELECT anio, COUNT(*) c FROM actividades $where_sql GROUP BY anio ORDER BY anio ASC";
$st = $conn->prepare($sql_anio); if($params){ $st->bind_param($types, ...$params); } $st->execute(); $ra = $st->get_result();
$by_anio = []; while($ra && $row=$ra->fetch_assoc()){ $by_anio[]=$row; } $st->close();

// Lista de Actividades
$sql_list = "SELECT id, titulo, tipo_vinculacion, estado, anio, fecha_inicio, duracion_horas, area_responsable, razon_social, etapa, porcentaje_avance 
             FROM actividades $where_sql ORDER BY id DESC LIMIT 200";
$st = $conn->prepare($sql_list); if($params){ $st->bind_param($types, ...$params); } $st->execute(); $rl = $st->get_result();
$actividades = []; while($rl && $r=$rl->fetch_assoc()){ $actividades[]=$r; } $st->close();

// 3. Helpers para conteos específicos
function get_count_like($arr, $term) {
    foreach ($arr as $k => $v) {
        if (strpos($k, strtoupper($term)) !== false) return $v;
    }
    return 0;
}

$cnt_extension = get_count_like($counts_by_type, 'EXTENSI');
$cnt_difusion  = get_count_like($counts_by_type, 'DIFUSI');
$cnt_academica = get_count_like($counts_by_type, 'ACAD');

// 4. Generar HTML de la tabla
ob_start();
if(!$actividades): ?>
  <tr><td colspan="7" style="text-align:center;color:#6b7280">No se encontraron actividades con los filtros seleccionados.</td></tr>
<?php else: foreach($actividades as $it): ?>
  <tr>
    <td><?= (int)$it['id'] ?></td>
    <td>
        <div style="font-weight:600;"><?= h($it['titulo']) ?></div>
        <div style="font-size:0.85em;color:#666;"><?= h($it['tipo_vinculacion']) ?> | <?= h($it['anio']) ?></div>
    </td>
    <td><?= h($it['area_responsable']) ?></td>
    <td><?= h($it['razon_social']) ?></td>
    <td><span class="badge"><?= h($it['etapa']) ?></span></td>
    <td>
        <div style="display:flex;align-items:center;gap:5px;">
            <div style="flex-grow:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;width:60px;">
                <div style="width:<?= (int)$it['porcentaje_avance'] ?>%;background:#10b981;height:100%;"></div>
            </div>
            <span style="font-size:0.8em;"><?= (int)$it['porcentaje_avance'] ?>%</span>
        </div>
    </td>
    <td style="display:flex;gap:6px;justify-content:center;">
      <a class="btn-action btn-view" href="ver.php?id=<?= (int)$it['id'] ?>"><span class="material-icons">visibility</span></a>
      <?php if(($_SESSION['rol']??'') !== 'observador'): ?>
          <a class="btn-action btn-edit" href="editar.php?id=<?= (int)$it['id'] ?>"><span class="material-icons">edit</span></a>
          <form method="post" action="eliminar.php" onsubmit="return confirm('¿Eliminar actividad?');" style="display:inline;">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']??'') ?>">
            <button type="submit" class="btn-action btn-del"><span class="material-icons">delete</span></button>
          </form>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; endif;
$table_html = ob_get_clean();

// --- DATOS DE CONVENIOS ---

$where_conv = [];
$params_conv = []; $types_conv = '';

// Filtro de Año para Convenios (usamos fecha_inicio)
if($anio !== ''){
    $where_conv[] = "YEAR(fecha_inicio) = ?";
    $params_conv[] = (int)$anio;
    $types_conv .= 'i';
}

$where_sql_conv = $where_conv ? ("WHERE ".implode(" AND ", $where_conv)) : "";

// 1. Convenios por Tipo
$sql_c_tipo = "SELECT tipo, COUNT(*) c FROM convenios $where_sql_conv GROUP BY tipo";
$st = $conn->prepare($sql_c_tipo); if($params_conv){ $st->bind_param($types_conv, ...$params_conv); } $st->execute(); $rc = $st->get_result();
$conv_tipo = []; while($rc && $row=$rc->fetch_assoc()){ $conv_tipo[]=$row; } $st->close();

// 2. Convenios por Estado (Vigente vs Finalizado)
// Vigente: fecha_fin >= HOY o fecha_fin IS NULL (asumimos indefinido/vigente)
// Finalizado: fecha_fin < HOY
$sql_c_estado = "SELECT 
    CASE 
        WHEN fecha_fin IS NULL OR fecha_fin = '0000-00-00' THEN 'Indefinido'
        WHEN fecha_fin >= CURDATE() THEN 'Vigente'
        ELSE 'Finalizado'
    END as estado_calc,
    COUNT(*) c 
    FROM convenios $where_sql_conv 
    GROUP BY estado_calc";
$st = $conn->prepare($sql_c_estado); if($params_conv){ $st->bind_param($types_conv, ...$params_conv); } $st->execute(); $rc = $st->get_result();
$conv_estado = []; while($rc && $row=$rc->fetch_assoc()){ $conv_estado[]=$row; } $st->close();

// 3. Convenios por Año (Evolución)
$sql_c_anio = "SELECT YEAR(fecha_inicio) as anio, COUNT(*) c FROM convenios $where_sql_conv GROUP BY YEAR(fecha_inicio) ORDER BY anio ASC";
$st = $conn->prepare($sql_c_anio); if($params_conv){ $st->bind_param($types_conv, ...$params_conv); } $st->execute(); $rc = $st->get_result();
$conv_anio = []; while($rc && $row=$rc->fetch_assoc()){ $conv_anio[]=$row; } $st->close();


// 5. Respuesta JSON
echo json_encode([
    'cards' => [
        'total' => $total_actividades,
        'extension' => $cnt_extension,
        'academica' => $cnt_academica,
        'difusion' => $cnt_difusion
    ],
    'charts' => [
        'estado' => $by_estado,
        'anio' => $by_anio,
        'tipo' => $by_tipo
    ],
    'convenios_charts' => [
        'tipo' => $conv_tipo,
        'estado' => $conv_estado,
        'anio' => $conv_anio
    ],
    'table_html' => $table_html
]);
