<?php
session_start();

header('Content-Type: application/json');
require '/var/www/private/db.php';

$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

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
    r.protein,
    r.carbs,
    r.fat,
    COALESCE(SUM(rs.time_minutes), 0) AS total_time
  FROM recipes r
  LEFT JOIN recipe_steps rs ON r.recipe_id = rs.recipe_id
  WHERE r.recipe_id = ?
  GROUP BY r.recipe_id, r.title, r.description, r.calories, r.protein, r.carbs, r.fat
");

$recipeQuery->bind_param('i', $recipeId);
$recipeQuery->execute();
$recipeResult = $recipeQuery->get_result();
$recipe = $recipeResult->fetch_assoc();

if (!$recipe) {
  echo json_encode(['error' => 'Recipe not found']);
  exit;
}

$flavorQuery = $conn->prepare("
    SELECT f.name 
    FROM flavors f
    JOIN recipe_flavors rf ON f.flavor_id = rf.flavor_id
    WHERE rf.recipe_id = ?
");
$flavorQuery->bind_param('i', $recipeId);
$flavorQuery->execute();
$flavorResult = $flavorQuery->get_result();
$flavors = [];
while ($fRow = $flavorResult->fetch_assoc()) {
    $flavors[] = $fRow['name'];
}

$regionQuery = $conn->prepare("
    SELECT r.name
    FROM regions r
    JOIN recipe_regions rr ON r.region_id = rr.region_id
    WHERE rr.recipe_id = ?
");
$regionQuery->bind_param('i', $recipeId);
$regionQuery->execute();
$regionResult = $regionQuery->get_result();

$regions = [];
while ($row = $regionResult->fetch_assoc()) {
    $regions[] = $row['name'];
}

$ingredientQuery = $conn->prepare("
  SELECT
    i.ingredient_id,
    i.name_ing,
    ri.quantity,
    ri.unit,
    CASE
      WHEN COUNT(ua.allergen_id) > 0 THEN 1
      ELSE 0
    END AS is_allergic
  FROM recipe_ingredients ri
  JOIN ingredients i
    ON ri.ingredient_id = i.ingredient_id
  LEFT JOIN ingredient_allergens ia
    ON i.ingredient_id = ia.ingredient_id
  LEFT JOIN user_allergens ua
    ON ia.allergen_id = ua.allergen_id
    AND ua.user_id = ?
  WHERE ri.recipe_id = ?
  GROUP BY i.ingredient_id, i.name_ing, ri.quantity, ri.unit
  ORDER BY i.name_ing
");

$ingredientQuery->bind_param('ii', $userId, $recipeId);
$ingredientQuery->execute();
$ingredientResult = $ingredientQuery->get_result();

$ingredients = [];
while ($row = $ingredientResult->fetch_assoc()) {
  $ingredients[] = [
    'id' => (int)$row['ingredient_id'],
    'name' => $row['name_ing'],
    'amount' => (float)$row['quantity'],
    'unit' => $row['unit'],
    'is_allergic' => (bool)$row['is_allergic']
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
  'description' => $recipe['description'],
  'calories' => (float)$recipe['calories'],
  'protein' => (float)$recipe['protein'],
  'carbs' => (float)$recipe['carbs'],
  'fat' => (float)$recipe['fat'],
  'total_time' => (int)$recipe['total_time'],
  'flavors' => $flavors,
  'cuisines' => $regions,
  'ingredients' => $ingredients,
  'steps' => $steps
]);
?>
