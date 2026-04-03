<?php
header('Content-Type: application/json');

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message']]);
    }
});

// Enable error reporting to JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Error [$errno]: $errstr in $errfile:$errline"]);
    exit;
});

// Check if db.php exists
$db_path = '/var/www/private/db.php';
if (!file_exists($db_path)) {
    echo json_encode(['success' => false, 'message' => "Database config not found at: $db_path"]);
    exit;
}

require $db_path;

session_start();

// Verify connection
if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection variable not set']);
    exit;
}

if (is_object($conn) && method_exists($conn, 'connect_error')) {
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $conn->connect_error]);
        exit;
    }
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$email || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Invalid input: username, email, and password (min 6 chars) required']);
    exit;
}

$check = $conn->prepare('SELECT user_id FROM users WHERE email = ? OR username = ?');
if (!$check) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$check->bind_param('ss', $email, $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email or username already exists']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Insert prepare error: ' . $conn->error]);
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
