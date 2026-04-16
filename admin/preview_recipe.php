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

if ($title === '') {
  $errors[] = 'Recipe title is required.';
}

if (!is_array($ingredients) || count($ingredients) === 0) {
  $errors[] = 'At least one ingredient is required.';
}

if (!is_array($steps) || count($steps) === 0) {
  $errors[] = 'At least one step is required.';
}

/* Validate ingredients against DB */
$ingredientStmt = $conn->prepare("
  SELECT ingredient_id, name_ing, default_unit
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
    'unit' => $row['default_unit']
  ];
}

/* Validate steps */
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

/* Sort steps */
usort($cleanSteps, function($a, $b) {
  return $a['step_number'] <=> $b['step_number'];
});

/* Optional strict sequential check */
for ($i = 0; $i < count($cleanSteps); $i++) {
  $expected = $i + 1;
  if ($cleanSteps[$i]['step_number'] !== $expected) {
    $errors[] = 'Step numbers must be sequential starting from 1.';
    break;
  }
}

$totalTime = 0;
foreach ($cleanSteps as $step) {
  $totalTime += $step['time_minutes'];
}

$payload = [
  'title' => $title,
  'description' => $description,
  'ingredients' => array_map(function($ing) {
    return [
      'ingredient_id' => $ing['ingredient_id'],
      'amount' => $ing['amount'],
      'unit' => $ing['unit']
    ];
  }, $cleanIngredients),
  'steps' => $cleanSteps,
  'total_time_minutes' => $totalTime
];

$preview = [
  'title' => $title,
  'description' => $description,
  'ingredients' => $cleanIngredients,
  'steps' => $cleanSteps,
  'total_time_minutes' => $totalTime
];

echo json_encode([
  'errors' => $errors,
  'payload' => count($errors) ? null : $payload,
  'preview' => $preview
]);
?>
