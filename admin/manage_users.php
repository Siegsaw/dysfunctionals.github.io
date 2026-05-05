<?php
require_once __DIR__ . '/auth.php';
require_admin(); // Įsitikink, kad ši funkcija iškviečia exit(), jei vartotojas ne adminas.

$query = "SELECT user_id, username, email, created_at FROM users";
$result = mysqli_query($db_connection, $query);

if (!$result) {
    die("Klaida užklausoje: " . mysqli_error($db_connection));
}

echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Vartotojas</th>
            <th>El. paštas</th>
            <th>Sukurta</th>
            <th>Veiksmai</th>
        </tr>";

while ($row = mysqli_fetch_assoc($result)) {
    // Apsauga nuo XSS
    $safe_username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8');
    $user_id = (int)$row['user_id']; // Skaičiams pakanka castinimo

    echo "<tr>
            <td>{$user_id}</td>
            <td>{$safe_username}</td>
            <td>{$safe_email}</td>
            <td>{$row['created_at']}</td>
            <td>
                <a href='edit_user.php?id={$user_id}'>Redaguoti</a>
            </td>
          </tr>";
}
echo "</table>";
