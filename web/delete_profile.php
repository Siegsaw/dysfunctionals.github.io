<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';
requireLogin();

$userId = $_SESSION['user_id'];

$conn->begin_transaction();
try {
    $deleteInventory = $conn->prepare('DELETE FROM user_inventory WHERE user_id = ?');
    if ($deleteInventory) {
        $deleteInventory->bind_param('i', $userId);
        $deleteInventory->execute();
    }

    $deleteUser = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    $deleteUser->bind_param('i', $userId);
    $deleteUser->execute();

    $conn->commit();
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Your profile has been deleted']);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete profile']);
}
