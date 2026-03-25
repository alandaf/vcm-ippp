<?php
$conn = new mysqli("localhost", "root", "", "ippilotopardo_vcm");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

$email = 'andres.landa@ippilotopardo.cl';
$new_pass = 'AdminVcm2025!';
$new_hash = password_hash($new_pass, PASSWORD_BCRYPT);

$st = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
$st->bind_param("ss", $new_hash, $email);
if ($st->execute()) {
    echo "SUCCESS: Password for $email has been reset to: $new_pass\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
