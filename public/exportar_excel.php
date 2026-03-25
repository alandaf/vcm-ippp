<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="actividades.xls"');
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Título</th><th>Tipo Vinculación</th><th>Tipo</th><th>Institución</th><th>Año</th><th>Estado</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Duración (h)</th></tr>";

$cols=[]; $r=$conn->query("SHOW COLUMNS FROM actividades"); while($r&&$c=$r->fetch_assoc()){ $cols[$c['Field']]=true; }
$col_title = isset($cols['titulo'])?'titulo':(isset($cols['nombre'])?'nombre':'id');
$col_inst_name = isset($cols['institucion'])?'institucion':null;
$has_inst_id = isset($cols['institucion_id']);
$col_tv = isset($cols['tipo_vinculacion'])?'tipo_vinculacion':null;
$col_tipo = isset($cols['tipo'])?'tipo':null;
$col_anio = isset($cols['anio'])?'anio':null;
$col_estado = isset($cols['estado'])?'estado':null;

$sql = "SELECT * FROM actividades ORDER BY id DESC";
$res = $conn->query($sql);
while($res && $row=$res->fetch_assoc()){
  $inst='';
  if($col_inst_name && !empty($row[$col_inst_name])) $inst=$row[$col_inst_name];
  elseif($has_inst_id && !empty($row['institucion_id'])){
    $iid=(int)$row['institucion_id']; $nm='';
    $rs=$conn->query("SELECT nombre FROM instituciones WHERE id=".$iid); if($rs){ $nm_arr=$rs->fetch_assoc(); $nm=$nm_arr?$nm_arr['nombre']:''; }
    $inst=$nm;
  }
  echo "<tr>";
  echo "<td>".(int)$row['id']."</td>";
  echo "<td>".htmlspecialchars($row[$col_title] ?? '')."</td>";
  echo "<td>".htmlspecialchars($col_tv? $row[$col_tv]:'')."</td>";
  echo "<td>".htmlspecialchars($col_tipo? $row[$col_tipo]:'')."</td>";
  echo "<td>".htmlspecialchars($inst)."</td>";
  echo "<td>".htmlspecialchars($col_anio? $row[$col_anio]:'')."</td>";
  echo "<td>".htmlspecialchars($col_estado? $row[$col_estado]:'')."</td>";
  echo "<td>".htmlspecialchars($row['fecha_inicio'] ?? '')."</td>";
  echo "<td>".htmlspecialchars($row['fecha_fin'] ?? '')."</td>";
  echo "<td>".htmlspecialchars($row['duracion_horas'] ?? '')."</td>";
  echo "</tr>";
}
echo "</table>";
