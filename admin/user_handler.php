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
$isAdmin  = $_POST['is_admin'] ?? 0;

if (!$userId) {
    die("User ID is required.");
}

try {
    if ($action === 'delete') {
        $conn->begin_transaction();
        
        // Delete user allergens first (references user_id)
        $stmt = mysqli_prepare($conn, "DELETE FROM user_allergens WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to delete user allergens: ' . mysqli_error($conn));
        }

        // Delete user inventory (references user_id)
        $stmt = mysqli_prepare($conn, "DELETE FROM user_inventory WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to delete user inventory: ' . mysqli_error($conn));
        }

        // Delete user account last (after all foreign key references are removed)
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to delete user: ' . mysqli_error($conn));
        }

        $conn->commit();
        header("Location: manage_users.php?msg=User+deleted");
        exit;
        
    } else {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, password_hash = ?, is_admin = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "sssii", $username, $email, $hashedPassword, $isAdmin, $userId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, is_admin = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "ssii", $username, $email, $isAdmin, $userId);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_users.php?msg=User+updated");
            exit;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    die("Database error: " . $e->getMessage());
}
