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
<style>
  .ingredient-search-wrapper {
    position: relative;
    margin-bottom: 20px;
  }

  .ingredient-search-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: var(--bg-s);
    color: var(--ink);
    font-size: 14px;
    outline: none;
    transition: all 0.15s ease;
  }

  .ingredient-search-input:focus {
    border-color: var(--green, #4a9d6f);
    background: var(--bg);
    box-shadow: 0 0 0 2px rgba(74, 157, 111, 0.1);
  }

  .ingredient-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .ingredient-suggestions.show {
    display: block;
  }

  .suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    transition: background 0.1s ease;
    font-size: 14px;
    color: var(--ink);
  }

  .suggestion-item:last-child {
    border-bottom: none;
  }

  .suggestion-item:hover {
    background: var(--bg-m);
  }

  .suggestion-item.selected {
    background: var(--green, #4a9d6f);
    color: white;
  }

  .ingredient-value {
    display: none;
    padding: 10px;
    background: var(--bg-s);
    border: 1px solid var(--border);
    border-radius: 4px;
    margin-top: 10px;
    color: var(--ink);
    font-size: 13px;
  }

  .ingredient-value.show {
    display: block;
  }
</style>
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
    <a class='nav' href="ingredients.php">Ingredients</a>
    <a class="nav active" href="add_nutrition.php">Nutrition Mapping</a>
    <a class="nav secondary" href="logout.php">Log out</a>
</aside>

<main class="main">
<div class="page-title">Nutrition Mapping</div>
<div class="page-sub">Assign values per 100g or per piece</div>

<div class="card">

<div class="section">1. Select Ingredient</div>
<div class="ingredient-search-wrapper">
  <input 
    type="text" 
    id="ingredient_search" 
    class="ingredient-search-input"
    placeholder="Search ingredient..."
    autocomplete="off"
  >
  <div id="ingredient_suggestions" class="ingredient-suggestions"></div>
  <div id="ingredient_value" class="ingredient-value"></div>
</div>
<input type="hidden" id="ingredient_id">

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

let ALL_INGREDIENTS = [];

// Load ingredients on page load
async function loadIngredients() {
  try {
    const res = await fetch('../web/get_ingredients.php', { cache: 'no-store' });
    ALL_INGREDIENTS = await res.json();
    // Show all ingredients on load
    displayIngredients(ALL_INGREDIENTS);
  } catch (err) {
    console.error('Failed to load ingredients:', err);
    ALL_INGREDIENTS = [];
  }
}

function displayIngredients(ingredientsList) {
  const suggestionsBox = document.getElementById('ingredient_suggestions');
  
  if (ingredientsList.length === 0) {
    suggestionsBox.classList.remove('show');
    return;
  }

  suggestionsBox.innerHTML = '';
  ingredientsList.forEach(ing => {
    const item = document.createElement('div');
    item.className = 'suggestion-item';
    item.textContent = ing.name;
    item.onclick = () => selectIngredient(ing);
    suggestionsBox.appendChild(item);
  });

  suggestionsBox.classList.add('show');
}

function filterIngredients() {
  const input = document.getElementById('ingredient_search');
  const val = input.value.trim().toLowerCase();

  // If input is empty, show all ingredients
  if (val.length === 0) {
    displayIngredients(ALL_INGREDIENTS);
    return;
  }

  // Otherwise, filter ingredients based on input
  const filtered = ALL_INGREDIENTS
    .filter(ing => ing.name.toLowerCase().includes(val));

  displayIngredients(filtered);
}

function selectIngredient(ingredient) {
  document.getElementById('ingredient_id').value = ingredient.id;
  document.getElementById('ingredient_search').value = ingredient.name;
  document.getElementById('ingredient_suggestions').classList.remove('show');
  
  // Show selected ingredient info
  const valueDiv = document.getElementById('ingredient_value');
  valueDiv.innerHTML = `✓ Selected: <strong>${ingredient.name}</strong>`;
  valueDiv.classList.add('show');
  
  loadNutrition();
}

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

// Event listeners for search
document.addEventListener('DOMContentLoaded', () => {
  loadIngredients();
  
  const searchInput = document.getElementById('ingredient_search');
  if (searchInput) {
    searchInput.addEventListener('input', filterIngredients);
    
    // Show full list when focusing on input
    searchInput.addEventListener('focus', () => {
      if (ALL_INGREDIENTS.length > 0) {
        displayIngredients(ALL_INGREDIENTS);
      }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.ingredient-search-wrapper')) {
        document.getElementById('ingredient_suggestions').classList.remove('show');
      }
    });
  }
});

</script>

</body>
</html>
