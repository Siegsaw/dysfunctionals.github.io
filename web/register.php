<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$email || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$check = $conn->prepare('SELECT user_id FROM users WHERE email = ? OR username = ?');
$check->bind_param('ss', $email, $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email or username already exists']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $username, $email, $passwordHash);
$stmt->execute();

$_SESSION['user_id'] = $stmt->insert_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

echo json_encode([
    'success' => true,
    'username' => $username,
    'email' => $email
]);
?>
