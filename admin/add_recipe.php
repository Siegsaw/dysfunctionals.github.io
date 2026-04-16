<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$ingredients = $data['ingredients'] ?? [];
$steps = $data['steps'] ?? [];

if (!$title || !$ingredients || !$steps) {
  echo json_encode(['success' => false, 'message' => 'Missing data']);
  exit;
}

// ── INSERT RECIPE ──
$stmt = $conn->prepare("
  INSERT INTO recipes (title, description, created_at)
  VALUES (?, ?, NOW())
");
$stmt->bind_param("ss", $title, $description);
$stmt->execute();

$recipe_id = $stmt->insert_id;

// ── INGREDIENTS ──
$ingStmt = $conn->prepare("
  INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit)
  VALUES (?, ?, ?, ?)
");

foreach ($ingredients as $i) {
  $ingStmt->bind_param(
    "iids",
    $recipe_id,
    $i['ingredient_id'],
    $i['qty'],
    $i['unit']
  );
  $ingStmt->execute();
}

// ── STEPS ──
$stepStmt = $conn->prepare("
  INSERT INTO recipe_steps (recipe_id, step_number, step_type, instructions, time_minutes)
  VALUES (?, ?, ?, ?, ?)
");

$stepNumber = 1;

foreach ($steps as $s) {
  $stepStmt->bind_param(
    "iissi",
    $recipe_id,
    $stepNumber,
    $s['type'],
    $s['text'],
    $s['time']
  );
  $stepStmt->execute();

  $stepNumber++;
}

echo json_encode(['success' => true]);
?>
