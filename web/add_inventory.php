<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$unit = trim($data['unit'] ?? '');

if (!$name || $amount <= 0 || !$unit) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Find or create ingredient
$ingredientStmt = $conn->prepare('SELECT ingredient_id FROM ingredients WHERE name_ing = ?');
$ingredientStmt->bind_param('s', $name);
$ingredientStmt->execute();
$ingredientResult = $ingredientStmt->get_result();

if ($ingredientResult->num_rows === 0) {
    // Ingredient doesn't exist - create it
    $insertIng = $conn->prepare('INSERT INTO ingredients (name_ing) VALUES (?)');
    $insertIng->bind_param('s', $name);
    if (!$insertIng->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create ingredient']);
        exit;
    }
    $ingredientId = $insertIng->insert_id;
} else {
    $ingredient = $ingredientResult->fetch_assoc();
    $ingredientId = $ingredient['ingredient_id'];
}

// Add or increment quantity using INSERT ... ON DUPLICATE KEY UPDATE
$insertStmt = $conn->prepare('
    INSERT INTO user_inventory (user_id, ingredient_id, quantity, unit) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    quantity = quantity + VALUES(quantity)
');
$insertStmt->bind_param('iids', $userId, $ingredientId, $amount, $unit);

if (!$insertStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to add ingredient']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Ingredient added']);
?>
