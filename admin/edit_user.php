<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json; charset=utf-8');
require '/var/www/private/db.php';

$userId = $_GET['id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID not specified']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, username, email, is_admin FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    } else {
        echo json_encode(['status' => 'success', 'data' => $user]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
