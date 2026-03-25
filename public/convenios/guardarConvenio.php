<?php
// archivo: public/convenios/guardarConvenio.php
require_once('../../config/db.php');

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $contraparte = $_POST['contraparte'];
    $responsable = $_POST['responsable'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $observaciones = $_POST['observaciones'];
    $avance = (int)($_POST['porcentaje_avance'] ?? 0);

    $archivoRuta = null;

    // Manejo del archivo PDF
    if (!empty($_FILES['archivo']['name'])) {
        $directorio = "../uploads/convenios/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $archivoTmp = $_FILES['archivo']['tmp_name'];
        $nombreArchivo = time() . "_" . basename($_FILES['archivo']['name']);
        $destino = $directorio . $nombreArchivo;

        $tipoArchivo = mime_content_type($archivoTmp);
        $tamano = $_FILES['archivo']['size'];

        if ($tipoArchivo === 'application/pdf' && $tamano <= 10 * 1024 * 1024) {
            if (move_uploaded_file($archivoTmp, $destino)) {
                $archivoRuta = "uploads/convenios/" . $nombreArchivo;
            }
        } else {
            die("Error: Solo se permiten archivos PDF menores a 10 MB.");
        }
    }

    // Insertar en la BD
    $sql = "INSERT INTO convenios (nombre, tipo, contraparte, responsable, fecha_inicio, fecha_fin, observaciones, archivo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nombre, $tipo, $contraparte, $responsable, $fecha_inicio, $fecha_fin, $observaciones, $archivoRuta);

    if ($stmt->execute()) {
        header("Location: ver_convenios.php?msg=ok");
        exit;
    } else {
        echo "Error al guardar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
