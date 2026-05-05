<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json');
require '/var/www/private/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

$ingredientId = (int)($data['ingredient_id'] ?? 0);
$nutrition = $data['nutrition'] ?? [];

if ($ingredientId <= 0 || empty($nutrition)) {
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

$required = $conn->query("
SELECT nutrient_id, LOWER(name_nutr) AS name
FROM nutrients
WHERE LOWER(name_nutr) IN ('calories','calorie','kcal','fat','carbs','carbohydrates','protein')
");

$requiredIds = [];
while ($r = $required->fetch_assoc()) {
    $requiredIds[$r['nutrient_id']] = $r['name'];
}

$submitted = [];

foreach ($nutrition as $n) {
    $id = (int)$n['nutrient_id'];
    $val = $n['amount_g100'];

    if ($val !== '' && is_numeric($val) && $val > 0) {
        $submitted[$id] = true;
    }
}

$missing = [];
foreach ($requiredIds as $id => $name) {
    if (empty($submitted[$id])) {
        $missing[] = $name;
    }
}

if ($missing) {
    echo json_encode([
        'success'=>false,
        'message'=>'Missing required (>0): '.implode(', ', $missing)
    ]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        INSERT INTO ingredient_nutrition
        (ingredient_id, nutrient_id, amount_per_100g, amount_per_unit)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            amount_per_100g = VALUES(amount_per_100g),
            amount_per_unit = VALUES(amount_per_unit)
    ");

    foreach ($nutrition as $n) {

        $id = (int)$n['nutrient_id'];
        $g = (float)$n['amount_g100'];
        $p = (float)$n['amount_pcs'];

        $stmt->bind_param("iidd", $ingredientId, $id, $g, $p);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        'success'=>true,
        'message'=>'Saved successfully'
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    echo json_encode([
        'success'=>false,
        'message'=>$e->getMessage()
    ]);
}
