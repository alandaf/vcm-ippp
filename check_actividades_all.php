<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
$res = $conn->query("DESCRIBE actividades");
echo "Column | Type | Null | Default\n";
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Default']}\n";
}
