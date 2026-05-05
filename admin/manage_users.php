<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json; charset=utf-8');
require '/var/www/private/db.php';

try {
    $stmt = $pdo->prepare("SELECT user_id, username, email, created_at, is_admin FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nepavyko gauti vartotojų: ' . $e->getMessage()
    ]);
}
