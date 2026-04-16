<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$ingredients = $data['ingredients'] ?? [];
$steps = $data['steps'] ?? [];
$totalTimeMinutes = (int)($data['total_time_minutes'] ?? 0);

if ($title === '' || !is_array($ingredients) || !count($ingredients) || !is_array($steps) || !count($steps)) {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid payload.'
  ]);
  exit;
}

$conn->begin_transaction();

try {
  /* Insert recipe */
  $recipeStmt = $conn->prepare("
    INSERT INTO recipes (title, description, created_at, total_time_minutes)
    VALUES (?, ?, NOW(), ?)
  ");
  $recipeStmt->bind_param("ssi", $title, $description, $totalTimeMinutes);
  $recipeStmt->execute();

  $recipeId = $conn->insert_id;

  /* Insert recipe_ingredients */
  $ingredientStmt = $conn->prepare("
    INSERT INTO recipe_ingredients (quantity, unit, is_optional, recipe_id, ingredient_id)
    VALUES (?, ?, 0, ?, ?)
  ");

  foreach ($ingredients as $ing) {
    $quantity = (float)$ing['amount'];
    $unit = trim($ing['unit']);
    $ingredientId = (int)$ing['ingredient_id'];

    $ingredientStmt->bind_param("dsii", $quantity, $unit, $recipeId, $ingredientId);
    $ingredientStmt->execute();
  }

  /* Insert recipe_steps */
  $stepStmt = $conn->prepare("
    INSERT INTO recipe_steps (step_number, step_type, instructions, time_minutes, recipe_id)
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
    'recipe_id' => $recipeId
  ]);
} catch (Throwable $e) {
  $conn->rollback();

  echo json_encode([
    'success' => false,
    'message' => 'Database insert failed: ' . $e->getMessage()
  ]);
}
?>
