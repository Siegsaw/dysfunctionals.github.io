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
    ri.unit,
    
    SUM(CASE WHEN rs.step_type = 'prep' THEN rs.time_minutes ELSE 0 END) AS prep_time,
    SUM(CASE WHEN rs.step_type = 'cook' THEN rs.time_minutes ELSE 0 END) AS cook_time
    
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
      'prep_time' => isset($row['prep_time']) ? (int)$row['prep_time'] : 0,
      'cook_time' => isset($row['cook_time']) ? (int)$row['cook_time'] : 0,
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
