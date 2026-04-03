<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Not logged in'
        ]);
        exit;
    }
}
?>