<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$userId = $_SESSION['user_id'];

if (strlen($username) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid username and email are required']);
    exit;
}

$check = $conn->prepare('SELECT user_id FROM users WHERE (email = ? OR username = ?) AND user_id != ? LIMIT 1');
$check->bind_param('ssi', $email, $username, $userId);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email or username is already in use']);
    exit;
}

$stmt = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ?');
$stmt->bind_param('ssi', $username, $email, $userId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    exit;
}

$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
