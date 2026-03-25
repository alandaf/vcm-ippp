<?php
// diagnostico_cli.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ippilotopardo_vcm";

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error connecting with root: " . $conn->connect_error);
}

echo "<h1>Diagnóstico de Base de Datos</h1>\n";

// 1. Verificar tabla usuarios
echo "## Estructura de tabla 'usuarios'\n";
$res = $conn->query("DESCRIBE usuarios");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']}\n";
    }
} else {
    echo "Error describing usuarios: " . $conn->error . "\n";
}

// 2. Verificar tabla actividades
echo "\n## Estructura de tabla 'actividades'\n";
$res = $conn->query("DESCRIBE actividades");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']}\n";
    }
} else {
    echo "Error describing actividades: " . $conn->error . "\n";
}

// 3. Usuarios registrados
echo "\n## Usuarios registrados\n";
$res = $conn->query("SELECT id, nombre, usuario, email, rol, password_hash FROM usuarios");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Usuario: {$row['usuario']} | Hash: {$row['password_hash']}\n";
    }
}
