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

// --- LÓGICA DE FILTROS (Traída de reportes.php) ---

// 1. Obtener listas para los selectores
$distinct_anios = []; $distinct_tipos = []; $distinct_estados = []; $distinct_inst = [];

// Detectar columnas disponibles
$cols=[]; $r=$conn->query("SHOW COLUMNS FROM actividades"); while($r&&$c=$r->fetch_assoc()){ $cols[$c['Field']]=true; }
$col_inst = isset($cols['institucion'])?'institucion':null;
$has_inst_id = isset($cols['institucion_id']);

// Años
$res = $conn->query("SELECT DISTINCT anio FROM actividades WHERE anio IS NOT NULL ORDER BY anio DESC");
while($res && $row=$res->fetch_assoc()){ $distinct_anios[] = (int)$row['anio']; }

// Tipos
$res = $conn->query("SELECT DISTINCT tipo_vinculacion FROM actividades WHERE tipo_vinculacion IS NOT NULL ORDER BY 1");
while($res && $row=$res->fetch_assoc()){ $distinct_tipos[] = $row['tipo_vinculacion']; }

// Estados
$res = $conn->query("SELECT DISTINCT estado FROM actividades WHERE estado IS NOT NULL ORDER BY 1");
while($res && $row=$res->fetch_assoc()){ $distinct_estados[] = $row['estado']; }

// Instituciones
if($col_inst){
  $res = $conn->query("SELECT DISTINCT $col_inst AS inst FROM actividades WHERE $col_inst IS NOT NULL AND $col_inst<>'' ORDER BY 1");
  while($res && $row=$res->fetch_assoc()){ $distinct_inst[] = $row['inst']; }
} elseif($has_inst_id){
  $res = $conn->query("SELECT DISTINCT i.nombre AS inst FROM actividades a JOIN instituciones i ON i.id=a.institucion_id ORDER BY 1");
  while($res && $row=$res->fetch_assoc()){ $distinct_inst[] = $row['inst']; }
}

// 2. Procesar Filtros
$inst = trim($_GET['institucion'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$anio = trim($_GET['anio'] ?? '');

$where = [];
$params = []; $types = '';

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

// 3. Consultas de Datos (Afectadas por filtros)

// Total Actividades (Filtradas)
$sql_total = "SELECT COUNT(*) AS c FROM actividades $where_sql";
$st = $conn->prepare($sql_total); if($params){ $st->bind_param($types, ...$params); } $st->execute(); 
$total_actividades = $st->get_result()->fetch_assoc()['c'] ?? 0; $st->close();

// Desglose por Tipo (Para Tarjetas y Gráfico)
$sql_tipo = "SELECT tipo_vinculacion, COUNT(*) c FROM actividades $where_sql GROUP BY tipo_vinculacion";
$st = $conn->prepare($sql_tipo); if($params){ $st->bind_param($types, ...$params); } $st->execute(); $rt = $st->get_result();
$by_tipo = []; 
$counts_by_type = []; // Mapa para acceso rápido
while($rt && $row=$rt->fetch_assoc()){ 
    $by_tipo[]=$row; 
    $counts_by_type[strtoupper($row['tipo_vinculacion'])] = (int)$row['c'];
} 
$st->close();

// --- DATOS GLOBALES (No afectados por filtros de actividades, o estáticos) ---

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

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estadísticas VCM</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php require_once __DIR__ . '/header.php'; ?>
  
  <div class="layout"><div class="layout-inner">

    <header class="page-header">
      <h1 class="page-title"><span class="material-icons">insights</span> Estadísticas VCM</h1>
      <p class="page-subtitle">Análisis detallado de actividades y convenios</p>
    </header>

    <!-- TARJETAS DE RESUMEN ELIMINADAS POR SOLICITUD DEL USUARIO -->

    <!-- SECCIÓN DE FILTROS -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">filter_list</span>Filtros de Actividades</h2>
      <form class="form-grid" method="get">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Institución</label>
            <select name="institucion" class="form-select">
              <option value="">Todas</option>
              <?php foreach($distinct_inst as $i): ?>
                <option value="<?= h($i) ?>" <?= ($inst===$i?'selected':'') ?>><?= h($i) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tipo de Vinculación</label>
            <select name="tipo" class="form-select">
              <option value="">Todos</option>
              <?php foreach($distinct_tipos as $t): ?>
                <option value="<?= h($t) ?>" <?= ($tipo===$t?'selected':'') ?>><?= h($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
              <option value="">Todos</option>
              <?php foreach($distinct_estados as $e): ?>
                <option value="<?= h($e) ?>" <?= ($estado===$e?'selected':'') ?>><?= h($e) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Año</label>
            <select name="anio" class="form-select">
              <option value="">Todos</option>
              <?php foreach($distinct_anios as $a): ?>
                <option value="<?= (int)$a ?>" <?= ($anio!=='' && (int)$anio===(int)$a ? 'selected':'' ) ?>><?= (int)$a ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-actions" style="justify-content: flex-start;">
          <!-- Botón submit oculto o eliminado, ya que es AJAX -->
          <button class="btn btn-secondary" id="btn-clean"><span class="material-icons">refresh</span> Limpiar Filtros</button>
        </div>
      </form>
    </div>

    <!-- GRÁFICOS (2 Columnas) -->
    <div class="form-section">
      <h2 class="section-title"><span class="material-icons">insights</span>Estadísticas Generales</h2>
      
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
        
        <!-- Columna Actividades -->
        <div>
          <h3 style="color:#00509e; margin-bottom:1rem; border-bottom:2px solid #e5e7eb; padding-bottom:0.5rem;">Actividades</h3>
          <div style="display:flex; flex-direction:column; gap:2rem;">
            <div style="height:200px;"><canvas id="chartTipo"></canvas></div>
            <div style="height:200px;"><canvas id="chartEstado"></canvas></div>
            <div style="height:200px;"><canvas id="chartAnio"></canvas></div>
          </div>
        </div>

        <!-- Columna Convenios -->
        <div>
          <h3 style="color:#00509e; margin-bottom:1rem; border-bottom:2px solid #e5e7eb; padding-bottom:0.5rem;">Convenios</h3>
          <div style="display:flex; flex-direction:column; gap:2rem;">
            <div style="height:200px;"><canvas id="chartConvTipo"></canvas></div>
            <div style="height:200px;"><canvas id="chartConvEstado"></canvas></div>
            <div style="height:200px;"><canvas id="chartConvAnio"></canvas></div>
          </div>
        </div>

      </div>
    </div>

    <!-- Tabla oculta para que el JS no falle si intenta actualizarla -->
    <div style="display:none;">
        <table class="vcm-table"><tbody></tbody></table>
    </div>

  </div></div>

  <script src="assets/js/dashboard.js?v=<?= time() ?>"></script>

</body>
<?php require_once __DIR__ . '/footer.php'; ?>
</html>
