<?php
header('Content-Type: application/json');
require '/var/www/private/db.php';

$sql = "SELECT region_id, name FROM regions ORDER BY name ASC";
$result = $conn->query($sql);

$cuisines = [];
while($row = $result->fetch_assoc()) {
    $cuisines[] = $row;
}

header('Content-Type: application/json');
echo json_encode($cuisines);
?>
