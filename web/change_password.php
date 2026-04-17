<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$newPassword = $data['new_password'] ?? '';
$repeatPassword = $data['repeat_password'] ?? '';
$userId = $_SESSION['user_id'];

if (strlen($newPassword) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}
if ($newPassword !== $repeatPassword) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
$stmt->bind_param('si', $passwordHash, $userId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
