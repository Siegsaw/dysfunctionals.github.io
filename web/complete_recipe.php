<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';
require 'session.php';

requireLogin();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$recipeId = isset($data['recipe_id']) ? (int)$data['recipe_id'] : 0;

if ($recipeId <= 0) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid recipe ID'
    ]);
    exit;
}

$recipeStmt = $conn->prepare("
    SELECT recipe_id, title
    FROM recipes
    WHERE recipe_id = ?
    LIMIT 1
");
$recipeStmt->bind_param('i', $recipeId);
$recipeStmt->execute();
$recipeResult = $recipeStmt->get_result();
$recipe = $recipeResult->fetch_assoc();

if (!$recipe) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Recipe not found'
    ]);
    exit;
}

$ingredientStmt = $conn->prepare("
    SELECT
        ri.ingredient_id,
        ri.quantity,
        ri.unit,
        i.name_ing
    FROM recipe_ingredients ri
    JOIN ingredients i ON i.ingredient_id = ri.ingredient_id
    WHERE ri.recipe_id = ?
    ORDER BY i.name_ing
");
$ingredientStmt->bind_param('i', $recipeId);
$ingredientStmt->execute();
$ingredientResult = $ingredientStmt->get_result();

$requiredIngredients = [];
while ($row = $ingredientResult->fetch_assoc()) {
    $requiredIngredients[] = [
        'ingredient_id' => (int)$row['ingredient_id'],
        'name' => $row['name_ing'],
        'quantity' => (float)$row['quantity'],
        'unit' => $row['unit']
    ];
}

if (!$requiredIngredients) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'This recipe has no ingredients'
    ]);
    exit;
}

$checkStmt = $conn->prepare("
    SELECT inventory_id, quantity, unit
    FROM user_inventory
    WHERE user_id = ? AND ingredient_id = ?
    LIMIT 1
");

$updateStmt = $conn->prepare("
    UPDATE user_inventory
    SET quantity = ?
    WHERE inventory_id = ?
");

$deleteStmt = $conn->prepare("
    DELETE FROM user_inventory
    WHERE inventory_id = ?
");

$problems = [];

foreach ($requiredIngredients as $ingredient) {
    $ingredientId = $ingredient['ingredient_id'];

    $checkStmt->bind_param('ii', $userId, $ingredientId);
    $checkStmt->execute();
    $inventoryResult = $checkStmt->get_result();
    $inventoryRow = $inventoryResult->fetch_assoc();

    if (!$inventoryRow) {
        $problems[] = $ingredient['name'] . ' is missing';
        continue;
    }

    $inventoryUnit = $inventoryRow['unit'];
    $inventoryQuantity = (float)$inventoryRow['quantity'];

    if ($inventoryUnit !== $ingredient['unit']) {
        $problems[] = $ingredient['name'] . ' has a unit mismatch';
        continue;
    }

    if ($inventoryQuantity < $ingredient['quantity']) {
        $problems[] = $ingredient['name'] . ' is not enough';
    }
}

if ($problems) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Cannot complete recipe',
        'problems' => $problems
    ]);
    exit;
}

$conn->begin_transaction();

try {
    foreach ($requiredIngredients as $ingredient) {
        $ingredientId = $ingredient['ingredient_id'];

        $checkStmt->bind_param('ii', $userId, $ingredientId);
        $checkStmt->execute();
        $inventoryResult = $checkStmt->get_result();
        $inventoryRow = $inventoryResult->fetch_assoc();

        if (!$inventoryRow) {
            throw new Exception('Inventory row missing during update');
        }

        $inventoryId = (int)$inventoryRow['inventory_id'];
        $newQuantity = (float)$inventoryRow['quantity'] - (float)$ingredient['quantity'];

        if ($newQuantity <= 0.00001) {
            $deleteStmt->bind_param('i', $inventoryId);
            $deleteStmt->execute();
        } else {
            $updateStmt->bind_param('di', $newQuantity, $inventoryId);
            $updateStmt->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Recipe completed and ingredients deducted',
        'recipe_name' => $recipe['title']
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update inventory'
    ]);
}
?>
