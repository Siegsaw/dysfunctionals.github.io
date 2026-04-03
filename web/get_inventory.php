<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare('
    SELECT i.name_ing as name, ui.quantity as amount, ui.unit 
    FROM user_inventory ui
    JOIN ingredients i ON ui.ingredient_id = i.ingredient_id
    WHERE ui.user_id = ?
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$inventory = [];
while ($row = $result->fetch_assoc()) {
    $inventory[] = $row;
}

echo json_encode($inventory);
?>
