<?php
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Add Recipe</title>";

echo "<link rel='stylesheet' href='admin.css'>";
echo "<script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>";

echo "</head>";
echo "<body>";

echo "<div class='admin-wrap'>";

echo "<h1>Add Recipe</h1>";

echo "<div class='form-card'>";

echo "<div class='form-group'>";
echo "<input id='title' placeholder='Recipe title'>";
echo "</div>";

echo "<div class='form-group'>";
echo "<textarea id='description' placeholder='Description'></textarea>";
echo "</div>";

echo "<hr>";

echo "<h3>Ingredients</h3>";

echo "<div class='row'>";
echo "<input id='ingName' placeholder='Ingredient'>";
echo "<input id='ingQty' type='number' placeholder='Qty' step='any'>";
echo "<select id='ingUnit'></select>";
echo "<button onclick='addIngredient()'>+</button>";
echo "</div>";

echo "<div id='ingredientList'></div>";

echo "<hr>";

echo "<h3>Steps</h3>";

echo "<div class='row'>";
echo "<input id='stepTime' type='number' placeholder='Time (min)' step='1'>";
echo "<select id='stepType'>";
echo "<option value='prep'>prep</option>";
echo "<option value='cook'>cook</option>";
echo "</select>";
echo "<input id='stepText' placeholder='Instructions'>";
echo "<button onclick='addStep()'>+</button>";
echo "</div>";

echo "<div id='stepList'></div>";

echo "<hr>";

echo "<button class='submit' onclick='submitRecipe()'>Save Recipe</button>";

echo "</div>";

echo "</div>";

echo "<script src='add_recipe.js'></script>";

echo "</body>";
echo "</html>";
?>
