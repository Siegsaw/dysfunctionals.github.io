<?php
require_once __DIR__ . '/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');
require '/var/www/private/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    $ingredientId = (int)($_POST['ingredient_id'] ?? 0);
    $name = trim($_POST['name_ing'] ?? '');
    $defaultUnit = trim($_POST['default_unit'] ?? '');
    $densityRaw = trim($_POST['density_g_per_ml'] ?? '');

    if ($action === 'delete') {
        if ($ingredientId <= 0) {
            $error = 'Invalid ingredient ID.';
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM ingredients WHERE ingredient_id = ?");
                $stmt->bind_param("i", $ingredientId);
                $stmt->execute();

                header("Location: ingredients.php?msg=Ingredient+deleted");
                exit;
            } catch (Throwable $e) {
                $error = 'Could not delete ingredient. It may already be used in recipes or nutrition mappings.';
            }
        }
    } else {
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
                try {
                    if ($action === 'edit') {
                        if ($ingredientId <= 0) {
                            throw new Exception('Invalid ingredient ID.');
                        }

                        if ($density === null) {
                            $stmt = $conn->prepare("
                                UPDATE ingredients
                                SET name_ing = ?, default_unit = ?, density_g_per_ml = NULL
                                WHERE ingredient_id = ?
                            ");
                            $stmt->bind_param("ssi", $name, $defaultUnit, $ingredientId);
                        } else {
                            $stmt = $conn->prepare("
                                UPDATE ingredients
                                SET name_ing = ?, default_unit = ?, density_g_per_ml = ?
                                WHERE ingredient_id = ?
                            ");
                            $stmt->bind_param("ssdi", $name, $defaultUnit, $density, $ingredientId);
                        }

                        $stmt->execute();

                        header("Location: ingredients.php?msg=Ingredient+updated");
                        exit;
                    }

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

                    $stmt->execute();

                    header("Location: ingredients.php?msg=Ingredient+added");
                    exit;

                } catch (Throwable $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
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


<script>
function filterIngredients() {
    const search = document.getElementById('ingredientSearch').value.trim().toLowerCase();
    const rows = document.querySelectorAll('#ingredientTableBody tr[data-name]');

    rows.forEach(row => {
        const name = row.dataset.name || '';
        row.style.display = name.includes(search) ? '' : 'none';
    });
}

function sortIngredients() {
    const tbody = document.getElementById('ingredientTableBody');
    const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
    const sortValue = document.getElementById('ingredientSort').value;

    rows.sort((a, b) => {
        if (sortValue === 'name-asc') {
            return a.dataset.name.localeCompare(b.dataset.name);
        }

        if (sortValue === 'name-desc') {
            return b.dataset.name.localeCompare(a.dataset.name);
        }

        if (sortValue === 'id-asc') {
            return Number(a.dataset.id) - Number(b.dataset.id);
        }

        if (sortValue === 'id-desc') {
            return Number(b.dataset.id) - Number(a.dataset.id);
        }

        if (sortValue === 'unit-asc') {
            return a.dataset.unit.localeCompare(b.dataset.unit);
        }

        return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
    filterIngredients();
}
</script>
    
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

<div class="section">Search & Sort</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:10px; margin-bottom:14px;">
    <input 
        id="ingredientSearch" 
        class="input" 
        placeholder="Search ingredient name..."
        oninput="filterIngredients()"
        style="margin-bottom:0;"
    >

    <select 
        id="ingredientSort" 
        class="input" 
        onchange="sortIngredients()"
        style="margin-bottom:0;"
    >
        <option value="name-asc">Name A → Z</option>
        <option value="name-desc">Name Z → A</option>
        <option value="id-asc">ID ascending</option>
        <option value="id-desc">ID descending</option>
        <option value="unit-asc">Unit A → Z</option>
    </select>
</div>
    
<div class="card" style="margin-top:18px;">
    <div class="section">Ingredient List</div>

    <table class="user-table">
        <thead>
            <tr>
                
  data-id="<?= htmlspecialchars($ing['ingredient_id']) ?>"
  data-name="<?= htmlspecialchars(strtolower($ing['name_ing'])) ?>"
  data-unit="<?= htmlspecialchars(strtolower($ing['default_unit'])) ?>"
                <th>Density g/ml</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="ingredientTableBody">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($ing = $result->fetch_assoc()): ?>
                <tr>
                    <form method="POST" action="ingredients.php">
                        <td>
                            <?= htmlspecialchars($ing['ingredient_id']) ?>
                            <input type="hidden" name="ingredient_id" value="<?= htmlspecialchars($ing['ingredient_id']) ?>">
                        </td>
        
                        <td>
                            <input 
                                class="input"
                                name="name_ing"
                                value="<?= htmlspecialchars($ing['name_ing']) ?>"
                                required
                                style="margin-bottom:0;"
                            >
                        </td>
        
                        <td>
                            <select class="input" name="default_unit" required style="margin-bottom:0;">
                                <option value="g" <?= $ing['default_unit'] === 'g' ? 'selected' : '' ?>>g</option>
                                <option value="ml" <?= $ing['default_unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                <option value="pcs" <?= $ing['default_unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                            </select>
                        </td>
        
                        <td>
                            <input 
                                class="input"
                                name="density_g_per_ml"
                                type="number"
                                step="0.0001"
                                min="0"
                                value="<?= htmlspecialchars($ing['density_g_per_ml'] ?? '') ?>"
                                placeholder="—"
                                style="margin-bottom:0;"
                            >
                        </td>
        
                        <td class="action-btns">
                            <button class="btn btn-sm primary" name="action" value="edit" type="submit">
                                Save
                            </button>
        
                            <button 
                                class="btn btn-sm row-remove"
                                name="action"
                                value="delete"
                                type="submit"
                                onclick="return confirm('Delete this ingredient? This may fail if it is used by recipes.');"
                            >
                                Delete
                            </button>
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center;">No ingredients found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
