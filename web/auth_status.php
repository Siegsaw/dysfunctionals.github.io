<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'guest' => false,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ]);
    exit;
}

echo json_encode([
    'loggedIn' => false,
    'guest' => true,
    'username' => 'Guest'
]);
