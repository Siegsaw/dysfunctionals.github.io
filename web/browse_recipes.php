<?php
echo "<!DOCTYPE html>";
echo "<html lang=\"en\">";
echo "<head>";
echo "  <meta charset=\"UTF-8\">";
echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "  <title>PantryChef — Recipe Browser</title>";
echo "  <link rel=\"stylesheet\" href=\"shared.css\">";
echo "  <link rel=\"stylesheet\" href=\"index.css\">";
echo "  <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>";
echo "</head>";
echo "<body>";

echo "<header>";
echo "  <div class=\"logo\" onclick=\"location.href='index.php'\">PantryChef</div>";
echo "  <nav class=\"h-nav\">";
echo "    <button class=\"nav-btn\" onclick=\"location.href='index.php'\">Home</button>";
echo "    <button class=\"nav-btn active\">Browse Recipes</button>";
echo "    <button class=\"nav-btn\" onclick=\"location.href='inventory.php'\">User Ingredients</button>";
echo "  </nav>";
echo "  <div class=\"h-right\">";
echo "    <a id=\"userBadge\" href=\"profile.php\"></a>";
echo "    <button class=\"btn-theme\" onclick=\"toggleTheme()\" title=\"Toggle dark mode\">🌙</button>";
echo "    <button class=\"btn-signin\" id=\"btnSignIn\" onclick=\"location.href='login.php'\">Sign In</button>";
echo "    <button class=\"btn-logout\" id=\"btnLogout\" onclick=\"doLogout()\">Sign Out</button>";
echo "  </div>";
echo "</header>";

echo "<div class=\"toast\" id=\"toast\"></div>";

echo "<div class=\"page\">";
echo "  <div class=\"page-title\">Recipe Browser</div>";
echo "  <div class=\"page-sub\">Browse all recipes in the database for inspiration, even without adding ingredients.</div>";

echo "  <div class=\"results-section\" id=\"resultsSection\" style=\"display:block\">";
echo "    <div class=\"results-label\" id=\"resultsLabel\">All recipes</div>";
echo "    <div class=\"results-grid\" id=\"resultsGrid\"></div>";
echo "  </div>";

echo "  <div class=\"results-empty\" id=\"resultsEmpty\" style=\"display:none\">";
echo "    <span class=\"results-empty-ico\">🍳</span>";
echo "    <p>No recipes found.</p>";
echo "  </div>";
echo "</div>";

echo "<script src=\"shared.js\"></script>";
?>

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
  return Number.isInteger(num) ? String(num) : num.toFixed(1);
}

function makeFlavorTags(flavors) {
  if (!Array.isArray(flavors) || flavors.length === 0) return '';
  return `
    <div class="tags">
      ${flavors.map(flavor => `<span class="tag tag-flavor">${escapeHtml(flavor)}</span>`).join('')}
    </div>
  `;
}

function makeIngredientRows(ingredients) {
  if (!Array.isArray(ingredients) || ingredients.length === 0) {
    return `<div class="detail-row"><span class="detail-name">No ingredients listed</span></div>`;
  }

  return ingredients.map(ing => `
    <div class="detail-row">
      <span class="detail-name">${escapeHtml(ing.name)}</span>
      <div class="detail-right">
        <span class="detail-qty">${formatNumber(ing.amount)} ${escapeHtml(ing.unit)}</span>
      </div>
    </div>
  `).join('');
}

function recipeCard(recipe, index) {
  const region = recipe.region_name
    ? `<div class="cuisine-row"><span class="tag-cuisine">${escapeHtml(recipe.region_name)}</span></div>`
    : '';

  const detailId = `recipeDetail${index}`;
  const toggleId = `recipeToggle${index}`;

  return `
    <article class="recipe-card">
      <div class="card-top">
        <div class="card-name-wrapper">
          ${region}
          <div class="card-name">${escapeHtml(recipe.name)}</div>
        </div>
        <div class="card-pct pct-high">${Array.isArray(recipe.ingredients) ? recipe.ingredients.length : 0} ingredients</div>
      </div>

      <div class="card-info">
        <div class="recipe-calories">🔥 ${formatNumber(recipe.calories)} kcal</div>
        <div class="recipe-time">⏱️ ${formatNumber(recipe.time)} min</div>
      </div>

      ${makeFlavorTags(recipe.flavors)}

      <button class="card-toggle" id="${toggleId}" onclick="toggleRecipeDetail('${detailId}', '${toggleId}')">
        <span>Show needed ingredients</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>

      <div class="card-detail" id="${detailId}">
        <div class="time-breakdown">
          <div>Prep: ${formatNumber(recipe.prep_time)} min</div>
          <div>Cook: ${formatNumber(recipe.cook_time)} min</div>
        </div>

        <div class="card-sec-lbl">Needed ingredients</div>
        ${makeIngredientRows(recipe.ingredients)}

        <div class="card-sec-lbl green" style="margin-top:12px;">Nutrition</div>
        <div class="detail-row">
          <span class="detail-name">Protein</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(recipe.protein)} g</span></div>
        </div>
        <div class="detail-row">
          <span class="detail-name">Carbs</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(recipe.carbs)} g</span></div>
        </div>
        <div class="detail-row">
          <span class="detail-name">Fat</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(recipe.fat)} g</span></div>
        </div>
      </div>

      <a class="btn-view-recipe" href="recipe.php?id=${encodeURIComponent(recipe.id)}">View recipe</a>
    </article>
  `;
}

function toggleRecipeDetail(detailId, toggleId) {
  const detail = document.getElementById(detailId);
  const toggle = document.getElementById(toggleId);
  if (!detail || !toggle) return;

  const isOpen = detail.classList.toggle('open');
  toggle.innerHTML = isOpen
    ? `
      <span>Hide needed ingredients</span>
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="transform: rotate(180deg);">
        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    `
    : `
      <span>Show needed ingredients</span>
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    `;
}

async function loadAllRecipes() {
  const grid = document.getElementById('resultsGrid');
  const empty = document.getElementById('resultsEmpty');
  const label = document.getElementById('resultsLabel');

  try {
    grid.innerHTML = '<div class="recipe-loading">Loading recipes...</div>';

    const response = await fetch('get_recipes.php', { cache: 'no-store' });
    const recipes = await response.json();

    if (!Array.isArray(recipes) || recipes.length === 0) {
      grid.innerHTML = '';
      empty.style.display = 'block';
      label.textContent = 'All recipes';
      return;
    }

    label.textContent = `All recipes (${recipes.length})`;
    empty.style.display = 'none';
    grid.innerHTML = recipes.map((recipe, index) => recipeCard(recipe, index)).join('');
  } catch (error) {
    console.error(error);
    label.textContent = 'All recipes';
    grid.innerHTML = `
      <div class="results-empty">
        <span class="results-empty-ico">⚠️</span>
        <p>Failed to load recipes.</p>
      </div>
    `;
  }
}

loadAllRecipes();
</script>

</body>
</html>
