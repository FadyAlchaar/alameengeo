<?php
// ⚠️ DELETE THIS FILE AFTER GENERATING YOUR HASH ⚠️
header('Content-Type: text/plain; charset=utf-8');

$password = $_GET['p'] ?? '';
if (!$password) {
    echo "Usage: _hash-tool.php?p=YourNewPassword\n";
    echo "Then copy the resulting hash into app/Auth.php\n";
    exit;
}

echo password_hash($password, PASSWORD_BCRYPT) . "\n";