<?php
 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require '/var/www/private/db.php';
 
if (!isset($_GET['ingredient_id']) || !is_numeric($_GET['ingredient_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid ingredient_id']);
    exit;
}
 
$ingredient_id = (int) $_GET['ingredient_id'];
 
try {
    $stmt = $pdo->prepare("
        SELECT
            s.id            AS substitute_id,
            i.name          AS substitute_name,
            i.unit          AS substitute_unit,
            sub.ratio       AS ratio,
            sub.note        AS note
        FROM ingredient_substitutes sub
        JOIN ingredients i ON i.id = sub.substitute_id
        JOIN ingredients s ON s.id = sub.substitute_id
        WHERE sub.ingredient_id = :ingredient_id
        ORDER BY i.name ASC
    ");
    $stmt->execute([':ingredient_id' => $ingredient_id]);
    $substitutes = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    echo json_encode([
        'ingredient_id' => $ingredient_id,
        'substitutes'   => $substitutes
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
