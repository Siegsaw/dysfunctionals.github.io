<?php
require_once __DIR__ . '/auth.php';
require_admin(); 
require '/var/www/private/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$action   = $_POST['action'] ?? 'update'; 
$userId   = $_POST['user_id'] ?? null;
$username = $_POST['username'] ?? null;
$email    = $_POST['email'] ?? null;
$password = $_POST['password'] ?? '';

if (!$userId) {
    die("User ID is required.");
}

try {
    if ($action === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        
        header("Location: manage_users.php?msg=User+deleted");
        exit;
        
    } else {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, password_hash = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $hashedPassword, $userId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $userId);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_users.php?msg=User+updated");
            exit;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
