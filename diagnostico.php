<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>Diagnóstico de Base de Datos</h1>";

// 1. Verificar tabla usuarios
echo "<h2>Estructura de tabla 'usuarios'</h2>";
$res = $conn->query("DESCRIBE usuarios");
if ($res) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// 2. Verificar tabla actividades
echo "<h2>Estructura de tabla 'actividades'</h2>";
$res = $conn->query("DESCRIBE actividades");
if ($res) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// 3. Resetear password de admin (opcional, vamos a ver los usuarios primero)
echo "<h2>Usuarios registrados</h2>";
$res = $conn->query("SELECT id, nombre, usuario, email, rol FROM usuarios");
if ($res) {
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Email</th><th>Rol</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['nombre']}</td><td>{$row['usuario']}</td><td>{$row['email']}</td><td>{$row['rol']}</td></tr>";
    }
    echo "</table>";
}

echo "<br><p>Script ejecutado. Por favor, revisa la salida en el navegador (si es posible) o dime si quieres que proceda con el cambio de password.</p>";
