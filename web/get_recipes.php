<?php
session_start();
header('Content-Type: application/json');
require '/var/www/private/db.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$sql = "
  SELECT 
    r.recipe_id,
    r.title AS name,
    r.servings,
    COALESCE(rs.prep_time, 0) AS prep_time,
    COALESCE(rs.cook_time, 0) AS cook_time,
    (COALESCE(rs.prep_time, 0) + COALESCE(rs.cook_time, 0)) AS total_time_minutes,
    r.calories,
    r.protein,
    r.carbs,
    r.fat,
    reg.name AS region_name,
    i.ingredient_id,
    i.name_ing,
    ri.quantity,
    ri.unit,
    f.name AS flavor,
    (
      SELECT GROUP_CONCAT(a.name)
      FROM ingredient_allergens ia2
      JOIN allergens a ON ia2.allergen_id = a.allergen_id
      WHERE ia2.ingredient_id = i.ingredient_id
    ) AS allergen_groups,
    (
      SELECT GROUP_CONCAT(DISTINCT a2.name)
      FROM ingredient_allergens ia3
      JOIN allergens a2 ON ia3.allergen_id = a2.allergen_id
      JOIN user_allergens ua2 
        ON ua2.allergen_id = ia3.allergen_id
       AND ua2.user_id = $userId
      WHERE ia3.ingredient_id = i.ingredient_id
    ) AS matched_allergens,
    CASE
      WHEN COUNT(ua.allergen_id) > 0 THEN 1
      ELSE 0
    END AS is_allergic
  FROM recipes r
  JOIN recipe_ingredients ri ON r.recipe_id = ri.recipe_id
  JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
  LEFT JOIN ingredient_allergens ia ON i.ingredient_id = ia.ingredient_id
  LEFT JOIN user_allergens ua
    ON ia.allergen_id = ua.allergen_id
   AND ua.user_id = $userId
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
  GROUP BY
    r.recipe_id,
    r.title,
    r.servings,
    rs.prep_time,
    rs.cook_time,
    r.calories,
    r.protein,
    r.carbs,
    r.fat,
    reg.name,
    i.ingredient_id,
    i.name_ing,
    ri.quantity,
    ri.unit,
    f.name
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
      'servings' => isset($row['servings']) ? (int)$row['servings'] : 0,
      'time' => isset($row['total_time_minutes']) ? (int)$row['total_time_minutes'] : null,
      'prep_time' => isset($row['prep_time']) ? (int)$row['prep_time'] : 0,
      'cook_time' => isset($row['cook_time']) ? (int)$row['cook_time'] : 0,
      'calories' => isset($row['calories']) ? (float)$row['calories'] : null,
      'flavors' => [],
      'region_name' => $row['region_name'],
      'protein' => isset($row['protein']) ? (float)$row['protein'] : 0,
      'carbs' => isset($row['carbs']) ? (float)$row['carbs'] : 0,
      'fat' => isset($row['fat']) ? (float)$row['fat'] : 0,
      'ingredients' => [],
      'matched_allergens' => []
    ];
    $recipes[$id]['_seen_ingredients'] = [];
  }

  $ingKey = $row['ingredient_id'] . '|' . $row['quantity'] . '|' . $row['unit'];

  if (!in_array($ingKey, $recipes[$id]['_seen_ingredients'])) {
    $ingredientMatchedAllergens = [];

    if (!empty($row['matched_allergens'])) {
      $ingredientMatchedAllergens = array_values(array_unique(array_filter(array_map('trim', explode(',', $row['matched_allergens'])))));
    }

    $recipes[$id]['ingredients'][] = [
      'id' => (int)$row['ingredient_id'],
      'name' => $row['name_ing'],
      'amount' => (float)$row['quantity'],
      'unit' => $row['unit'],
      'allergen_groups' => $row['allergen_groups'],
      'matched_allergens' => $ingredientMatchedAllergens,
      'is_allergic' => (bool)$row['is_allergic']
    ];

    foreach ($ingredientMatchedAllergens as $allergenName) {
      if (!in_array($allergenName, $recipes[$id]['matched_allergens'])) {
        $recipes[$id]['matched_allergens'][] = $allergenName;
      }
    }

    $recipes[$id]['_seen_ingredients'][] = $ingKey;
  }

  if (!empty($row['flavor']) && !in_array($row['flavor'], $recipes[$id]['flavors'])) {
    $recipes[$id]['flavors'][] = $row['flavor'];
  }
}

foreach ($recipes as &$recipe) {
  $recipe['has_allergen'] = count($recipe['matched_allergens']) > 0;
  unset($recipe['_seen_ingredients']);
}

echo json_encode(array_values($recipes));
?>
