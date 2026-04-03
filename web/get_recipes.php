<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$sql = "
  SELECT 
    r.recipe_id,
    r.title,
    r.total_time_minutes,
    r.calories,
    i.name_ing,
    ri.quantity,
    ri.unit
  FROM recipes r
  JOIN recipe_ingredients ri ON r.recipe_id = ri.recipe_id
  JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
  ORDER BY r.recipe_id
";

$result = $conn->query($sql);

$recipes = [];

while ($row = $result->fetch_assoc()) {
  $id = (int)$row['recipe_id'];

  if (!isset($recipes[$id])) {
    $recipes[$id] = [
      'id' => $id,
      'name' => $row['title'],
      'time' => isset($row['total_time_minutes']) ? (int)$row['total_time_minutes'] : null,
      'calories' => isset($row['calories']) ? (float)$row['calories'] : null,
      'ingredients' => []
    ];
  }

  $recipes[$id]['ingredients'][] = [
    'name' => $row['name_ing'],
    'amount' => (float)$row['quantity'],
    'unit' => $row['unit']
  ];
}

echo json_encode(array_values($recipes));
?>
