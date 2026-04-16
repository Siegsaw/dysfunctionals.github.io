<?php
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
echo "<a class='nav active' href='add_recipe.php'>Add Recipe</a>";
echo "</aside>";

echo "<main class='main'>";

echo "<div class='page-title'>Create Recipe</div>";
echo "<div class='page-sub'>Insert structured recipe data into database</div>";

echo "<div class='card'>";

echo "<div class='section'>Basic Info</div>";
echo "<input id='title' class='input' placeholder='Recipe title'>";
echo "<textarea id='description' class='textarea' placeholder='Description'></textarea>";

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
