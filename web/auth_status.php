<?php
header('Content-Type: application/json');
session_start();

echo json_encode([
    'loggedIn' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'email' => $_SESSION['email'] ?? null
]);
?>