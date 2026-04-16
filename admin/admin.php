<?php
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Admin Dashboard</title>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "</head>";

echo "<body>";

echo "<div class='layout'>";

/* SIDEBAR */
echo "<aside class='sidebar'>";
echo "<a class='logo' href='admin.php'>PantryAdmin</a>";

echo "<a class='nav active' href='admin.php'>Dashboard</a>";
echo "<a class='nav' href='add_recipe.php'>Add Recipe</a>";

echo "</aside>";

/* MAIN */
echo "<main class='main'>";

echo "<div class='page-title'>Dashboard</div>";
echo "<div class='page-sub'>Manage recipes, ingredients and structure</div>";

echo "<div class='grid'>";

echo "<div class='card'>";
echo "<div class='card-title'>Recipes</div>";
echo "<div class='card-sub'>Create and manage recipes</div>";
echo "</div>";

echo "<div class='card'>";
echo "<div class='card-title'>Ingredients</div>";
echo "<div class='card-sub'>Controlled from DB</div>";
echo "</div>";

echo "<div class='card'>";
echo "<div class='card-title'>Nutrition</div>";
echo "<div class='card-sub'>Ingredient nutrition mapping</div>";
echo "</div>";

echo "</div>";

echo "</main>";

echo "</div>";

echo "</body>";
echo "</html>";
?>
