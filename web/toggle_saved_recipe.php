<?php
session_start();
header('Content-Type: application/json');

require '/var/www/private/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to save recipes.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$recipeId = isset($data['recipe_id']) ? (int)$data['recipe_id'] : 0;
$userId = (int)$_SESSION['user_id'];

if ($recipeId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid recipe.'
    ]);
    exit;
}

$check = $conn->prepare("
    SELECT saved_recipe_id
    FROM saved_recipes
    WHERE user_id = ? AND recipe_id = ?
");
$check->bind_param("ii", $userId, $recipeId);
$check->execute();
$result = $check->get_result();

if ($row = $result->fetch_assoc()) {
    $delete = $conn->prepare("
        DELETE FROM saved_recipes
        WHERE user_id = ? AND recipe_id = ?
    ");
    $delete->bind_param("ii", $userId, $recipeId);
    $delete->execute();

    echo json_encode([
        'success' => true,
        'saved' => false,
        'message' => 'Recipe removed from saved recipes.'
    ]);
    exit;
}

$insert = $conn->prepare("
    INSERT INTO saved_recipes (user_id, recipe_id)
    VALUES (?, ?)
");
$insert->bind_param("ii", $userId, $recipeId);
$insert->execute();

echo json_encode([
    'success' => true,
    'saved' => true,
    'message' => 'Recipe saved.'
]);
