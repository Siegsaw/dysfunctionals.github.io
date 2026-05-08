<?php
require_once __DIR__ . '/auth.php';
require_admin(true);

header('Content-Type: application/json');
require '/var/www/private/db.php';

$recipeId = (int)($_GET['recipe_id'] ?? 0);

if ($recipeId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid recipe ID.']);
  exit;
}

$stmt = $conn->prepare("
  SELECT recipe_id, title, description
  FROM recipes
  WHERE recipe_id = ?
");
$stmt->bind_param("i", $recipeId);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
  echo json_encode(['success' => false, 'message' => 'Recipe not found.']);
  exit;
}

$ingStmt = $conn->prepare("
  SELECT 
    ri.ingredient_id,
    i.name_ing,
    ri.quantity,
    ri.unit
  FROM recipe_ingredients ri
  JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
  WHERE ri.recipe_id = ?
  ORDER BY ri.id_recipe_ingredients ASC
");
$ingStmt->bind_param("i", $recipeId);
$ingStmt->execute();
$ingRes = $ingStmt->get_result();

$ingredients = [];
while ($row = $ingRes->fetch_assoc()) {
  $ingredients[] = [
    'ingredient_id' => (int)$row['ingredient_id'],
    'name' => $row['name_ing'],
    'amount' => (float)$row['quantity'],
    'unit' => $row['unit']
  ];
}

$stepStmt = $conn->prepare("
  SELECT step_number, step_type, time_minutes, instructions
  FROM recipe_steps
  WHERE recipe_id = ?
  ORDER BY step_number ASC
");
$stepStmt->bind_param("i", $recipeId);
$stepStmt->execute();
$stepRes = $stepStmt->get_result();

$steps = [];
while ($row = $stepRes->fetch_assoc()) {
  $steps[] = [
    'step_number' => (int)$row['step_number'],
    'step_type' => $row['step_type'],
    'time_minutes' => (int)$row['time_minutes'],
    'instructions' => $row['instructions']
  ];
}

$flavorStmt = $conn->prepare("
  SELECT flavor_id
  FROM recipe_flavors
  WHERE recipe_id = ?
");
$flavorStmt->bind_param("i", $recipeId);
$flavorStmt->execute();
$flavorRes = $flavorStmt->get_result();

$flavors = [];
while ($row = $flavorRes->fetch_assoc()) {
  $flavors[] = (int)$row['flavor_id'];
}

$regionStmt = $conn->prepare("
  SELECT region_id
  FROM recipe_regions
  WHERE recipe_id = ?
");
$regionStmt->bind_param("i", $recipeId);
$regionStmt->execute();
$regionRes = $regionStmt->get_result();

$regions = [];
while ($row = $regionRes->fetch_assoc()) {
  $regions[] = (int)$row['region_id'];
}

echo json_encode([
  'success' => true,
  'recipe' => $recipe,
  'ingredients' => $ingredients,
  'steps' => $steps,
  'flavors' => $flavors,
  'regions' => $regions
]);
