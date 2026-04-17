<?php
require 'db.php';

$sql = "SELECT name FROM flavors ORDER BY name ASC";
$result = $conn->query($sql);

$flavors = [];

while ($row = $result->fetch_assoc()) {
    $flavors[] = $row['name'];
}

echo json_encode($flavors);
