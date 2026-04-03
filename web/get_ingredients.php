<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$result = $conn->query("
  SELECT 
    ingredient_id,
    name_ing,
    default_unit
  FROM ingredients
  ORDER BY name_ing
");

$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = [
    'id' => (int)$row['ingredient_id'],
    'name' => $row['name_ing'],
    'default_unit' => $row['default_unit']
  ];
}

echo json_encode($data);
?>
