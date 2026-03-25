<?php
// fix_and_diag.php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

echo "--- USUARIOS (ID | Nombre | Usuario | Email | Rol) ---\n";
$res = $conn->query("SELECT id, nombre, usuario, email, rol, password_hash FROM usuarios");
while($row = $res->fetch_assoc()) {
    echo "{$row['id']} | {$row['nombre']} | {$row['usuario']} | {$row['email']} | {$row['rol']} | {$row['password_hash']}\n";
}

echo "\n--- ACTIVIDADES (Field | Type) ---\n";
$res = $conn->query("DESCRIBE actividades");
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} | {$row['Type']}\n";
}
