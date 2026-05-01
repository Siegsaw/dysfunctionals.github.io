<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json; charset=utf-8');

require '/var/www/private/db.php';

$ingredientId = $_GET['ingredient_id'] ?? '';

if ($ingredientId === '' || !is_numeric($ingredientId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ingredient.'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT nutrient_id, amount_per_100g
    FROM ingredient_nutrition
    WHERE ingredient_id = ?
");

$stmt->bind_param("i", $ingredientId);
$stmt->execute();

$result = $stmt->get_result();

$nutrition = [];

while ($row = $result->fetch_assoc()) {
    $nutrition[$row['nutrient_id']] = $row['amount_per_100g'];
}

echo json_encode([
    'success' => true,
    'nutrition' => $nutrition
]);
?>
