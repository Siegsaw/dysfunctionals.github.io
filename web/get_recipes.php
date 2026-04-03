<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

// Get all recipes with their ingredients
$sql = "
    SELECT 
        r.recipe_id,
        r.name_recipe,
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
    $id = $row['recipe_id'];

    // Create recipe entry if not exists
    if (!isset($recipes[$id])) {
        $recipes[$id] = [
            'id' => $id,
            'name' => $row['name_recipe'],
            'ingredients' => []
        ];
    }

    // Add ingredient to recipe
    $recipes[$id]['ingredients'][] = [
        'name' => $row['name_ing'],
        'amount' => (float)$row['quantity'],
        'unit' => $row['unit']
    ];
}

// Return as array (not object)
echo json_encode(array_values($recipes));
?>
