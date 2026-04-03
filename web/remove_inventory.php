<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$ingredientId = isset($data['ingredient_id']) ? (int)$data['ingredient_id'] : 0;

if ($ingredientId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ingredient ID']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM user_inventory WHERE user_id = ? AND ingredient_id = ?');
$stmt->bind_param('ii', $userId, $ingredientId);
$stmt->execute();

echo json_encode(['success' => true]);
?>
