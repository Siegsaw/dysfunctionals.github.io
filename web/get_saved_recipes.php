<?php
session_start();
header('Content-Type: application/json');

require '/var/www/private/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        r.recipe_id,
        r.title,
        r.total_time_minutes,
        r.calories,
        sr.saved_at
    FROM saved_recipes sr
    JOIN recipes r ON sr.recipe_id = r.recipe_id
    WHERE sr.user_id = ?
    ORDER BY sr.saved_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$recipes = [];

while ($row = $result->fetch_assoc()) {
    $recipes[] = [
        'id' => (int)$row['recipe_id'],
        'name' => $row['title'],
        'time' => (int)$row['total_time_minutes'],
        'calories' => (float)$row['calories'],
        'saved_at' => $row['saved_at']
    ];
}

echo json_encode($recipes);
