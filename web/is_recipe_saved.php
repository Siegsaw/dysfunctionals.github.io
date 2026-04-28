<?php
session_start();
header('Content-Type: application/json');

require '/var/www/private/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => false,
        'saved' => false
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$recipeId = isset($_GET['recipe_id']) ? (int)$_GET['recipe_id'] : 0;

if ($recipeId <= 0) {
    echo json_encode([
        'loggedIn' => true,
        'saved' => false
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT saved_recipe_id
    FROM saved_recipes
    WHERE user_id = ? AND recipe_id = ?
");
$stmt->bind_param("ii", $userId, $recipeId);
$stmt->execute();

$result = $stmt->get_result();

echo json_encode([
    'loggedIn' => true,
    'saved' => $result->num_rows > 0
]);
