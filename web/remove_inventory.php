<?php
header('Content-Type: application/json');

require '/var/www/private/db.php';
require 'session.php';

$data = json_decode(file_get_contents('php://input'), true);
$ingredientId = $data['ingredient_id'] ?? null;

if (!$ingredientId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ingredient ID'
    ]);
    exit;
}

/*
  ── GUEST MODE ─────────────────────────────
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['guest_inventory'] = array_values(array_filter(
        $_SESSION['guest_inventory'] ?? [],
        fn($item) => (string)$item['ingredient_id'] !== (string)$ingredientId
    ));

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
$ingredientId = (int)$ingredientId;

if ($ingredientId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ingredient ID'
    ]);
    exit;
}

$stmt = $conn->prepare('
    DELETE FROM user_inventory 
    WHERE user_id = ? AND ingredient_id = ?
');
$stmt->bind_param('ii', $userId, $ingredientId);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove ingredient: ' . $conn->error
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'guest' => false
]);
?>
