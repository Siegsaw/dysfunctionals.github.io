<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recipeId <= 0) {
  echo json_encode(['error' => 'Invalid recipe ID']);
  exit;
}

$recipeQuery = $conn->prepare("
  SELECT
    r.recipe_id,
    r.title,
    r.description,
    r.calories,
    COALESCE(SUM(rs.time_minutes), 0) AS total_time
  FROM recipes r
  LEFT JOIN recipe_steps rs ON r.recipe_id = rs.recipe_id
  WHERE r.recipe_id = ?
  GROUP BY r.recipe_id
");

$recipeQuery->bind_param('i', $recipeId);
$recipeQuery->execute();
$recipeResult = $recipeQuery->get_result();
$recipe = $recipeResult->fetch_assoc();

if (!$recipe) {
  echo json_encode(['error' => 'Recipe not found']);
  exit;
}

$ingredientQuery = $conn->prepare("
  SELECT
    i.name_ing,
    ri.quantity,
    ri.unit
  FROM recipe_ingredients ri
  JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
  WHERE ri.recipe_id = ?
  ORDER BY i.name_ing
");

$ingredientQuery->bind_param('i', $recipeId);
$ingredientQuery->execute();
$ingredientResult = $ingredientQuery->get_result();

$ingredients = [];
while ($row = $ingredientResult->fetch_assoc()) {
  $ingredients[] = [
    'name' => $row['name_ing'],
    'amount' => (float)$row['quantity'],
    'unit' => $row['unit']
  ];
}

$stepQuery = $conn->prepare("
  SELECT
    step_id,
    step_number,
    step_type,
    instructions,
    time_minutes
  FROM recipe_steps
  WHERE recipe_id = ?
  ORDER BY step_number ASC
");

$stepQuery->bind_param('i', $recipeId);
$stepQuery->execute();
$stepResult = $stepQuery->get_result();

$steps = [];
while ($row = $stepResult->fetch_assoc()) {
  $steps[] = [
    'step_id' => (int)$row['step_id'],
    'step_number' => (int)$row['step_number'],
    'step_type' => $row['step_type'],
    'instructions' => $row['instructions'],
    'time_minutes' => (int)$row['time_minutes']
  ];
}

echo json_encode([
  'id' => (int)$recipe['recipe_id'],
  'name' => $recipe['title'],
]);
