<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json');
require '/var/www/private/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

$ingredientId = isset($data['ingredient_id']) ? (int)$data['ingredient_id'] : 0;
$nutrition    = $data['nutrition'] ?? [];

if ($ingredientId <= 0 || empty($nutrition)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

$requiredResult = $conn->query("
    SELECT nutrient_id, LOWER(name_nutr) AS name_nutr
    FROM nutrients
    WHERE LOWER(name_nutr) IN ('calories', 'calorie', 'kcal', 'fat', 'carbs', 'carbohydrates', 'protein')
");

$requiredNutrients = [];
while ($row = $requiredResult->fetch_assoc()) {
    $requiredNutrients[(int)$row['nutrient_id']] = $row['name_nutr'];
}

$submittedNutrients = [];
foreach ($nutrition as $item) {
    $nId = (int)($item['nutrient_id'] ?? 0);
    $valG = $item['amount_g100'] ?? '';

    if ($nId > 0 && $valG !== '' && is_numeric($valG)) {
        $submittedNutrients[$nId] = true;
    }
}

$missing = [];
foreach ($requiredNutrients as $nId => $name) {
    if (empty($submittedNutrients[$nId])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required macronutrients: ' . implode(', ', $missing)
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        INSERT INTO ingredient_nutrition (
            ingredient_id, 
            nutrient_id, 
            amount_per_100g, 
            amount_per_unit
        )
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            amount_per_100g = VALUES(amount_per_100g),
            amount_per_unit = VALUES(amount_per_unit)
    ");

    $count = 0;
    foreach ($nutrition as $item) {
        $nId = (int)$item['nutrient_id'];
        $valG = (float)$item['amount_g100'];
        $valP = (float)$item['amount_pcs'];

        $stmt->bind_param("iidd", $ingredientId, $nId, $valG, $valP);
        $stmt->execute();
        $count++;
    }

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Successfully updated $count nutrient values."
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
