<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];

$allAllergens = [];
$res = $conn->query("SELECT allergen_id, name FROM allergens ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $allAllergens[] = $row;
}

$selected = [];
$stmt = $conn->prepare("
    SELECT a.name
    FROM user_allergens ua
    JOIN allergens a ON ua.allergen_id = a.allergen_id
    WHERE ua.user_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $selected[] = $row['name'];
}

echo json_encode([
    'success' => true,
    'allergens' => $allAllergens,
    'selected' => $selected
]);
?>
