<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
$res = $conn->query("SELECT id, nombre, email, password_hash FROM usuarios");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Email: {$row['email']} | Hash: {$row['password_hash']}\n";
}
