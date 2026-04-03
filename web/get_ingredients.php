<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$result = $conn->query('SELECT name_ing FROM ingredients ORDER BY name_ing ASC');

$ingredients = [];
while ($row = $result->fetch_assoc()) {
    $ingredients[] = $row['name_ing'];
}

echo json_encode($ingredients);
?>
