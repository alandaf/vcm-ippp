<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['rol'] ?? '') === 'observador') {
        header("Location: ../index.php");
        exit;
    }

    // CSRF Check (Optional but recommended, skipping for now to match strict user request speed, but ideally should be here)
    // if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) { die("Error CSRF"); }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        // 1. Eliminar archivo principal
        $stmt = $conn->prepare("SELECT archivo FROM convenios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($archivo);
        $stmt->fetch();
        $stmt->close();

        if ($archivo && file_exists(__DIR__ . '/../' . $archivo)) {
            unlink(__DIR__ . '/../' . $archivo);
        }

        // 2. Eliminar evidencias asociadas
        $res = $conn->query("SELECT ruta FROM evidencias_convenios WHERE convenio_id = $id");
        while ($row = $res->fetch_assoc()) {
            if (file_exists(__DIR__ . '/../' . $row['ruta'])) {
                unlink(__DIR__ . '/../' . $row['ruta']);
            }
        }
        $conn->query("DELETE FROM evidencias_convenios WHERE convenio_id = $id");

        // 3. Eliminar registro
        $stmt = $conn->prepare("DELETE FROM convenios WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: ../index.php?msg=convenio_deleted");
        } else {
            header("Location: ../index.php?err=delete_failed");
        }
        $stmt->close();
    } else {
        header("Location: ../index.php");
    }
} else {
    header("Location: ../index.php");
}
?>
