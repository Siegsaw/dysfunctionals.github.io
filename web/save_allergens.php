<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$selected = $data['allergens'] ?? [];

if (!is_array($selected)) {
    echo json_encode(['success' => false, 'message' => 'Invalid allergens data']);
    exit;
}

$conn->begin_transaction();

try {
    $deleteStmt = $conn->prepare("DELETE FROM user_allergens WHERE user_id = ?");
    $deleteStmt->bind_param('i', $userId);
    $deleteStmt->execute();

    $findStmt = $conn->prepare("SELECT allergen_id FROM allergens WHERE name = ?");
    $insertStmt = $conn->prepare("INSERT INTO user_allergens (user_id, allergen_id) VALUES (?, ?)");

    foreach ($selected as $name) {
        $name = trim($name);
        if ($name === '') continue;

        $findStmt->bind_param('s', $name);
        $findStmt->execute();
        $result = $findStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $allergenId = (int)$row['allergen_id'];
            $insertStmt->bind_param('ii', $userId, $allergenId);
            $insertStmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Allergens updated']);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to save allergens']);
}
?>
