<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $conn->prepare('SELECT user_id, username, email, password_hash FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No account found']);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Wrong password']);
    exit;
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];

echo json_encode([
    'success' => true,
    'username' => $user['username'],
    'email' => $user['email']
]);
?>