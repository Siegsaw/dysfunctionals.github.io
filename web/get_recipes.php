<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$sql = "
  SELECT 
    r.recipe_id,
    r.title AS name,
    COALESCE(rs.prep_time, 0) AS prep_time,
    COALESCE(rs.cook_time, 0) AS cook_time,
    (COALESCE(rs.prep_time, 0) + COALESCE(rs.cook_time, 0)) AS total_time_minutes,
    r.calories,
    r.protein,
    r.carbs,
    r.fat,
    reg.name AS region_name, 
    i.name_ing,
    ri.quantity,
    ri.unit,
    f.name AS flavor
FROM recipes r
JOIN recipe_ingredients ri ON r.recipe_id = ri.recipe_id
JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
LEFT JOIN recipe_regions rr ON r.recipe_id = rr.recipe_id
LEFT JOIN regions reg ON rr.region_id = reg.region_id 
LEFT JOIN recipe_flavors rf ON r.recipe_id = rf.recipe_id
LEFT JOIN flavors f ON rf.flavor_id = f.flavor_id
LEFT JOIN (
    SELECT 
        recipe_id, 
        SUM(CASE WHEN step_type = 'prep' THEN time_minutes ELSE 0 END) AS prep_time,
        SUM(CASE WHEN step_type = 'cook' THEN time_minutes ELSE 0 END) AS cook_time
    FROM recipe_steps
    GROUP BY recipe_id
) rs ON r.recipe_id = rs.recipe_id
ORDER BY r.recipe_id
";

$result = $conn->query($sql);

$recipes = [];

while ($row = $result->fetch_assoc()) {
  $id = (int)$row['recipe_id'];

  if (!isset($recipes[$id])) {
    $recipes[$id] = [
      'id' => $id,
      'name' => $row['name'],
      'time' => isset($row['total_time_minutes']) ? (int)$row['total_time_minutes'] : null,
      'prep_time' => isset($row['prep_time']) ? (int)$row['prep_time'] : 0,
      'cook_time' => isset($row['cook_time']) ? (int)$row['cook_time'] : 0,
      'calories' => isset($row['calories']) ? (float)$row['calories'] : null,
      'flavors' => [],
      'region_name' => $row['region_name'],
      'protein' => isset($row['protein']) ? (float)$row['protein'] : 0,
      'carbs' => isset($row['carbs']) ? (float)$row['carbs'] : 0,
      'fat' => isset($row['fat']) ? (float)$row['fat'] : 0,
      'ingredients' => []
    ];
        $recipes[$id]['_seen_ingredients'] = [];
  }
  
  $ing_key = $row['name_ing'] . $row['quantity'] . $row['unit'];
    if (!in_array($ing_key, $recipes[$id]['_seen_ingredients'])) {
        $recipes[$id]['ingredients'][] = [
            'name' => $row['name_ing'],
            'amount' => (float)$row['quantity'],
            'unit' => $row['unit']
        ];
        $recipes[$id]['_seen_ingredients'][] = $ing_key;
    }
    
    if (!empty($row['flavor']) && !in_array($row['flavor'], $recipes[$id]['flavors'])) {
        $recipes[$id]['flavors'][] = $row['flavor'];
    }
}

echo json_encode(array_values($recipes));
?>
