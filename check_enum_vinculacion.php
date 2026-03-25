<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
$res = $conn->query("SHOW COLUMNS FROM actividades LIKE 'tipo_vinculacion'");
$row = $res->fetch_assoc();
echo "Column 'tipo_vinculacion' values:\n";
echo "Type: " . $row['Type'] . "\n";
?>
