<?php
header('Content-Type: application/json');
require 'db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

$conn->query("DELETE FROM user_inventory WHERE user_id = $userId");

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