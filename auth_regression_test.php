<?php
require_once __DIR__ . '/includes/auth.php';
session_start();
$_SESSION = [];

if (sessionIsValid() !== false) {
    fwrite(STDERR, "FAIL: sessionIsValid should reject a missing authenticated user\n");
    exit(1);
}

echo "PASS: sessionIsValid rejects missing authenticated user\n";
