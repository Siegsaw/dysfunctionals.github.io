<?php
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Add Recipe</title>";
echo "<link rel='stylesheet' href='../web/shared.css'>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";
echo "<body>";

echo "<header>";
echo "<div class='logo' onclick=\"location.href='admin.php'\">Admin</div>";
echo "</header>";

echo "<div class='admin-page'>";

echo "<div class='admin-title'>Add Recipe</div>";

echo "<div class='form-card'>";

echo "<input id='title' class='admin-input' placeholder='Recipe title'>";
echo "<textarea id='desc' class='admin-textarea' placeholder='Description'></textarea>";

echo "<div class='section-label'>Ingredients</div>";
echo "<div id='ingredients'></div>";
echo "<button onclick='addIngredient()' class='btn-add'>+ Add Ingredient</button>";

echo "<div class='section-label'>Steps</div>";
echo "<div id='steps'></div>";
echo "<button onclick='addStep()' class='btn-add'>+ Add Step</button>";

echo "<button class='btn-save' onclick='saveRecipe()'>Save Recipe</button>";

echo "</div>";
echo "</div>";

echo "<script src='admin.js'></script>";
echo "</body>";
echo "</html>";
?>
