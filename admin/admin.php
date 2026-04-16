<?php
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>PantryChef Admin</title>";

echo "<link rel='stylesheet' href='../web/shared.css'>";
echo "<link rel='stylesheet' href='admin.css'>";
echo "<script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>";

echo "</head>";
echo "<body>";

echo "<div class='admin-wrap'>";

echo "<div class='admin-header'>";
echo "<h1>PantryChef Admin</h1>";
echo "<p>Backend control panel</p>";
echo "</div>";

echo "<div class='admin-grid'>";

echo "<a class='admin-card' href='recipe_add.php'>";
echo "<h2>➕ Add Recipe</h2>";
echo "<p>Create recipes with ingredients and steps</p>";
echo "</a>";

echo "<div class='admin-card disabled'>";
echo "<h2>✏️ Edit Recipes</h2>";
echo "<p>Coming soon</p>";
echo "</div>";

echo "<div class='admin-card disabled'>";
echo "<h2>🥕 Ingredients</h2>";
echo "<p>Coming soon</p>";
echo "</div>";

echo "</div>";

echo "</div>";

echo "</body>";
echo "</html>";
?>
