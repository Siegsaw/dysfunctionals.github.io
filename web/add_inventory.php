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
$expirationDate = $data['expiration_date'] ?? null;

if ($expirationDate === '') {
    $expirationDate = null;
}

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

// Check if user already has this ingredient
$checkStmt = $conn->prepare('SELECT inventory_id, quantity FROM user_inventory WHERE user_id = ? AND ingredient_id = ?');
$checkStmt->bind_param('ii', $userId, $ingredientId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $row = $checkResult->fetch_assoc();
    $newQuantity = floatval($row['quantity']) + $amount;
    $inventoryId = $row['inventory_id'];

    $updateStmt = $conn->prepare('UPDATE user_inventory SET quantity = ?, expiration_date = ? WHERE inventory_id = ?');
    $updateStmt->bind_param('dsi', $newQuantity, $expirationDate, $inventoryId);

    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
        exit;
    }
} else {
    $insertStmt = $conn->prepare('INSERT INTO user_inventory (quantity, unit, user_id, ingredient_id, expiration_date) VALUES (?, ?, ?, ?, ?)');
    $insertStmt->bind_param('dsiis', $amount, $unit, $userId, $ingredientId, $expirationDate);

    if (!$insertStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to add ingredient']);
        exit;
    }
}

echo json_encode(['success' => true, 'message' => 'Ingredient added']);
?>
