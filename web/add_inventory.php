<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$unit = trim($data['unit'] ?? '');
$expirationDate = trim($data['expiration_date'] ?? '');

if (!$name || $amount <= 0 || !$unit) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_inventory'])) {
        $_SESSION['guest_inventory'] = [];
    }

    $guestId = 'guest_' . time() . '_' . rand(1000, 9999);

    $_SESSION['guest_inventory'][] = [
        'ingredient_id' => $guestId,
        'name' => $name,
        'amount' => $amount,
        'unit' => $unit,
        'expiration_date' => $expirationDate
    ];

    echo json_encode([
        'success' => true,
        'guest' => true
    ]);
    exit;
}
$userId = $_SESSION['user_id'];

// Find or create ingredient
$ingredientStmt = $conn->prepare('SELECT ingredient_id FROM ingredients WHERE name_ing = ?');
$ingredientStmt->bind_param('s', $name);
$ingredientStmt->execute();
$ingredientResult = $ingredientStmt->get_result();

if ($ingredientResult->num_rows === 0) {
    $insertIng = $conn->prepare('INSERT INTO ingredients (name_ing) VALUES (?)');
    $insertIng->bind_param('s', $name);

    if (!$insertIng->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create ingredient: ' . $conn->error]);
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

    $updateStmt = $conn->prepare("
        UPDATE user_inventory
        SET quantity = ?, expiration_date = NULLIF(?, '')
        WHERE inventory_id = ?
    ");
    $updateStmt->bind_param('dsi', $newQuantity, $expirationDate, $inventoryId);

    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update quantity: ' . $conn->error]);
        exit;
    }
} else {
    $insertStmt = $conn->prepare("
        INSERT INTO user_inventory (quantity, unit, user_id, ingredient_id, expiration_date)
        VALUES (?, ?, ?, ?, NULLIF(?, ''))
    ");
    $insertStmt->bind_param('dsiis', $amount, $unit, $userId, $ingredientId, $expirationDate);

    if (!$insertStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to add ingredient: ' . $conn->error]);
        exit;
    }
}

echo json_encode(['success' => true, 'message' => 'Ingredient added']);
?>
