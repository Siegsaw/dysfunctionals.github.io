<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare('
 SELECT ui.inventory_id, ui.quantity, ui.unit, i.name_ing
 FROM user_inventory ui
 JOIN ingredients i ON ui.ingredient_id = i.ingredient_id
 WHERE ui.user_id = ?
');

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => $row['inventory_id'],
        'name' => $row['name_ing'],
        'amount' => $row['quantity'],
        'unit' => $row['unit']
    ];
}

echo json_encode($items);
?>
