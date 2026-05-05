<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json; charset=utf-8');
require '/var/www/private/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid query method']);
    exit;
}

$action = $_POST['action'] ?? 'update'; 
$userId = $_POST['user_id'] ?? null;
$username = $_POST['username'] ?? null;
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? '';

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

try {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['status' => 'success', 'message' => 'User removed']);
        
    } else {
        // Atnaujinimo logika
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $hashedPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $userId]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Data successfully updated']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}
