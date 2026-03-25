<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_actividades.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Título','Tipo Vinculación','Tipo','Estado','Año','Inicio','Fin','Duración (h)','Institución']);

// Detect columns
$cols=[]; $r=$conn->query("SHOW COLUMNS FROM actividades"); while($r&&$c=$r->fetch_assoc()){ $cols[$c['Field']]=true; }
$col_title = isset($cols['titulo'])?'titulo':(isset($cols['nombre'])?'nombre':'id');
$has_inst_id = isset($cols['institucion_id']);
$col_inst = isset($cols['institucion'])?'institucion':null;

// Filters
$inst = trim($_GET['institucion'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$anio = trim($_GET['anio'] ?? '');

$where = [];
$params=[]; $types='';

if($inst !== ''){
  if($col_inst){ $where[]="$col_inst=?"; $params[]=$inst; $types.='s'; }
  elseif($has_inst_id){
    $stI=$conn->prepare("SELECT id FROM instituciones WHERE nombre=? LIMIT 1");
    if($stI){ $stI->bind_param('s',$inst); $stI->execute(); $resI=$stI->get_result(); $rowI=$resI->fetch_assoc(); $stI->close();
      if($rowI){ $where[]="institucion_id=?"; $params[]=(int)$rowI['id']; $types.='i'; } else { $where[]="1=0"; }
    }
  }
}
if($tipo!==''){ $where[]="tipo_vinculacion=?"; $params[]=$tipo; $types.='s'; }
if($estado!==''){ $where[]="estado=?"; $params[]=$estado; $types.='s'; }
if($anio!==''){ $where[]="anio=?"; $params[]=(int)$anio; $types.='i'; }

$where_sql = $where ? ("WHERE ".implode(" AND ", $where)) : "";
$sql = "SELECT * FROM actividades $where_sql ORDER BY id DESC";

$st=$conn->prepare($sql);
if($params){ $st->bind_param($types, ...$params); }
$st->execute(); $res=$st->get_result();
while($res && $row=$res->fetch_assoc()){
  $inst_name = '';
  if($col_inst && !empty($row[$col_inst])) $inst_name = $row[$col_inst];
  elseif($has_inst_id && !empty($row['institucion_id'])){
    $iid=(int)$row['institucion_id']; $nm='';
    $rs=$conn->query("SELECT nombre FROM instituciones WHERE id=".$iid); if($rs){ $nm_arr=$rs->fetch_assoc(); $nm=$nm_arr?$nm_arr['nombre']:''; }
    $inst_name=$nm;
  }
  fputcsv($out, [
    $row['id'],
    $row[$col_title] ?? '',
    $row['tipo_vinculacion'] ?? '',
    $row['tipo'] ?? '',
    $row['estado'] ?? '',
    $row['anio'] ?? '',
    $row['fecha_inicio'] ?? '',
    $row['fecha_fin'] ?? '',
    $row['duracion_horas'] ?? '',
    $inst_name
  ]);
}
fclose($out);
