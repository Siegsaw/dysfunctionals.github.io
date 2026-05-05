<?php
require_once __DIR__ . '/auth.php';
require_admin(); 
require '/var/www/private/db.php';

$userId = $_GET['id'] ?? null;

if (!$userId) {
    die("User ID not specified");
}

$user = null;
$stmt = mysqli_prepare($conn, "SELECT user_id, username, email, is_admin FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User not found");
}

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Edit User - PantryAdmin</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";

echo "<body>";
echo "<div class='layout'>";

// --- SIDEBAR ---
echo "<aside class='sidebar'>";
echo "<a class='logo' href='admin.php'>PantryAdmin</a>";
echo "<a class='nav' href='admin.php'>Dashboard</a>";
echo "<a class='nav active' href='manage_users.php'>User Manager</a>";
echo "<a class='nav secondary' href='logout.php'>Log out</a>";
echo "</aside>";

// --- MAIN CONTENT ---
echo "<main class='main'>";
echo "<div class='page-title'>Edit User: " . htmlspecialchars($user['username']) . "</div>";
echo "<div class='page-sub'>Update user credentials and permissions</div>";

echo "<div class='card' style='max-width: 600px;'>";
echo "<form action='user_handler.php' method='POST'>";
echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user['user_id']) . "'>";
echo "<input type='hidden' name='action' value='update'>";

echo "<div class='section'>Basic Information</div>";

echo "<label class='page-sub' style='display:block; margin-bottom:4px;'>Username</label>";
echo "<input type='text' name='username' class='input' value='" . htmlspecialchars($user['username']) . "' required>";

echo "<label class='page-sub' style='display:block; margin-bottom:4px;'>Email Address</label>";
echo "<input type='email' name='email' class='input' value='" . htmlspecialchars($user['email']) . "' required>";

echo "<div class='section'>Security</div>";
echo "<label class='page-sub' style='display:block; margin-bottom:4px;'>New Password (leave blank to keep current)</label>";
echo "<input type='password' name='password' class='input' placeholder='••••••••'>";

echo "<hr>";

echo "<div class='action-row'>";
echo "<button type='submit' class='btn primary'>Save Changes</button>";
echo "<a href='manage_users.php' class='btn'>Cancel</a>";
echo "</div>";

echo "</form>";
echo "</div>";

echo "<div class='card' style='max-width: 600px; margin-top: 20px; border-color: var(--red);'>";
echo "<div class='card-title' style='color: var(--red);'>Danger Zone</div>";
echo "<div class='card-sub'>Once you delete a user, there is no going back.</div>";
echo "<form action='user_handler.php' method='POST' onsubmit='return confirm(\"Are you sure you want to delete this user?\");'>";
echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user['user_id']) . "'>";
echo "<input type='hidden' name='action' value='delete'>";
echo "<button type='submit' class='btn row-remove' style='margin-top:10px;'>Delete User</button>";
echo "</form>";
echo "</div>";

echo "</main>";
echo "</div>";
echo "</body>";
echo "</html>";
