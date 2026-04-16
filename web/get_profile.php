<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';
requireLogin();

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT user_id, username, email, created_at FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode($user);
