<?php
require_once __DIR__ . '/auth.php';
require_login();

// ✅ Asegurar APP_BASE
if (!defined('APP_BASE')) define('APP_BASE', '/vcm/public');

// Configuración y DB
require_once __DIR__ . '/../config/db.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

$rol = $_SESSION['rol'] ?? 'observador';

// --- DATOS GLOBALES (Sin filtros) ---

// Total Actividades
$sql_total = "SELECT COUNT(*) AS c FROM actividades";
$r = $conn->query($sql_total);
$total_actividades = $r->fetch_assoc()['c'] ?? 0;

// Desglose por Tipo (Para Tarjetas)
$sql_tipo = "SELECT tipo_vinculacion, COUNT(*) c FROM actividades GROUP BY tipo_vinculacion";
$rt = $conn->query($sql_tipo);
$counts_by_type = [];
while($rt && $row=$rt->fetch_assoc()){ 
    $counts_by_type[strtoupper($row['tipo_vinculacion'])] = (int)$row['c'];
} 

// Total Convenios
$cnt_convenios = 0;
if ($r = $conn->query("SELECT COUNT(*) c FROM convenios")) { $cnt_convenios = (int)$r->fetch_assoc()['c']; }

// Mapeo de conteos a tarjetas (Búsqueda aproximada)
function get_count_like($arr, $term) {
    foreach ($arr as $k => $v) {
        if (strpos($k, strtoupper($term)) !== false) return $v;
    }
    return 0;
}

$cnt_extension = get_count_like($counts_by_type, 'EXTENSI');
$cnt_difusion  = get_count_like($counts_by_type, 'DIFUSI');
$cnt_academica = get_count_like($counts_by_type, 'ACAD');

// Lista de Actividades (Últimas 20)
$sql_list = "SELECT id, titulo, tipo_vinculacion, estado, anio, fecha_inicio, fecha_fin, duracion_horas, area_responsable, razon_social, etapa, porcentaje_avance 
             FROM actividades ORDER BY id DESC LIMIT 20";
$rl = $conn->query($sql_list);
$actividades = []; while($rl && $r=$rl->fetch_assoc()){ $actividades[]=$r; }

// Lista de Convenios (Últimos 10)
$convenios = [];
$sqlConv = "SELECT id, nombre, tipo, contraparte, responsable, fecha_inicio, fecha_fin FROM convenios ORDER BY id DESC LIMIT 10";
if ($r = $conn->query($sqlConv)) { while ($row = $r->fetch_assoc()) $convenios[] = $row; }

// Helper Badge Estado
function estado_badge($fecha_fin){
  if(!$fecha_fin || $fecha_fin == '0000-00-00') return '<span class="badge" style="background:#f1f5f9;color:#475569;">Sin fecha</span>';
  $hoy = new DateTime(); $fin = new DateTime($fecha_fin);
  $diff = $hoy->diff($fin)->days * ($hoy <= $fin ? 1 : -1);
  if ($diff < 0) return '<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:700;">Finalizado</span>';
  elseif ($diff <= 30) return '<span class="badge" style="background:#fef3c7;color:#b45309;font-weight:700;">Por renovar</span>';
  else return '<span class="badge" style="background:#ecfdf5;color:#047857;font-weight:700;">Vigente</span>';
}

// Helper Badge Etapa
function etapa_badge($etapa){
    $etapa = trim($etapa);
    if(!$etapa) return '<span class="badge" style="background:#f1f5f9;color:#64748b;">Sin etapa</span>';
    
    $colors = [
        'Planificación' => ['bg'=>'#eff6ff', 'c'=>'#1d4ed8'], // Blue
        'Ejecución' => ['bg'=>'#ecfdf5', 'c'=>'#047857'],     // Green
        'Seguimiento' => ['bg'=>'#fff7ed', 'c'=>'#c2410c'],   // Orange
        'Evaluación' => ['bg'=>'#fdf2f8', 'c'=>'#be185d'],    // Pink
        'Retroalimentación de Mejora' => ['bg'=>'#f5f3ff', 'c'=>'#6d28d9'] // Purple
    ];

    $style = $colors[$etapa] ?? ['bg'=>'#f1f5f9', 'c'=>'#475569'];
    return '<span class="badge" style="background:'.$style['bg'].';color:'.$style['c'].';font-weight:700;padding:4px 8px;border-radius:4px;">'.h($etapa).'</span>';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard VCM</title>
</head>
<body>
  <?php require_once __DIR__ . '/header.php'; ?>
  
  <div class="layout"><div class="layout-inner">

    <header class="page-header">
      <h1 class="page-title"><span class="material-icons">dashboard</span> Dashboard VCM</h1>
      <p class="page-subtitle">Gestión integral de actividades y convenios</p>
    </header>

    <!-- TARJETAS DE RESUMEN (Estáticas) -->
    <div class="summary-cards-grid">
      <div class="card-impact bg-blue">
        <h6 class="card-title">Total Actividades</h6>
        <h1 class="card-value"><?= $total_actividades ?></h1>
        <div class="card-icon"><span class="material-icons">summarize</span></div>
      </div>
      <div class="card-impact bg-dark-ipp">
        <h6 class="card-title">Extensión</h6>
        <h1 class="card-value"><?= $cnt_extension ?></h1>
        <div class="card-icon"><span class="material-icons">volunteer_activism</span></div>
      </div>
      <div class="card-impact bg-red">
        <h6 class="card-title">Académicas</h6>
        <h1 class="card-value"><?= $cnt_academica ?></h1>
        <div class="card-icon"><span class="material-icons">school</span></div>
      </div>
      <div class="card-impact bg-gold">
        <h6 class="card-title" style="color:#000">Difusión</h6>
        <h1 class="card-value" style="color:#000"><?= $cnt_difusion ?></h1>
        <div class="card-icon" style="color:#000"><span class="material-icons">campaign</span></div>
      </div>
      <div class="card-impact bg-blue">
        <h6 class="card-title">Convenios (Total)</h6>
        <h1 class="card-value"><?= $cnt_convenios ?></h1>
        <div class="card-icon"><span class="material-icons">handshake</span></div>
      </div>
    </div>

    <style>
      .summary-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
      .card-impact { position: relative; padding: 1.5rem; border-radius: 12px; color: white; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.2s; min-height: 120px; display: flex; flex-direction: column; justify-content: center; }
      .card-impact:hover { transform: translateY(-5px); }
      .card-title { font-size: 0.85rem; text-transform: uppercase; font-weight: 700; opacity: 0.9; margin: 0 0 0.5rem 0; }
      .card-value { font-size: 2.5rem; font-weight: 700; margin: 0; line-height: 1; }
      .card-icon { position: absolute; right: -10px; bottom: -10px; opacity: 0.15; transform: rotate(-15deg); }
      .card-icon .material-icons { font-size: 5rem; }
      .bg-blue { background: linear-gradient(135deg, #002e5f 0%, #00509e 100%); }
      .bg-dark-ipp { background: linear-gradient(135deg, #1a1a1a 0%, #444 100%); }
      .bg-red { background: linear-gradient(135deg, #d32f2f 0%, #ff6659 100%); }
      .bg-gold { background: linear-gradient(135deg, #f09819 0%, #edde5d 100%); color: #333; }
    </style>

    <!-- BARRA DE ACCIONES -->
    <div class="actions" style="margin: 2rem 0; display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between; background:white; padding:15px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
      <div style="display:flex; gap:10px;">
        <?php if($rol !== 'observador'): ?>
            <a href="form.php" class="btn btn-primary"><span class="material-icons">add</span> Nueva Actividad</a>
            <a href="convenios/registro_convenio.php" class="btn btn-primary"><span class="material-icons">handshake</span> Nuevo Convenio</a>
        <?php endif; ?>
      </div>

    </div>

    <!-- TABLA ACTIVIDADES -->
    <div class="table-wrapper">
      <h2 style="color:#26416e; margin-bottom:15px;">Listado de Actividades</h2>
      <table class="vcm-table">
        <thead>
          <tr>
            <th>ID</th><th>Título</th><th>Área Resp. / Contraparte</th>
            <th>Etapa</th><th>Fechas</th><th>Avance</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$actividades): ?>
          <tr><td colspan="7" style="text-align:center;color:#6b7280">No se encontraron actividades.</td></tr>
        <?php else: foreach($actividades as $it): ?>
          <tr>
            <td><?= (int)$it['id'] ?></td>
            <td>
                <div style="font-weight:600;"><?= h($it['titulo']) ?></div>
                <div style="font-size:0.85em;color:#666;"><?= h($it['tipo_vinculacion']) ?> | <?= h($it['anio']) ?></div>
            </td>
            <td>
                <div><?= h($it['area_responsable']) ?></div>
                <div style="font-size:0.85em;color:#666;"><?= h($it['razon_social']) ?></div>
            </td>
            <td><?= etapa_badge($it['etapa']) ?></td>
            <td style="font-size:0.9em;">
                <div><?= date('d-m-Y', strtotime($it['fecha_inicio'])) ?></div>
                <div style="color:#666;"><?= $it['fecha_fin'] ? date('d-m-Y', strtotime($it['fecha_fin'])) : '-' ?></div>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:5px;">
                    <div style="flex-grow:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;width:60px;">
                        <div style="width:<?= (int)$it['porcentaje_avance'] ?>%;background:#10b981;height:100%;"></div>
                    </div>
                    <span style="font-size:0.8em;"><?= (int)$it['porcentaje_avance'] ?>%</span>
                </div>
            </td>
            <td style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/ver.php?id=<?= (int)$it['id'] ?>" title="Ver"><span class="material-icons">visibility</span></a>
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/ver.php?id=<?= (int)$it['id'] ?>&auto_print=1" target="_blank" title="PDF"><span class="material-icons">picture_as_pdf</span></a>
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/descargar_zip.php?type=actividad&id=<?= (int)$it['id'] ?>" title="ZIP"><span class="material-icons">folder_zip</span></a>
              <?php if($rol !== 'observador'): ?>
                  <a class="btn-action btn-edit" href="<?= APP_BASE ?>/editar.php?id=<?= (int)$it['id'] ?>" title="Editar"><span class="material-icons">edit</span></a>
                  <form method="post" action="<?= APP_BASE ?>/eliminar.php" onsubmit="return confirm('¿Eliminar actividad?');" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <button type="submit" class="btn-action btn-del" title="Eliminar"><span class="material-icons">delete</span></button>
                  </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- TABLA CONVENIOS (Sección secundaria) -->
    <div class="table-wrapper" style="margin-top:40px;">
      <h2 style="color:#26416e; margin-bottom:15px;">Últimos Convenios</h2>
      <table class="vcm-table">
        <thead>
          <tr>
            <th>ID</th><th>Nombre</th><th>Tipo</th><th>Contraparte</th>
            <th>Vigencia</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$convenios): ?>
          <tr><td colspan="7" style="text-align:center;color:#6b7280">No hay convenios registrados.</td></tr>
        <?php else: foreach($convenios as $conv): ?>
          <tr>
            <td><?= (int)$conv['id'] ?></td>
            <td><?= h($conv['nombre']) ?></td>
            <td><?= h($conv['tipo']) ?></td>
            <td><?= h($conv['contraparte']) ?></td>
            <td><?= date('d-m-Y', strtotime($conv['fecha_inicio'])) ?> - <?= date('d-m-Y', strtotime($conv['fecha_fin'])) ?></td>
            <td><?= estado_badge($conv['fecha_fin']) ?></td>
            <td style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/convenios/ver_convenios.php?id=<?= (int)$conv['id'] ?>" title="Ver"><span class="material-icons">visibility</span></a>
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/convenios/ver_convenios.php?id=<?= (int)$conv['id'] ?>&auto_print=1" target="_blank" title="PDF"><span class="material-icons">picture_as_pdf</span></a>
              <a class="btn-action btn-view" href="<?= APP_BASE ?>/descargar_zip.php?type=convenio&id=<?= (int)$conv['id'] ?>" title="ZIP"><span class="material-icons">folder_zip</span></a>
              <?php if($rol !== 'observador'): ?>
                  <a class="btn-action btn-edit" href="<?= APP_BASE ?>/convenios/editar_convenio.php?id=<?= (int)$conv['id'] ?>" title="Editar"><span class="material-icons">edit</span></a>
                  <form method="post" action="<?= APP_BASE ?>/convenios/eliminar_convenio.php" onsubmit="return confirm('¿Eliminar convenio?');" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$conv['id'] ?>">
                    <!-- <input type="hidden" name="csrf" value="<?= h($csrf) ?>"> -->
                    <button type="submit" class="btn-action btn-del" title="Eliminar"><span class="material-icons">delete</span></button>
                  </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div></div>

</body>
<?php require_once __DIR__ . '/footer.php'; ?>
</html>
