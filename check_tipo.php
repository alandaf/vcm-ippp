<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
$res = $conn->query("SHOW COLUMNS FROM actividades LIKE 'tipo'");
$row = $res->fetch_assoc();
echo "Column 'tipo' info:\n";
print_r($row);

$res2 = $conn->query("SHOW COLUMNS FROM actividades LIKE 'tipo_vinculacion'");
$row2 = $res2->fetch_assoc();
echo "\nColumn 'tipo_vinculacion' info:\n";
print_r($row2);
