<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';
requireLogin();

$userId = $_SESSION['user_id'];

$conn->begin_transaction();
try {
    // Delete user inventory
    $deleteInventory = $conn->prepare('DELETE FROM user_inventory WHERE user_id = ?');
    if (!$deleteInventory) {
        throw new Exception('Failed to prepare inventory deletion: ' . $conn->error);
    }
    $deleteInventory->bind_param('i', $userId);
    if (!$deleteInventory->execute()) {
        throw new Exception('Failed to execute inventory deletion: ' . $deleteInventory->error);
    }

    // Delete user account
    $deleteUser = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    if (!$deleteUser) {
        throw new Exception('Failed to prepare user deletion: ' . $conn->error);
    }
    $deleteUser->bind_param('i', $userId);
    if (!$deleteUser->execute()) {
        throw new Exception('Failed to execute user deletion: ' . $deleteUser->error);
    }

    $conn->commit();
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Your profile has been deleted']);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    // Log the actual error for debugging
    error_log('Profile deletion error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete profile', 'debug' => $e->getMessage()]);
}
?>
