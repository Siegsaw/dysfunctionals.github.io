<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '/var/www/private/db.php'; // naudoja $conn (MySQLi)

if (!isset($_GET['ingredient_id']) || !is_numeric($_GET['ingredient_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid ingredient_id']);
    exit;
}

$ingredient_id = (int) $_GET['ingredient_id'];

try {

    $stmt = $conn->prepare("
        SELECT 
            i.ingredient_id AS substitute_id,
            i.name_ing AS substitute_name,
            i.unit AS substitute_unit,
            s.ratio,
            s.note
        FROM ingredient_substitutes s
        JOIN ingredients i 
            ON i.ingredient_id = s.substitute_id
        WHERE s.ingredient_id = ?
        ORDER BY i.name_ing ASC
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $ingredient_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $substitutes = [];

    while ($row = $result->fetch_assoc()) {
        $substitutes[] = [
            'substitute_id'   => (int)$row['substitute_id'],
            'substitute_name' => $row['substitute_name'],
            'substitute_unit' => $row['substitute_unit'],
            'ratio'           => (float)$row['ratio'],
            'note'            => $row['note']
        ];
    }

    echo json_encode([
        'ingredient_id' => $ingredient_id,
        'substitutes'   => $substitutes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'debug' => $e->getMessage() // gali vėliau išimti
    ]);
}
