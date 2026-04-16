<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$ingredients = $data['ingredients'] ?? [];
$steps = $data['steps'] ?? [];

if ($title === '' || !is_array($ingredients) || !count($ingredients) || !is_array($steps) || !count($steps)) {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid payload.'
  ]);
  exit;
}

/* ── CALCULATE TOTAL TIME ───────────────────────────── */
$totalTimeMinutes = 0;
foreach ($steps as $step) {
  $totalTimeMinutes += (int)($step['time_minutes'] ?? 0);
}

/* ── CALCULATE NUTRITION ────────────────────────────── */
$calories = 0.0;
$protein  = 0.0;
$fat      = 0.0;
$carbs    = 0.0;

/* Get ingredient density */
$densityStmt = $conn->prepare("
  SELECT name_ing, density_g_per_ml
  FROM ingredients
  WHERE ingredient_id = ?
");

/* Get nutrition rows for ingredient */
$nutritionStmt = $conn->prepare("
  SELECT 
    n.name AS nutrient_name,
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
  $densityRes = $densityStmt->get_result();
  $ingredientRow = $densityRes->fetch_assoc();

  if (!$ingredientRow) {
    echo json_encode([
      'success' => false,
      'message' => 'Ingredient not found: ID ' . $ingredientId
    ]);
    exit;
  }

  $density = isset($ingredientRow['density_g_per_ml']) ? (float)$ingredientRow['density_g_per_ml'] : 0.0;

  $nutritionStmt->bind_param("i", $ingredientId);
  $nutritionStmt->execute();
  $nutritionRes = $nutritionStmt->get_result();

  while ($nutRow = $nutritionRes->fetch_assoc()) {
    $nutrientName = strtolower(trim($nutRow['nutrient_name']));
    $amountPer100g = isset($nutRow['amount_per_100g']) ? (float)$nutRow['amount_per_100g'] : 0.0;
    $amountPerUnit = isset($nutRow['amount_per_unit']) ? (float)$nutRow['amount_per_unit'] : 0.0;

    $contribution = 0.0;

    if ($unit === 'g') {
      $contribution = ($quantity / 100.0) * $amountPer100g;
    } elseif ($unit === 'ml') {
      if ($density <= 0) {
        echo json_encode([
          'success' => false,
          'message' => 'Ingredient "' . $ingredientRow['name_ing'] . '" is missing density_g_per_ml for ml conversion.'
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

/* Round totals */
$calories = round($calories, 2);
$protein  = round($protein, 2);
$fat      = round($fat, 2);
$carbs    = round($carbs, 2);

$conn->begin_transaction();

try {
  /* Insert recipe */
  $recipeStmt = $conn->prepare("
    INSERT INTO recipes (
      title,
      description,
      created_at,
      calories,
      protein,
      fat,
      carbs,
      total_time_minutes
    )
    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
  ");

  $recipeStmt->bind_param(
    "ssddddi",
    $title,
    $description,
    $calories,
    $protein,
    $fat,
    $carbs,
    $totalTimeMinutes
  );
  $recipeStmt->execute();

  $recipeId = $conn->insert_id;

  /* Insert recipe ingredients */
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

  /* Insert recipe steps */
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

  $conn->commit();

  echo json_encode([
    'success' => true,
    'recipe_id' => $recipeId,
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
    'message' => 'Database insert failed: ' . $e->getMessage()
  ]);
}
?>
