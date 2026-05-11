<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json');
require '/var/www/private/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$recipeId = (int)($data['recipe_id'] ?? 0);
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$servings = (int)($data['servings'] ?? 1);
$ingredients = $data['ingredients'] ?? [];
$steps = $data['steps'] ?? [];
$flavors = $data['flavors'] ?? [];
$regions = $data['regions'] ?? [];

if ($recipeId <= 0 || $title === '' || $servings < 1 || empty($ingredients) || empty($steps)) {
  echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
  exit;
}

$totalTimeMinutes = 0;
foreach ($steps as $step) {
  $totalTimeMinutes += (int)$step['time_minutes'];
}

$calories = 0.0;
$protein = 0.0;
$fat = 0.0;
$carbs = 0.0;

$densityStmt = $conn->prepare("
  SELECT name_ing, density_g_per_ml
  FROM ingredients
  WHERE ingredient_id = ?
");

$nutritionStmt = $conn->prepare("
  SELECT 
    n.name_nutr AS nutrient_name,
    inut.amount_per_100g,
    inut.amount_per_unit
  FROM ingredient_nutrition inut
  JOIN nutrients n ON inut.nutrient_id = n.nutrient_id
  WHERE inut.ingredient_id = ?
");

foreach ($ingredients as $ing) {
  $ingredientId = (int)$ing['ingredient_id'];
  $quantity = (float)$ing['amount'];
  $unit = trim($ing['unit']);

  $densityStmt->bind_param("i", $ingredientId);
  $densityStmt->execute();
  $ingredientRow = $densityStmt->get_result()->fetch_assoc();

  if (!$ingredientRow) {
    echo json_encode(['success' => false, 'message' => 'Ingredient not found: ID ' . $ingredientId]);
    exit;
  }

  $density = isset($ingredientRow['density_g_per_ml']) ? (float)$ingredientRow['density_g_per_ml'] : 0.0;

  $nutritionStmt->bind_param("i", $ingredientId);
  $nutritionStmt->execute();
  $nutritionRes = $nutritionStmt->get_result();

  while ($nutRow = $nutritionRes->fetch_assoc()) {
    $nutrientName = strtolower(trim($nutRow['nutrient_name']));
    $amountPer100g = (float)$nutRow['amount_per_100g'];
    $amountPerUnit = (float)$nutRow['amount_per_unit'];

    $contribution = 0.0;

    if ($unit === 'g') {
      $contribution = ($quantity / 100.0) * $amountPer100g;
    } elseif ($unit === 'ml') {
      if ($density <= 0) {
        echo json_encode([
          'success' => false,
          'message' => 'Ingredient "' . $ingredientRow['name_ing'] . '" is missing density_g_per_ml.'
        ]);
        exit;
      }

      $grams = $quantity * $density;
      $contribution = ($grams / 100.0) * $amountPer100g;
    } elseif ($unit === 'pcs') {
      $contribution = $quantity * $amountPerUnit;
    }

    if ($nutrientName === 'calories') {
      $calories += $contribution;
    } elseif ($nutrientName === 'protein') {
      $protein += $contribution;
    } elseif ($nutrientName === 'fat') {
      $fat += $contribution;
    } elseif ($nutrientName === 'carbs') {
      $carbs += $contribution;
    }
  }
}

$calories = round($calories, 2);
$protein = round($protein, 2);
$fat = round($fat, 2);
$carbs = round($carbs, 2);

$conn->begin_transaction();

try {
  $recipeStmt = $conn->prepare("
    UPDATE recipes
    SET 
      title = ?,
      description = ?,
      servings = ?,
      calories = ?,
      protein = ?,
      fat = ?,
      carbs = ?,
      total_time_minutes = ?
    WHERE recipe_id = ?
  ");

  $recipeStmt->bind_param(
    "ssiddddii",
    $title,
    $description,
    $servings,
    $calories,
    $protein,
    $fat,
    $carbs,
    $totalTimeMinutes,
    $recipeId
  );

  $recipeStmt->execute();

  $delIng = $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
  $delIng->bind_param("i", $recipeId);
  $delIng->execute();

  $delSteps = $conn->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?");
  $delSteps->bind_param("i", $recipeId);
  $delSteps->execute();

  $delFlavors = $conn->prepare("DELETE FROM recipe_flavors WHERE recipe_id = ?");
  $delFlavors->bind_param("i", $recipeId);
  $delFlavors->execute();

  $delRegions = $conn->prepare("DELETE FROM recipe_regions WHERE recipe_id = ?");
  $delRegions->bind_param("i", $recipeId);
  $delRegions->execute();

  $ingredientStmt = $conn->prepare("
    INSERT INTO recipe_ingredients (
      quantity,
      unit,
      is_optional,
      recipe_id,
      ingredient_id
    )
    VALUES (?, ?, 0, ?, ?)
  ");

  foreach ($ingredients as $ing) {
    $quantity = (float)$ing['amount'];
    $unit = trim($ing['unit']);
    $ingredientId = (int)$ing['ingredient_id'];

    $ingredientStmt->bind_param("dsii", $quantity, $unit, $recipeId, $ingredientId);
    $ingredientStmt->execute();
  }

  $stepStmt = $conn->prepare("
    INSERT INTO recipe_steps (
      step_number,
      step_type,
      instructions,
      time_minutes,
      recipe_id
    )
    VALUES (?, ?, ?, ?, ?)
  ");

  foreach ($steps as $step) {
    $stepNumber = (int)$step['step_number'];
    $stepType = trim($step['step_type']);
    $instructions = trim($step['instructions']);
    $timeMinutes = (int)$step['time_minutes'];

    $stepStmt->bind_param("issii", $stepNumber, $stepType, $instructions, $timeMinutes, $recipeId);
    $stepStmt->execute();
  }

  if (!empty($flavors)) {
    $flStmt = $conn->prepare("
      INSERT INTO recipe_flavors (recipe_id, flavor_id)
      VALUES (?, ?)
    ");

    foreach ($flavors as $flavorId) {
      $flavorId = (int)$flavorId;
      $flStmt->bind_param("ii", $recipeId, $flavorId);
      $flStmt->execute();
    }
  }

  if (!empty($regions)) {
    $regStmt = $conn->prepare("
      INSERT INTO recipe_regions (recipe_id, region_id)
      VALUES (?, ?)
    ");

    foreach ($regions as $regionId) {
      $regionId = (int)$regionId;
      $regStmt->bind_param("ii", $recipeId, $regionId);
      $regStmt->execute();
    }
  }

  $conn->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Recipe updated successfully.',
    'calculated' => [
      'total_time_minutes' => $totalTimeMinutes,
      'calories' => $calories,
      'protein' => $protein,
      'fat' => $fat,
      'carbs' => $carbs
    ]
  ]);

} catch (Throwable $e) {
  $conn->rollback();

  echo json_encode([
    'success' => false,
    'message' => 'Update failed: ' . $e->getMessage()
  ]);
}
