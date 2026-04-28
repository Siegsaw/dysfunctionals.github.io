<?php
session_start();
header('Content-Type: application/json');

require '/var/www/private/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT recipe_id
    FROM saved_recipes
    WHERE user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$ids = [];

while ($row = $result->fetch_assoc()) {
    $ids[] = (int)$row['recipe_id'];
}

echo json_encode($ids);
