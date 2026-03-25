<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_login();

$type = trim($_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

// DEBUG LOG
file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - Request: type=[$type], id=[$id]\n", FILE_APPEND);

if ($id <= 0 || !in_array($type, ['actividad', 'convenio'])) {
    file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - Invalid Request\n", FILE_APPEND);
    die("Solicitud inválida.");
}

// Crear directorio temporal único
$tempBase = sys_get_temp_dir();
$uniqueId = uniqid("vcm_{$type}_{$id}_");
$sourceDir = $tempBase . DIRECTORY_SEPARATOR . $uniqueId;
$zipName = "VCM_{$type}_{$id}_" . date('YmdHis') . ".zip";
$zipPath = $tempBase . DIRECTORY_SEPARATOR . $zipName;

// Headers para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!mkdir($sourceDir, 0777, true)) {
    die("Error al crear directorio temporal.");
}

// Estructura de carpetas
$evidenciasDir = $sourceDir . DIRECTORY_SEPARATOR . 'evidencias';
$docsDir = $sourceDir . DIRECTORY_SEPARATOR . 'documentos';
mkdir($evidenciasDir, 0777, true);
mkdir($docsDir, 0777, true);

// --- 1. Generar Reporte HTML ---
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte VCM</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* =========================
           GLOBAL STYLES (From header.php)
           ========================= */
        :root {
            --azul: #0b1d3a;
            --warn: #facc15;
            --bg: #f5f7fb;
            --card: #fff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fff; /* White background for PDF */
            font-family: 'Roboto', system-ui, -apple-system, sans-serif;
            color: #0f172a;
            padding: 40px;
        }

        /* Layout Containers */
        .layout-inner { width: 100%; max-width: 1000px; margin: 0 auto; }
        
        /* Page Headers */
        .page-header { display: flex; flex-direction: column; gap: .5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 2rem; }
        .page-title { margin: 0; font-size: 2rem; color: var(--azul); display: flex; align-items: center; gap: .6rem; font-weight: 800; }
        .page-subtitle { margin: 0; color: #64748b; }

        /* Logo */
        #pdf-logo { text-align: center; margin-bottom: 40px; }
        #pdf-logo img { max-width: 250px; }

        /* FORM STYLES */
        .form-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.1rem; font-weight: 600; color: var(--azul); margin: 0 0 1.5rem 0; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
        .section-title .material-icons { font-size: 1.2rem; color: #94a3b8; }

        .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-label { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .form-hint { font-size: 0.95rem; color: #0f172a; padding: 8px 12px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; min-height: 38px; display: flex; align-items: center; }
        
        /* Progress Bar */
        .progress-container { background: #e2e8f0; border-radius: 4px; height: 20px; width: 100%; position: relative; overflow: hidden; }
        .progress-bar { background: #10b981; height: 100%; }
        .progress-text { position: absolute; top: 0; left: 50%; transform: translateX(-50%); font-size: 12px; line-height: 20px; color: #000; font-weight: 600; }

        /* Helpers */
        .material-icons { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="layout-inner">
        <div id="pdf-logo">
            <?php
            $logoPath = __DIR__ . '/../img/logo_ipp_con_nombre.png';
            if (file_exists($logoPath)) {
                $imgType = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $base64 = 'data:image/' . $imgType . ';base64,' . base64_encode($data);
                echo '<img src="' . $base64 . '" alt="Logo IPP">';
            } else {
                echo '<h1>IPP</h1>';
            }
            ?>
        </div>

        <header class="page-header">
            <h1 class="page-title"><span class="material-icons">description</span> Reporte de <?= ucfirst($type) ?></h1>
            <p class="page-subtitle">Generado el <?= date('d-m-Y H:i') ?></p>
        </header>

    <?php
    if ($type === 'actividad') {
        file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - Entering ACTIVIDAD block\n", FILE_APPEND);
        $sql = "SELECT * FROM actividades WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if(!$stmt) file_put_contents(__DIR__ . '/../debug_zip.log', "Prepare failed: ".$conn->error."\n", FILE_APPEND);
        $stmt->bind_param("i", $id);
        file_put_contents(__DIR__ . '/../debug_zip.log', "Execute...\n", FILE_APPEND);
        $stmt->execute();
        $res = $stmt->get_result();
        file_put_contents(__DIR__ . '/../debug_zip.log', "Get result done. Rows: ".$res->num_rows."\n", FILE_APPEND);
        $act = $res->fetch_assoc();
        $stmt->close();
        file_put_contents(__DIR__ . '/../debug_zip.log', "Fetch done. Act found: ".($act?'YES':'NO')."\n", FILE_APPEND);

        if ($act) {
            // Módulo 1: Institucional
            echo '<div class="form-section">
                <h2 class="section-title"><span class="material-icons">account_balance</span> Información Institucional</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Nombre Actividad</label><div class="form-hint">'.htmlspecialchars($act['titulo']).'</div></div>
                        <div class="form-group"><label class="form-label">Tipo Vinculación</label><div class="form-hint">'.htmlspecialchars($act['tipo_vinculacion']).'</div></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Área Responsable</label><div class="form-hint">'.htmlspecialchars($act['area_responsable']).'</div></div>
                        <div class="form-group"><label class="form-label">Carrera/Ciclo</label><div class="form-hint">'.htmlspecialchars($act['carrera_ciclo']??'—').'</div></div>
                    </div>
                </div>
            </div>';

            // Módulo 2: Contraparte
            echo '<div class="form-section">
                <h2 class="section-title"><span class="material-icons">business</span> Información Contraparte</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Razón Social</label><div class="form-hint">'.htmlspecialchars($act['razon_social']).'</div></div>
                        <div class="form-group"><label class="form-label">Tipo Contraparte</label><div class="form-hint">'.htmlspecialchars($act['tipo_contraparte']??'—').'</div></div>
                    </div>
                </div>
            </div>';

            // Módulo 3: Detalles
            echo '<div class="form-section">
                <h2 class="section-title"><span class="material-icons">event</span> Detalles de la Actividad</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Fecha Inicio</label><div class="form-hint">'.date('d-m-Y', strtotime($act['fecha_inicio'])).'</div></div>
                        <div class="form-group"><label class="form-label">Fecha Término</label><div class="form-hint">'.($act['fecha_fin']?date('d-m-Y', strtotime($act['fecha_fin'])):'—').'</div></div>
                    </div>
                    <div class="form-group full-width"><label class="form-label">Objetivo General</label><div class="form-hint">'.nl2br(htmlspecialchars($act['objetivo'])).'</div></div>
                    <div class="form-group full-width"><label class="form-label">Descripción</label><div class="form-hint">'.nl2br(htmlspecialchars($act['descripcion'])).'</div></div>
                </div>
            </div>';

            // Módulo 4: Estado
            echo '<div class="form-section">
                <h2 class="section-title"><span class="material-icons">analytics</span> Estado y Avance</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Etapa Actual</label><div class="form-hint">'.htmlspecialchars($act['etapa']).'</div></div>
                        <div class="form-group">
                            <label class="form-label">% Avance</label>
                            <div class="form-hint">
                                <div class="progress-container">
                                    <div class="progress-bar" style="width:'.(int)$act['porcentaje_avance'].'%"></div>
                                    <span class="progress-text">'.(int)$act['porcentaje_avance'].'%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }

        // Evidencias logic (copying files)
        $sqlEv = "SELECT ruta FROM evidencias WHERE actividad_id = ?";
        $stmtEv = $conn->prepare($sqlEv);
        $stmtEv->bind_param("i", $id);
        $stmtEv->execute();
        $resEv = $stmtEv->get_result();
        while ($row = $resEv->fetch_assoc()) {
            $filePath = __DIR__ . '/' . $row['ruta'];
            if (file_exists($filePath)) copy($filePath, $evidenciasDir . DIRECTORY_SEPARATOR . basename($filePath));
        }
        $stmtEv->close();

    } elseif ($type === 'convenio') {
        file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - Entering CONVENIO block\n", FILE_APPEND);
        $sql = "SELECT * FROM convenios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $conv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($conv) {
            echo '<div class="form-section">
                <h2 class="section-title"><span class="material-icons">info</span> Información del Convenio</h2>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Nombre</label><div class="form-hint">'.htmlspecialchars($conv['nombre']).'</div></div>
                        <div class="form-group"><label class="form-label">Tipo</label><div class="form-hint">'.htmlspecialchars($conv['tipo']).'</div></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Contraparte</label><div class="form-hint">'.htmlspecialchars($conv['contraparte']).'</div></div>
                        <div class="form-group"><label class="form-label">Responsable</label><div class="form-hint">'.htmlspecialchars($conv['responsable']).'</div></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Inicio</label><div class="form-hint">'.date('d-m-Y', strtotime($conv['fecha_inicio'])).'</div></div>
                        <div class="form-group"><label class="form-label">Fin</label><div class="form-hint">'.($conv['fecha_fin']?date('d-m-Y', strtotime($conv['fecha_fin'])):'—').'</div></div>
                    </div>
                    <div class="form-group full-width"><label class="form-label">Observaciones</label><div class="form-hint">'.nl2br(htmlspecialchars($conv['observaciones'])).'</div></div>
                </div>
            </div>';
            
            // Archivo principal (FIXED PATH)
            if (!empty($conv['archivo'])) {
                $mainFile = __DIR__ . '/uploads/convenios/' . basename($conv['archivo']);
                if (file_exists($mainFile)) {
                    copy($mainFile, $docsDir . DIRECTORY_SEPARATOR . basename($mainFile));
                } else {
                    file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - MISSING MAIN FILE: $mainFile\n", FILE_APPEND);
                }
            }
        }

        // Evidencias adicionales (FIXED PATH)
        $sqlEv = "SELECT ruta FROM evidencias_convenios WHERE convenio_id = ?";
        $stmtEv = $conn->prepare($sqlEv);
        $stmtEv->bind_param("i", $id);
        $stmtEv->execute();
        $resEv = $stmtEv->get_result();
        while ($row = $resEv->fetch_assoc()) {
            $filePath = __DIR__ . '/uploads/convenios/' . basename($row['ruta']);
            if (!file_exists($filePath)) $filePath = __DIR__ . '/' . $row['ruta'];
            
            if (file_exists($filePath)) {
                copy($filePath, $evidenciasDir . DIRECTORY_SEPARATOR . basename($filePath));
            } else {
                file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - MISSING EVIDENCE: $filePath\n", FILE_APPEND);
            }
        }
        $stmtEv->close();
    } else {
        die("Error interno: Tipo desconocido.");
    }
    ?>
    </div>
</body>
</html>


<?php
// --- 2. Convertir a PDF (Intento Cross-Platform) ---
$htmlPath = $sourceDir . DIRECTORY_SEPARATOR . 'Reporte.html';
$pdfPath = $sourceDir . DIRECTORY_SEPARATOR . 'Reporte.pdf';
file_put_contents($htmlPath, $htmlContent); // Guardar HTML siempre

$chromePath = null;
$candidates = [
    // Windows
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    getenv('LOCALAPPDATA') . '\Google\Chrome\Application\chrome.exe',
    // Linux (Standard paths)
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/snap/bin/chromium',
    '/usr/bin/chrome'
];

foreach ($candidates as $c) {
    if (file_exists($c)) {
        $chromePath = $c;
        break;
    }
}

if ($chromePath) {
    // En Linux a veces se requiere --no-sandbox para root/www-data dependiendo de config
    $cmdPDF = '"' . $chromePath . '" --headless --disable-gpu --no-sandbox --print-to-pdf="' . $pdfPath . '" "' . $htmlPath . '" 2>&1';
    exec($cmdPDF, $outPDF, $retPDF);
    // Si se creó, borramos el HTML para limpiar (o lo dejamos si prefieres backup)
    if (file_exists($pdfPath) && filesize($pdfPath) > 0) {
        unlink($htmlPath); 
    }
} else {
    // Fallback: No se encontró Chrome, se entregará el HTML.
    file_put_contents(__DIR__ . '/../debug_zip.log', date('Y-m-d H:i:s') . " - No Chrome found. Providing HTML report.\n", FILE_APPEND);
}

// --- 3. Comprimir con ZipArchive (Nativo PHP) ---
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Error crítico: No se puede crear el archivo ZIP.");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    // Ruta absoluta del archivo
    $filePath = $file->getRealPath();
    // Ruta relativa dentro del ZIP
    $relativePath = substr($filePath, strlen($sourceDir) + 1);

    if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else {
        $zip->addFile($filePath, $relativePath);
    }
}
$zip->close();

// --- 4. Descargar y Limpiar ---
if (file_exists($zipPath)) {
    // Limpiar el buffer de salida por si hubo algún echo previo
    if (ob_get_length()) ob_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    
    // Eliminar archivo ZIP final
    unlink($zipPath);

    // Borrar directorio temporal $sourceDir
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $f) {
        if ($f->isDir()) {
            rmdir($f->getRealPath());
        } else {
            unlink($f->getRealPath());
        }
    }
    rmdir($sourceDir);
    exit;
} else {
    die("Error: El archivo ZIP no se generó correctamente.");
}
?>
