<?php
require_once __DIR__ . '/auth.php';
require_admin();
require '/var/www/private/db.php';

$recipes = $conn->query("
  SELECT recipe_id, title, total_time_minutes, calories
  FROM recipes
  ORDER BY recipe_id ASC
");

$flavors_res = $conn->query("SELECT * FROM flavors ORDER BY name");
$regions_res = $conn->query("SELECT * FROM regions ORDER BY name");

$flavors_options = "";
while ($f = $flavors_res->fetch_assoc()) {
  $flavors_options .= "<option value='{$f['flavor_id']}'>" . htmlspecialchars($f['name']) . "</option>";
}

$regions_options = "";
while ($r = $regions_res->fetch_assoc()) {
  $regions_options .= "<option value='{$r['region_id']}'>" . htmlspecialchars($r['name']) . "</option>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Modify Recipes</title>
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
  <a class="nav active" href="modify_recipe.php">Modify Recipes</a>
  <a class="nav" href="add_nutrition.php">Nutrition Mapping</a>
  <a class="nav secondary" href="logout.php">Log out</a>
</aside>

<main class="main">
  <div class="page-title">Modify Recipes</div>
  <div class="page-sub">Edit existing recipe data and update the database</div>

  <div class="card">
    <div class="section">Search Recipe</div>
    
    <input 
      id="recipe_search" 
      class="input" 
      placeholder="Type recipe name..."
      oninput="filterRecipeList()"
    >
    
    <select id="recipe_id" class="input" size="8" onchange="loadRecipeForEdit()">
      <option value="">-- Choose recipe --</option>
      <?php while ($r = $recipes->fetch_assoc()): ?>
        <option 
          value="<?= $r['recipe_id'] ?>"
          data-title="<?= htmlspecialchars(strtolower($r['title'])) ?>"
        >
          #<?= $r['recipe_id'] ?> — <?= htmlspecialchars($r['title']) ?>
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <div class="card" id="editCard" style="display:none; margin-top:18px;">
    <div class="section">Basic Info</div>
    <input id="title" class="input" placeholder="Recipe title">
    <textarea id="description" class="textarea" placeholder="Description"></textarea>

    <div class="section">Flavors & Regions</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
      <div>
        <div style="font-size:11px; color:var(--muted); margin-bottom:6px;">Flavors</div>
        <select id="flavors" class="input" multiple style="height:140px; margin-bottom:0;">
          <?= $flavors_options ?>
        </select>
      </div>

      <div>
        <div style="font-size:11px; color:var(--muted); margin-bottom:6px;">Regions</div>
        <select id="regions" class="input" multiple style="height:140px; margin-bottom:0;">
          <?= $regions_options ?>
        </select>
      </div>
    </div>

    <div class="section">Ingredients</div>
    <div id="ingredients"></div>
    <button class="btn" type="button" onclick="addIngredientRow()">+ Add ingredient</button>

    <div class="section">Steps</div>
    <div id="steps"></div>
    <button class="btn" type="button" onclick="addStepRow()">+ Add step</button>

    <hr>

    <div class="action-row">
      <button class="btn" type="button" onclick="previewModifiedRecipe()">Preview Changes</button>
      <button class="btn primary" type="button" id="confirmUpdateBtn" onclick="confirmUpdateRecipe()" disabled>
        Confirm Update
      </button>
    </div>
  </div>

  <div class="card preview-card" id="previewCard" style="display:none;">
    <div class="section">Verification Preview</div>
    <div id="previewContent"></div>
  </div>
</main>
</div>

<script src="modify_recipe.js"></script>
</body>
</html>
