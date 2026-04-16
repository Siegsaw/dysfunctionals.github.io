<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$ingredients = $data['ingredients'] ?? [];
$steps = $data['steps'] ?? [];

$errors = [];
$cleanIngredients = [];
$cleanSteps = [];

/* ── BASIC VALIDATION ───────────────────────────────────── */
if ($title === '') {
  $errors[] = 'Recipe title is required.';
}

if (!is_array($ingredients) || count($ingredients) === 0) {
  $errors[] = 'At least one ingredient is required.';
}

if (!is_array($steps) || count($steps) === 0) {
  $errors[] = 'At least one step is required.';
}

/* ── INGREDIENT VALIDATION ──────────────────────────────── */
$ingredientStmt = $conn->prepare("
  SELECT ingredient_id, name_ing, default_unit, density_g_per_ml
  FROM ingredients
  WHERE ingredient_id = ?
");

foreach ($ingredients as $index => $ing) {
  $ingredientId = (int)($ing['ingredient_id'] ?? 0);
  $amount = isset($ing['amount']) ? (float)$ing['amount'] : 0;
  $unit = trim($ing['unit'] ?? '');

  if ($ingredientId <= 0) {
    $errors[] = 'Ingredient #' . ($index + 1) . ' is invalid or not selected from the database.';
    continue;
  }

  if ($amount <= 0) {
    $errors[] = 'Ingredient #' . ($index + 1) . ' must have an amount greater than 0.';
    continue;
  }

  $ingredientStmt->bind_param("i", $ingredientId);
  $ingredientStmt->execute();
  $result = $ingredientStmt->get_result();
  $row = $result->fetch_assoc();

  if (!$row) {
    $errors[] = 'Ingredient #' . ($index + 1) . ' does not exist in ingredients table.';
    continue;
  }

  if ($unit !== $row['default_unit']) {
    $errors[] = 'Ingredient "' . $row['name_ing'] . '" must use unit "' . $row['default_unit'] . '".';
    continue;
  }

  $cleanIngredients[] = [
    'ingredient_id' => (int)$row['ingredient_id'],
    'name' => $row['name_ing'],
    'amount' => round($amount, 3),
    'unit' => $row['default_unit'],
    'density_g_per_ml' => isset($row['density_g_per_ml']) ? (float)$row['density_g_per_ml'] : 0.0
  ];
}

/* ── STEP VALIDATION ────────────────────────────────────── */
$seenStepNumbers = [];

foreach ($steps as $index => $step) {
  $stepNumber = (int)($step['step_number'] ?? 0);
  $stepType = trim($step['step_type'] ?? '');
  $timeMinutes = (int)($step['time_minutes'] ?? -1);
  $instructions = trim($step['instructions'] ?? '');

  if ($stepNumber <= 0) {
    $errors[] = 'Step #' . ($index + 1) . ' must have a step number greater than 0.';
    continue;
  }

  if (isset($seenStepNumbers[$stepNumber])) {
    $errors[] = 'Duplicate step number: ' . $stepNumber . '.';
    continue;
  }
  $seenStepNumbers[$stepNumber] = true;

  if ($stepType !== 'prep' && $stepType !== 'cook') {
    $errors[] = 'Step ' . $stepNumber . ' must have type prep or cook.';
    continue;
  }

  if ($timeMinutes < 0) {
    $errors[] = 'Step ' . $stepNumber . ' must have time 0 or more.';
    continue;
  }

  if ($instructions === '') {
    $errors[] = 'Step ' . $stepNumber . ' instructions cannot be empty.';
    continue;
  }

  $cleanSteps[] = [
    'step_number' => $stepNumber,
    'step_type' => $stepType,
    'time_minutes' => $timeMinutes,
    'instructions' => $instructions
  ];
}

usort($cleanSteps, function ($a, $b) {
  return $a['step_number'] <=> $b['step_number'];
});

for ($i = 0; $i < count($cleanSteps); $i++) {
  $expected = $i + 1;
  if ($cleanSteps[$i]['step_number'] !== $expected) {
    $errors[] = 'Step numbers must be sequential starting from 1.';
    break;
  }
}

/* ── TOTAL TIME ─────────────────────────────────────────── */
$totalTime = 0;
foreach ($cleanSteps as $step) {
  $totalTime += $step['time_minutes'];
}

/* ── NUTRITION CALCULATION ──────────────────────────────── */
/*
  Uses:
  - nutrients.name
  - ingredient_nutrition.amount_per_100g
  - ingredient_nutrition.amount_per_unit

  Assumed nutrient names:
  - Calories
  - Protein
  - Fat
  - Carbs

  Matching is case-insensitive.
*/
$calories = 0.0;
$protein  = 0.0;
$fat      = 0.0;
$carbs    = 0.0;

$nutritionStmt = $conn->prepare("
  SELECT
    n.name_nutr AS nutrient_name,
    n.unit AS nutrient_unit,
    inut.amount_per_100g,
    inut.amount_per_unit
  FROM ingredient_nutrition inut
  JOIN nutrients n ON inut.nutrient_id = n.nutrient_id
  WHERE inut.ingredient_id = ?
");

foreach ($cleanIngredients as $ing) {
  $ingredientId = (int)$ing['ingredient_id'];
  $amount = (float)$ing['amount'];
  $unit = $ing['unit'];
  $density = (float)$ing['density_g_per_ml'];

  $nutritionStmt->bind_param("i", $ingredientId);
  $nutritionStmt->execute();
  $nutritionRes = $nutritionStmt->get_result();

  $foundAnyNutrition = false;

  while ($nutRow = $nutritionRes->fetch_assoc()) {
    $foundAnyNutrition = true;

    $nutrientName = strtolower(trim($nutRow['nutrient_name']));
    $amountPer100g = isset($nutRow['amount_per_100g']) ? (float)$nutRow['amount_per_100g'] : 0.0;
    $amountPerUnit = isset($nutRow['amount_per_unit']) ? (float)$nutRow['amount_per_unit'] : 0.0;

    $contribution = 0.0;

    if ($unit === 'g') {
      $contribution = ($amount / 100.0) * $amountPer100g;
    } elseif ($unit === 'ml') {
      if ($density <= 0) {
        $errors[] = 'Ingredient "' . $ing['name'] . '" is missing density_g_per_ml, cannot convert ml to grams for nutrition calculation.';
        continue;
      }

      $grams = $amount * $density;
      $contribution = ($grams / 100.0) * $amountPer100g;
    } elseif ($unit === 'pcs') {
      $contribution = $amount * $amountPerUnit;
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

  if (!$foundAnyNutrition) {
    $errors[] = 'Ingredient "' . $ing['name'] . '" has no rows in ingredient_nutrition.';
  }
}

$calories = round($calories, 2);
$protein  = round($protein, 2);
$fat      = round($fat, 2);
$carbs    = round($carbs, 2);

/* ── CLEAN PAYLOAD FOR FINAL INSERT ─────────────────────── */
$payload = [
  'title' => $title,
  'description' => $description,
  'ingredients' => array_map(function ($ing) {
    return [
      'ingredient_id' => $ing['ingredient_id'],
      'amount' => $ing['amount'],
      'unit' => $ing['unit']
    ];
  }, $cleanIngredients),
  'steps' => $cleanSteps,
  'total_time_minutes' => $totalTime,
  'calories' => $calories,
  'protein' => $protein,
  'fat' => $fat,
  'carbs' => $carbs
];

/* ── PREVIEW RESPONSE ────────────────────────────────────── */
$preview = [
  'title' => $title,
  'description' => $description,
  'ingredients' => array_map(function ($ing) {
    return [
      'ingredient_id' => $ing['ingredient_id'],
      'name' => $ing['name'],
      'amount' => $ing['amount'],
      'unit' => $ing['unit']
    ];
  }, $cleanIngredients),
  'steps' => $cleanSteps,
  'total_time_minutes' => $totalTime,
  'calories' => $calories,
  'protein' => $protein,
  'fat' => $fat,
  'carbs' => $carbs
];

echo json_encode([
  'errors' => $errors,
  'payload' => count($errors) ? null : $payload,
  'preview' => $preview
]);
?>
