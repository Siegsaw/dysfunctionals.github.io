<?php
require_once __DIR__ . '/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');
require '/var/www/private/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name_ing'] ?? '');
    $defaultUnit = trim($_POST['default_unit'] ?? '');
    $densityRaw = trim($_POST['density_g_per_ml'] ?? '');

    if ($name === '') {
        $error = 'Ingredient name is required.';
    } elseif (!in_array($defaultUnit, ['g', 'ml', 'pcs'], true)) {
        $error = 'Default unit must be g, ml, or pcs.';
    } else {
        $density = null;

        if ($densityRaw !== '') {
            $density = (float)$densityRaw;

            if ($density <= 0) {
                $error = 'Density must be greater than 0.';
            }
        }

        if ($error === '') {
            if ($density === null) {
                $stmt = $conn->prepare("
                    INSERT INTO ingredients (name_ing, default_unit, density_g_per_ml)
                    VALUES (?, ?, NULL)
                ");
                $stmt->bind_param("ss", $name, $defaultUnit);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO ingredients (name_ing, default_unit, density_g_per_ml)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("ssd", $name, $defaultUnit, $density);
            }

            try {
                $stmt->execute();
                header("Location: ingredients.php?msg=Ingredient+added");
                exit;
            } catch (Throwable $e) {
                $error = 'Could not add ingredient: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

$result = $conn->query("
    SELECT ingredient_id, name_ing, default_unit, density_g_per_ml
    FROM ingredients
    ORDER BY name_ing ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ingredients - PantryAdmin</title>
<link rel="stylesheet" href="admin.css">
</head>

<body>
<div class="layout">

<aside class="sidebar">
    <a class="logo" href="admin.php">PantryAdmin</a>
    <a class="nav" href="admin.php">Dashboard</a>
    <a class="nav secondary" href="../web/index.php" target="_blank">Main Website ↗</a>
    <a class="nav" href="manage_users.php">User Manager</a>
    <a class="nav" href="add_recipe.php">Add Recipe</a>
    <a class="nav" href="modify_recipe.php">Modify Recipes</a>
    <a class="nav active" href="ingredients.php">Ingredients</a>
    <a class="nav" href="add_nutrition.php">Nutrition Mapping</a>
    <a class="nav secondary" href="logout.php">Log out</a>
</aside>

<main class="main">

<div class="page-title">Ingredients</div>
<div class="page-sub">View and add ingredients used by recipes</div>

<?php if ($message): ?>
    <div class="preview-ok">✓ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="preview-errors">
        <h4>Error</h4>
        <ul><li><?= htmlspecialchars($error) ?></li></ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="section">Add New Ingredient</div>

    <form method="POST" action="ingredients.php">
        <input 
            class="input" 
            name="name_ing" 
            placeholder="Ingredient name"
            required
        >

        <select class="input" name="default_unit" required>
            <option value="">-- Default unit --</option>
            <option value="g">g</option>
            <option value="ml">ml</option>
            <option value="pcs">pcs</option>
        </select>

        <input 
            class="input" 
            name="density_g_per_ml" 
            type="number"
            step="0.0001"
            min="0"
            placeholder="Density g/ml, only needed for ml ingredients"
        >

        <button class="btn primary" type="submit">
            Add Ingredient
        </button>
    </form>
</div>

<div class="card" style="margin-top:18px;">
    <div class="section">Ingredient List</div>

    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ingredient</th>
                <th>Default Unit</th>
                <th>Density g/ml</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($ing = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($ing['ingredient_id']) ?></td>
                    <td><strong><?= htmlspecialchars($ing['name_ing']) ?></strong></td>
                    <td><?= htmlspecialchars($ing['default_unit']) ?></td>
                    <td>
                        <?= $ing['density_g_per_ml'] === null 
                            ? '<span style="color:var(--muted);">—</span>' 
                            : htmlspecialchars($ing['density_g_per_ml']) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align:center;">No ingredients found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
