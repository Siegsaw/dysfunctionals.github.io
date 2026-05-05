<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$ingredientId = isset($_GET['ingredient_id']) ? (int)$_GET['ingredient_id'] : 0;

if ($ingredientId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT nutrient_id, amount_per_100g FROM ingredient_nutrition WHERE ingredient_id = ?");
$stmt->bind_param("i", $ingredientId);
$stmt->execute();
$result = $stmt->get_result();

$values = [];
while ($row = $result->fetch_assoc()) {
    $values[] = $row;
}

echo json_encode($values);
