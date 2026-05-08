<?php
require_once __DIR__ . '/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');
require '/var/www/private/db.php'; 

$ingResult = $conn->query("SELECT ingredient_id, name_ing FROM ingredients ORDER BY name_ing ASC");

$nutrResult = $conn->query("SELECT nutrient_id, name_nutr, unit FROM nutrients ORDER BY nutrient_id ASC");
$nutrients = [];

while ($row = $nutrResult->fetch_assoc()) {
    $nutrients[] = $row;
}

function isRequiredMacroPHP($name) {
    $name = strtolower(trim($name));
    return in_array($name, ['calories','calorie','kcal','fat','carbs','carbohydrates','protein']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nutrition Mapping</title>
<link rel="stylesheet" href="admin.css">
</head>

<body>

<div class="layout">

<aside class="sidebar">
    <a class="logo" href="admin.php">PantryAdmin</a>
    <a class="nav" href="admin.php">Dashboard</a>
    <a class="nav secondary" href="/web/index.php" target="_blank">Main Website ↗</a>
    <a class='nav' href='manage_users.php'>User Manager</a>
    <a class="nav" href="add_recipe.php">Add Recipe</a>
    <a class='nav' href="modify_recipe.php">Modify Recipes</a>
    <a class="nav active" href="add_nutrition.php">Nutrition Mapping</a>
    <a class="nav secondary" href="logout.php">Log out</a>
</aside>

<main class="main">
<div class="page-title">Nutrition Mapping</div>
<div class="page-sub">Assign values per 100g or per piece</div>

<div class="card">

<div class="section">1. Select Ingredient</div>
<select id="ingredient_id" class="input" onchange="loadNutrition()">
<option value="">-- Choose an ingredient --</option>
<?php while ($ing = $ingResult->fetch_assoc()): ?>
    <option value="<?= $ing['ingredient_id'] ?>"><?= $ing['name_ing'] ?></option>
<?php endwhile; ?>
</select>

<div class="section">Grams per piece</div>
<input type="number" id="grams_per_unit" class="input" placeholder="e.g. 57">

<div class="section">2. Nutrient Values</div>
<div class="preview-list">

<?php foreach ($nutrients as $nutr): 
    $nutrientName = strtolower(trim($nutr['name_nutr']));
    $isRequired = isRequiredMacroPHP($nutrientName);
?>

<div class="preview-row" style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px; align-items:center;">
    
    <div>
        <?= $nutr['name_nutr'] ?> (<?= $nutr['unit'] ?>)
        <?php if ($isRequired): ?>
            <span style="color:red">*</span>
        <?php endif; ?>
    </div>

    <input type="number" step="0.01" min="0"
           class="input nutrient-input-g100"
           data-nutrient-id="<?= $nutr['nutrient_id'] ?>"
           data-nutrient-name="<?= htmlspecialchars($nutrientName) ?>"
           placeholder="100g">

    <input type="number" step="0.01" min="0"
           class="input nutrient-input-pcs"
           data-nutrient-id="<?= $nutr['nutrient_id'] ?>"
           placeholder="pcs">

</div>

<?php endforeach; ?>

</div>

<button class="btn primary" onclick="saveNutrition()" style="width:100%; margin-top:20px;">
Save All Nutrition Data
</button>

</div>
</main>
</div>

<script>

function isRequiredMacro(name) {
    name = String(name || '').toLowerCase().trim();
    return ['calories','calorie','kcal','fat','carbs','carbohydrates','protein'].includes(name);
}

function loadNutrition() {
    const ingId = document.getElementById('ingredient_id').value;

    document.querySelectorAll('.nutrient-input-g100, .nutrient-input-pcs')
        .forEach(i => i.value = "");

    if (!ingId) return;

    fetch('get_nutrition_mapping.php?ingredient_id=' + ingId)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Object.keys(res.nutrition).forEach(id => {
                const g = document.querySelector(`.nutrient-input-g100[data-nutrient-id="${id}"]`);
                const p = document.querySelector(`.nutrient-input-pcs[data-nutrient-id="${id}"]`);

                if (g) g.value = res.nutrition[id].g100;
                if (p) p.value = res.nutrition[id].pcs;
            });
        }
    });
}

function saveNutrition() {
    const ingId = document.getElementById('ingredient_id').value;
    if (!ingId) {
        alert("Select ingredient!");
        return;
    }

    let data = [];
    let missing = [];

    document.querySelectorAll('.nutrient-input-g100').forEach(input => {

        const id = input.dataset.nutrientId;
        const name = input.dataset.nutrientName;

        const valG = input.value.trim();
        const valP = document.querySelector(`.nutrient-input-pcs[data-nutrient-id="${id}"]`).value.trim();

        if (isRequiredMacro(name)) {
            if (
                (valG === "" || Number(valG) <= 0) &&
                (valP === "" || Number(valP) <= 0)
            ) {
                missing.push(name);
            }
        }

        if (valG !== "" || valP !== "") {
            data.push({
                nutrient_id: id,
                amount_g100: valG || 0,
                amount_pcs: valP || 0
            });
        }
    });

    if (missing.length > 0) {
        alert("Required (>0): calories, fat, carbs, protein (either 100g or pcs)");
        return;
    }

    fetch('add_nutrition_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ingredient_id: ingId, nutrition: data })
    })
    .then(res => res.json())
    .then(res => alert(res.message));
}

document.querySelectorAll('.nutrient-input-g100').forEach(input => {

    input.addEventListener('input', () => {

        const grams = Number(document.getElementById('grams_per_unit').value);

        if (!grams || grams <= 0) return;

        const id = input.dataset.nutrientId;
        const g100 = Number(input.value);

        const pcsInput = document.querySelector(`.nutrient-input-pcs[data-nutrient-id="${id}"]`);

        if (pcsInput && g100 > 0) {
            pcsInput.value = (g100 * grams / 100).toFixed(2);
        }
    });

});

</script>

</body>
</html>
