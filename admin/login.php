<?php
require_once __DIR__ . '/auth.php';
require_once '/var/www/private/db.php';

$error = '';

if (admin_is_logged_in()) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("
        SELECT user_id, username, password_hash, is_admin
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (
        $user &&
        password_verify($password, $user['password_hash']) &&
        (int)$user['is_admin'] === 1
    ) {
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['user_id'];
        $_SESSION['admin_username'] = $user['username'];

        header('Location: admin.php');
        exit;
    }

    $error = 'Invalid username, password, or admin access.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <main class="login-page">
    <section class="login-card">
      <a class="login-logo" href="login.php">PantryAdmin</a>
      <h1>Admin Login</h1>
      <p>Sign in to manage recipes and nutrition data.</p>

      <?php if ($error !== ''): ?>
        <div class="login-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="login.php" class="login-form">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" autocomplete="username" required autofocus>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="btn primary login-btn" type="submit">Log in</button>
      </form>
    </section>
  </main>
</body>
</html>
