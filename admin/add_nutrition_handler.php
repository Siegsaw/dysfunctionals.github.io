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
    $nutrientId = (int)($item['nutrient_id'] ?? 0);
    $amount = $item['amount'] ?? '';

    if ($nutrientId > 0 && $amount !== '' && is_numeric($amount)) {
        $submittedNutrients[$nutrientId] = true;
    }
}

$missing = [];

foreach ($requiredNutrients as $nutrientId => $name) {
    if (empty($submittedNutrients[$nutrientId])) {
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
        INSERT INTO ingredient_nutrition (ingredient_id, nutrient_id, amount_per_100g)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE amount_per_100g = VALUES(amount_per_100g)
    ");

    $count = 0;
    foreach ($nutrition as $item) {
        $nutrientId = (int)$item['nutrient_id'];
        $amount = (float)$item['amount'];

        $stmt->bind_param("iid", $ingredientId, $nutrientId, $amount);
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
?>
