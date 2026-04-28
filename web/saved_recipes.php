<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PantryChef — Saved Recipes</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="index.css">
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body>

<header>
  <div class="logo" onclick="location.href='index.php'">PantryChef</div>

  <nav class="h-nav">
    <button class="nav-btn" onclick="location.href='index.php'">Home</button>
    <button class="nav-btn" onclick="location.href='browse_recipes.php'">Browse Recipes</button>
    <button class="nav-btn" onclick="location.href='inventory.php'">User Ingredients</button>
  </nav>

  <div class="h-right">
    <span id="userBadge"></span>

    <div class="profile-menu-wrap" id="profileMenuWrap">
      <button class="profile-menu-btn" id="profileMenuBtn" type="button" onclick="toggleProfileMenu(event)" title="Profile menu">▾</button>

      <div class="profile-dropdown" id="profileDropdown">
        <button type="button" onclick="location.href='profile.php'">Profile</button>
        <button type="button" onclick="location.href='saved_recipes.php'">Saved recipes</button>
        <button type="button" onclick="doLogout()">Logout</button>
      </div>
    </div>

    <button class="btn-theme" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
    <button class="btn-signin" id="btnSignIn" onclick="location.href='login.php'">Sign In</button>
  </div>
</header>

<div class="toast" id="toast"></div>

<div class="page">
  <div class="page-title">Saved Recipes</div>
  <div class="page-sub">Recipes you saved for later.</div>

  <div class="results-section" style="display:block">
    <div class="results-head">
      <div class="results-label" id="savedRecipesLabel">Saved recipes</div>
    </div>

    <div class="results-grid" id="savedRecipesGrid"></div>
  </div>

  <div class="results-empty" id="savedRecipesEmpty" style="display:none">
    <span class="results-empty-ico">📌</span>
    <p>You have no saved recipes yet.</p>
  </div>
</div>

<script src="shared.js"></script>

<script>
function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formatNumber(value) {
  const num = Number(value ?? 0);
  if (Number.isInteger(num)) return String(num);
  return String(Math.round(num * 100) / 100);
}

async function loadSavedRecipes() {
  const grid = document.getElementById('savedRecipesGrid');
  const empty = document.getElementById('savedRecipesEmpty');
  const label = document.getElementById('savedRecipesLabel');

  try {
    grid.innerHTML = '<div class="recipe-loading">Loading saved recipes...</div>';

    const response = await fetch('get_saved_recipes.php', { cache: 'no-store' });
    const recipes = await response.json();

    grid.innerHTML = '';

    if (!Array.isArray(recipes) || recipes.length === 0) {
      empty.style.display = 'block';
      label.textContent = 'Saved recipes';
      return;
    }

    empty.style.display = 'none';
    label.textContent = `Saved recipes (${recipes.length})`;

    grid.innerHTML = recipes.map(recipe => `
      <article class="recipe-card saved-recipe-card">
        <div class="card-top">
          <div class="card-name-wrapper">
            <div class="saved-indicator">★ Saved</div>
            <div class="card-name">${escapeHtml(recipe.name)}</div>
          </div>
        </div>

        <div class="card-info">
          <div class="recipe-calories">🔥 ${formatNumber(recipe.calories)} kcal</div>
          <div class="recipe-time">⏱️ ${formatNumber(recipe.time)} min</div>
        </div>

        <a class="btn-view-recipe" href="recipe.php?id=${encodeURIComponent(recipe.id)}">
          Detailed recipe
        </a>
      </article>
    `).join('');

  } catch (error) {
    console.error(error);
    grid.innerHTML = `
      <div class="results-empty">
        <span class="results-empty-ico">⚠️</span>
        <p>Failed to load saved recipes.</p>
      </div>
    `;
  }
}

document.addEventListener('DOMContentLoaded', loadSavedRecipes);
</script>

</body>
</html>
