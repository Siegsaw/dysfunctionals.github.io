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
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

/*
  ── GUEST MODE ─────────────────────────────
  Uses $_SESSION['guest_inventory'] instead of MariaDB.
  Merges same ingredient + same unit.
*/
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_inventory'])) {
        $_SESSION['guest_inventory'] = [];
    }

    $found = false;

    foreach ($_SESSION['guest_inventory'] as &$item) {
        $sameName = strtolower($item['name']) === strtolower($name);
        $sameUnit = $item['unit'] === $unit;

        if ($sameName && $sameUnit) {
            $item['amount'] = floatval($item['amount']) + $amount;

            if ($expirationDate !== '') {
                $item['expiration_date'] = $expirationDate;
            }

            $found = true;
            break;
        }
    }

    unset($item);

    if (!$found) {
        $guestId = 'guest_' . time() . '_' . rand(1000, 9999);

        $_SESSION['guest_inventory'][] = [
            'ingredient_id' => $guestId,
            'name' => $name,
            'amount' => $amount,
            'unit' => $unit,
            'expiration_date' => $expirationDate !== '' ? $expirationDate : null
        ];
    }

    echo json_encode([
        'success' => true,
        'guest' => true,
        'message' => 'Ingredient added to guest inventory'
    ]);
    exit;
}

/*
  ── LOGGED-IN MODE ─────────────────────────
  Uses MariaDB.
*/
$userId = $_SESSION['user_id'];

// Find ingredient
$ingredientStmt = $conn->prepare('
    SELECT ingredient_id 
    FROM ingredients 
    WHERE name_ing = ?
');
$ingredientStmt->bind_param('s', $name);
$ingredientStmt->execute();
$ingredientResult = $ingredientStmt->get_result();

if ($ingredientResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Ingredient does not exist in database'
    ]);
    exit;
}

$ingredient = $ingredientResult->fetch_assoc();
$ingredientId = (int)$ingredient['ingredient_id'];

// Check if user already has this ingredient
$checkStmt = $conn->prepare('
    SELECT inventory_id, quantity 
    FROM user_inventory 
    WHERE user_id = ? AND ingredient_id = ?
');
$checkStmt->bind_param('ii', $userId, $ingredientId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $row = $checkResult->fetch_assoc();

    $newQuantity = floatval($row['quantity']) + $amount;
    $inventoryId = (int)$row['inventory_id'];

    $updateStmt = $conn->prepare("
        UPDATE user_inventory
        SET quantity = ?, unit = ?, expiration_date = NULLIF(?, '')
        WHERE inventory_id = ?
    ");
    $updateStmt->bind_param('dssi', $newQuantity, $unit, $expirationDate, $inventoryId);

    if (!$updateStmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update quantity: ' . $conn->error
        ]);
        exit;
    }
} else {
    $insertStmt = $conn->prepare("
        INSERT INTO user_inventory 
            (quantity, unit, user_id, ingredient_id, expiration_date)
        VALUES 
            (?, ?, ?, ?, NULLIF(?, ''))
    ");
    $insertStmt->bind_param('dsiis', $amount, $unit, $userId, $ingredientId, $expirationDate);

    if (!$insertStmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add ingredient: ' . $conn->error
        ]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'guest' => false,
    'message' => 'Ingredient added'
]);
?>
