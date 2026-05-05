<?php
require_once __DIR__ . '/auth.php';
require_admin();
require '/var/www/private/db.php';

$users = [];
$error = null;

if (!isset($conn)) {
    $error = "DB connection variable ($conn) not found. Check db.php.";
} else {
    $sql = "SELECT user_id, username, email, created_at FROM users ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_free_result($result);
    } else {
        $error = "Error executing query: " . mysqli_error($conn);
    }
}

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>User Manager - PantryAdmin</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "<style>
    .user-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .user-table th { text-align: left; padding: 12px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--border); }
    .user-table td { padding: 14px 12px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--ink); }
    .user-table tr:hover td { background: rgba(255, 255, 255, 0.02); }
    .action-btns { display: flex; gap: 8px; }
    .btn-sm { padding: 6px 10px; font-size: 12px; text-decoration: none; }
</style>";
echo "</head>";

echo "<body>";
echo "<div class='layout'>";

// --- SIDEBAR ---
echo "<aside class='sidebar'>";
echo "<a class='logo' href='admin.php'>PantryAdmin</a>";
echo "<a class='nav' href='admin.php'>Dashboard</a>";
echo "<a class='nav active' href='manage_users.php'>User Manager</a>";
echo "<a class='nav secondary' href='http://siegsaw.mockus.lt/web/index.php' target='_blank'>Main Website ↗</a>";
echo "<a class='nav' href='add_recipe.php'>Add Recipe</a>";
echo "<a class='nav' href='add_nutrition.php'>Nutrition Mapping</a>";
echo "<a class='nav secondary' href='logout.php'>Log out</a>";
echo "</aside>";

// --- MAIN CONTENT ---
echo "<main class='main'>";

echo "<div class='page-title'>User Manager</div>";
echo "<div class='page-sub'>View and manage system administrators and users</div>";

if ($error) {
    echo "<div class='preview-errors'><h4>System Error</h4><ul><li>$error</li></ul></div>";
}

echo "<div class='card'>";
echo "<table class='user-table'>";
echo "<thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
      </thead>";
echo "<tbody>";

if (!empty($users)) {
    foreach ($users as $user) {
        $id = htmlspecialchars($user['user_id']);
        $uname = htmlspecialchars($user['username']);
        $email = htmlspecialchars($user['email']);
        $date = date('Y-m-d', strtotime($user['created_at']));

        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td><strong>$uname</strong></td>";
        echo "<td>$email</td>";
        echo "<td>$date</td>";
        echo "<td class='action-btns'>";
        echo "<a href='edit_user.php?id=$id' class='btn btn-sm'>Edit</a>";
        echo "</td>";
        echo "</tr>";
    }
} else if (!$error) {
    echo "<tr><td colspan='5' style='text-align:center;'>No users found in database.</td></tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

echo "</main>";
echo "</div>";

echo "</body>";
echo "</html>";
