<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Debug: Check if db.php exists and load it
if (!file_exists('/var/www/private/db.php')) {
    echo json_encode(['success' => false, 'message' => 'Database config not found']);
    exit;
}

require '/var/www/private/db.php';
session_start();

// Debug: Check if connection exists
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

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
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('sss', $username, $email, $passwordHash);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    exit;
}

$_SESSION['user_id'] = $stmt->insert_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

echo json_encode([
    'success' => true,
    'username' => $username,
    'email' => $email
]);
?>
