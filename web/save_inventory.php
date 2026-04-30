<?php
header('Content-Type: application/json');

require '/var/www/private/db.php';
require 'session.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid inventory data'
    ]);
    exit;
}

/*
  ── GUEST MODE ─────────────────────────────
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['guest_inventory'] = [];

    foreach ($data as $item) {
        $name = trim($item['name'] ?? '');
        $amount = floatval($item['amount'] ?? 0);
        $unit = trim($item['unit'] ?? '');
        $expirationDate = $item['expiration_date'] ?? null;

        if (!$name || $amount <= 0 || !$unit) {
            continue;
        }

        $_SESSION['guest_inventory'][] = [
            'ingredient_id' => $item['ingredient_id'] ?? ('guest_' . time() . '_' . rand(1000, 9999)),
            'name' => $name,
            'amount' => $amount,
            'unit' => $unit,
            'expiration_date' => $expirationDate
        ];
    }

    echo json_encode([
        'success' => true,
        'guest' => true
    ]);
    exit;
}

/*
  ── LOGGED-IN MODE ─────────────────────────
*/
$userId = (int)$_SESSION['user_id'];

$deleteStmt = $conn->prepare("
    DELETE FROM user_inventory 
    WHERE user_id = ?
");
$deleteStmt->bind_param('i', $userId);
$deleteStmt->execute();

foreach ($data as $item) {
    $name = trim($item['name'] ?? '');
    $amount = floatval($item['amount'] ?? 0);
    $unit = trim($item['unit'] ?? '');
    $expirationDate = trim($item['expiration_date'] ?? '');

    if (!$name || $amount <= 0 || !$unit) {
        continue;
    }

    $ingredientStmt = $conn->prepare('
        SELECT ingredient_id 
        FROM ingredients 
        WHERE name_ing = ?
    ');
    $ingredientStmt->bind_param('s', $name);
    $ingredientStmt->execute();

    $ingredientResult = $ingredientStmt->get_result();

    if ($ingredientResult->num_rows === 0) {
        continue;
    }

    $ingredient = $ingredientResult->fetch_assoc();
    $ingredientId = (int)$ingredient['ingredient_id'];

    $insert = $conn->prepare("
        INSERT INTO user_inventory 
            (quantity, unit, user_id, ingredient_id, expiration_date) 
        VALUES 
            (?, ?, ?, ?, NULLIF(?, ''))
    ");
    $insert->bind_param('dsiis', $amount, $unit, $userId, $ingredientId, $expirationDate);
    $insert->execute();
}

echo json_encode([
    'success' => true,
    'guest' => false
]);
?><?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

// Delete old inventory using prepared statement
$deleteStmt = $conn->prepare("DELETE FROM user_inventory WHERE user_id = ?");
$deleteStmt->bind_param('i', $userId);
$deleteStmt->execute();

foreach ($data as $item) {
    $name = $item['name'];
    $amount = $item['amount'];
    $unit = $item['unit'];

    $ingredientStmt = $conn->prepare('SELECT ingredient_id FROM ingredients WHERE name_ing = ?');
    $ingredientStmt->bind_param('s', $name);
    $ingredientStmt->execute();
    $ingredientResult = $ingredientStmt->get_result();

    if ($ingredientResult->num_rows === 0) continue;

    $ingredient = $ingredientResult->fetch_assoc();
    $ingredientId = $ingredient['ingredient_id'];

    $insert = $conn->prepare('INSERT INTO user_inventory (quantity, unit, user_id, ingredient_id) VALUES (?, ?, ?, ?)');
    $insert->bind_param('dsii', $amount, $unit, $userId, $ingredientId);
    $insert->execute();
}

echo json_encode(['success' => true]);
?>
