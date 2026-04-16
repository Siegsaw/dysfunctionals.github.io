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

echo "<header>";
echo "<div class='logo'>PantryChef Admin</div>";
echo "<nav class='h-nav'>";
echo "<button class='nav-btn active'>Dashboard</button>";
echo "</nav>";
echo "<div class='h-right'>";
echo "<button class='btn-theme' onclick='toggleTheme()'>🌙</button>";
echo "</div>";
echo "</header>";

echo "<div class='admin-page'>";
echo "<div class='admin-title'>Admin Panel</div>";
echo "<div class='admin-sub'>Manage recipes and ingredients</div>";

echo "<div class='admin-grid'>";

echo "<a href='add_recipe.php' class='admin-card'>";
echo "<div class='admin-card-ico'>➕</div>";
echo "<div class='admin-card-title'>Add Recipe</div>";
echo "<div class='admin-card-desc'>Create new recipes with steps and ingredients</div>";
echo "</a>";

echo "<div class='admin-card disabled'>";
echo "<div class='admin-card-ico'>📖</div>";
echo "<div class='admin-card-title'>Manage Recipes</div>";
echo "<div class='admin-card-desc'>Edit or delete recipes</div>";
echo "</div>";

echo "<div class='admin-card disabled'>";
echo "<div class='admin-card-ico'>🥕</div>";
echo "<div class='admin-card-title'>Ingredients</div>";
echo "<div class='admin-card-desc'>Manage ingredient list</div>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<script src='../web/shared.js'></script>";
echo "</body>";
echo "</html>";
?>
