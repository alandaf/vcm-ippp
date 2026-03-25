<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
$res = $conn->query("DESCRIBE actividades");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'] . " (" . $row['Type'] . ")";
}
echo implode("\n", $cols);
?>
