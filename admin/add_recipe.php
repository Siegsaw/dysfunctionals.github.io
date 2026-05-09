<?php
require_once __DIR__ . '/auth.php';
require_admin();
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Add Recipe</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";

echo "<body>";

echo "<div class='layout'>";

echo "<aside class='sidebar'>";
echo "<a class='logo' href='admin.php'>PantryAdmin</a>";
echo "<a class='nav' href='admin.php'>Dashboard</a>";
echo "<a class='nav secondary' href='../web/index.php' target='_blank'>Main Website ↗</a>";
echo "<a class='nav' href='manage_users.php'>User Manager</a>";
echo "<a class='nav active' href='add_recipe.php'>Add Recipe</a>";
echo "<a class='nav' href='modify_recipe.php'>Modify Recipes</a>";
echo "<a class='nav' href='ingredients.php'>Ingredients</a>";
echo "<a class='nav' href='add_nutrition.php'>Nutrition Mapping</a>";
echo "<a class='nav secondary' href='logout.php'>Log out</a>";
echo "</aside>";

require '/var/www/private/db.php';
$flavors_res = $conn->query("SELECT * FROM flavors ORDER BY name");
$regions_res = $conn->query("SELECT * FROM regions ORDER BY name");

$flavors_options = "";
while($f = $flavors_res->fetch_assoc()) {
    $flavors_options .= "<option value='{$f['flavor_id']}'>{$f['name']}</option>";
}

$regions_options = "";
while($r = $regions_res->fetch_assoc()) {
    $regions_options .= "<option value='{$r['region_id']}'>{$r['name']}</option>";
}

echo "<main class='main'>";

echo "<div class='page-title'>Create Recipe</div>";
echo "<div class='page-sub'>Insert structured recipe data into database</div>";

echo "<div class='card'>";

echo "<div class='section'>Basic Info</div>";
echo "<input id='title' class='input' placeholder='Recipe title'>";
echo "<textarea id='description' class='textarea' placeholder='Description'></textarea>";

echo "<div class='section'>Flavors & Regions</div>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;'>";
    echo "<div>";
        echo "<div style='font-size: 11px; color: var(--muted); margin-bottom: 6px;'>Flavors (Ctrl + Click)</div>";
        echo "<select id='flavors' class='input' multiple style='height: 140px; margin-bottom: 0;'>$flavors_options</select>";
    echo "</div>";
    echo "<div>";
        echo "<div style='font-size: 11px; color: var(--muted); margin-bottom: 6px;'>Regions (Ctrl + Click)</div>";
        echo "<select id='regions' class='input' multiple style='height: 140px; margin-bottom: 0;'>$regions_options</select>";
    echo "</div>";
echo "</div>";

echo "<div class='section'>Ingredients</div>";
echo "<div id='ingredients'></div>";
echo "<button class='btn' type='button' onclick='addIngredientRow()'>+ Add ingredient</button>";

echo "<div class='section'>Steps</div>";
echo "<div id='steps'></div>";
echo "<button class='btn' type='button' onclick='addStepRow()'>+ Add step</button>";

echo "<hr>";
echo "<div class='action-row'>";
echo "<button class='btn' type='button' onclick='previewRecipe()'>Preview Recipe</button>";
echo "<button class='btn primary' type='button' id='confirmSaveBtn' onclick='confirmSaveRecipe()' disabled>Confirm Save</button>";
echo "</div>";

echo "</div>";

echo "<div class='card preview-card' id='previewCard' style='display:none'>";
echo "<div class='section'>Verification Preview</div>";
echo "<div id='previewContent'></div>";
echo "</div>";

echo "</main>";
echo "</div>";

echo "<script src='add_recipe.js'></script>";
echo "</body>";
echo "</html>";
?>
