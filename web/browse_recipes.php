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
echo "    <div class=\"results-head\">";
echo "      <div class=\"results-label\" id=\"resultsLabel\">All recipes</div>";
echo "      <div class=\"view-toggle\">";
echo "        <button id=\"cardViewBtn\" class=\"view-btn active\" onclick=\"setRecipeView('card')\">Cards</button>";
echo "        <button id=\"listViewBtn\" class=\"view-btn\" onclick=\"setRecipeView('list')\">List</button>";
echo "      </div>";
echo "    </div>";
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
  if (Number.isInteger(num)) return String(num);
  return String(Math.round(num * 100) / 100);
}

function makeFlavorTags(flavors) {
  if (!Array.isArray(flavors) || flavors.length === 0) return '';
  return `
    <div class="tags">
      ${flavors.map(flavor => `<span class="tag tag-flavor">${escapeHtml(flavor)}</span>`).join('')}
    </div>
  `;
}

let BROWSE_RECIPES = [];
const browseServings = {};

function getBrowseServings(recipe) {
  const original = Number(recipe.servings) || 1;

  if (!browseServings[recipe.id]) {
    browseServings[recipe.id] = original;
  }

  return browseServings[recipe.id];
}

function scaleBrowseAmount(amount, recipe) {
  const original = Number(recipe.servings) || 1;
  const current = getBrowseServings(recipe);
  return Number(amount || 0) * (current / original);
}

function setBrowseServings(recipeId, value) {
  const parsed = parseInt(value, 10);
  browseServings[recipeId] = Number.isInteger(parsed) && parsed >= 1 ? parsed : 1;
  renderBrowseRecipes();
}

function changeBrowseServings(recipeId, delta) {
  const recipe = BROWSE_RECIPES.find(r => Number(r.id) === Number(recipeId));
  if (!recipe) return;

  const current = getBrowseServings(recipe);
  setBrowseServings(recipeId, current + delta);
}

function sanitizeBrowseServingsInput(input, recipeId) {
  input.value = input.value.replace(/[^0-9]/g, '');
  if (input.value === '') return;
  setBrowseServings(recipeId, input.value);
}
  
function makeIngredientRows(ingredients, recipe) {
  if (!Array.isArray(ingredients) || ingredients.length === 0) {
    return `<div class="detail-row"><span class="detail-name">No ingredients listed</span></div>`;
  }

  return ingredients.map(ing => {
    const matched = Array.isArray(ing.matched_allergens) ? ing.matched_allergens : [];
    const label = matched.length > 0 ? matched.join(', ') : 'ALLERGEN';

    return `
      <div class="detail-row ${ing.is_allergic ? 'detail-row-allergic' : ''}">
        <span class="detail-name-wrap">
          <span class="detail-name">${escapeHtml(ing.name)}</span>
          ${ing.is_allergic ? `<span class="ingredient-allergen-pill">${escapeHtml(label)}</span>` : ''}
        </span>
        <div class="detail-right">
          <span class="detail-qty">${formatNumber(scaleBrowseAmount(ing.amount, recipe))} ${escapeHtml(ing.unit)}</span>
        </div>
      </div>
    `;
  }).join('');
}

function recipeCard(recipe, index) {
  const region = recipe.region_name
    ? `<div class="cuisine-row"><span class="tag-cuisine">${escapeHtml(recipe.region_name)}</span></div>`
    : '';

  const detailId = `recipeDetail${index}`;
  const toggleId = `recipeToggle${index}`;
  const hasAllergen = Boolean(recipe.has_allergen);
  const currentServings = getBrowseServings(recipe);
const matchedAllergens = Array.isArray(recipe.matched_allergens) ? recipe.matched_allergens : [];

const allergenPreview = matchedAllergens.length > 0
  ? `
    <div class="tags tags-allergen-preview">
      ${matchedAllergens.map(allergen => `
        <span class="tag tag-allergen-preview">⚠ ${escapeHtml(allergen)}</span>
      `).join('')}
    </div>
  `
  : '';

  return `
    <article class="recipe-card ${hasAllergen ? 'has-allergen' : ''}">
      <div class="card-top">
        <div class="card-name-wrapper">
          ${region}
          <div class="card-name">${escapeHtml(recipe.name)}</div>
        </div>
        <div class="card-pct pct-high">${Array.isArray(recipe.ingredients) ? recipe.ingredients.length : 0} ingredients</div>
      </div>

      <div class="card-info">
        <div class="recipe-calories">🔥 ${formatNumber(scaleBrowseAmount(recipe.calories, recipe))} kcal</div>
        <div class="recipe-time">⏱️ ${formatNumber(recipe.time)} min</div>
        <div class="recipe-time">🍽️ ${currentServings} servings</div>
      </div>

      ${makeFlavorTags(recipe.flavors)}
      ${allergenPreview}

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

        <div class="servings-row browse-servings-row">
          <span class="servings-label">Servings</span>
          <div class="servings-control">
            <button type="button" class="servings-btn" onclick="changeBrowseServings(${recipe.id}, -1)">−</button>
            <input
              class="servings-input"
              type="text"
              inputmode="numeric"
              value="${currentServings}"
              oninput="sanitizeBrowseServingsInput(this, ${recipe.id})"
              onblur="setBrowseServings(${recipe.id}, this.value)"
            >
            <button type="button" class="servings-btn" onclick="changeBrowseServings(${recipe.id}, 1)">+</button>
         </div>
</div>
        <div class="card-sec-lbl">Needed ingredients</div>
        ${makeIngredientRows(recipe.ingredients, recipe)}

        <div class="card-sec-lbl green" style="margin-top:12px;">Nutrition</div>
        <div class="detail-row">
          <span class="detail-name">Protein</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(scaleBrowseAmount(recipe.protein, recipe))} g</span></div>
        </div>
        <div class="detail-row">
          <span class="detail-name">Carbs</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(scaleBrowseAmount(recipe.carbs, recipe))} g</span></div>
        </div>
        <div class="detail-row">
          <span class="detail-name">Fat</span>
          <div class="detail-right"><span class="detail-qty">${formatNumber(scaleBrowseAmount(recipe.fat, recipe))} g</span></div>
        </div>
      </div>

      ${hasAllergen
        ? `<span class="btn-view-recipe btn-view-recipe-disabled" aria-disabled="true">Blocked by allergen</span>`
        : `<a class="btn-view-recipe" href="recipe.php?id=${encodeURIComponent(recipe.id)}">Detailed recipe</a>`
      }
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
let recipeView = localStorage.getItem('recipeView') || 'card';

function setRecipeView(view) {
  recipeView = view;
  localStorage.setItem('recipeView', view);
  applyRecipeView();
}

function applyRecipeView() {
  const grid = document.getElementById('resultsGrid');
  const cardBtn = document.getElementById('cardViewBtn');
  const listBtn = document.getElementById('listViewBtn');

  if (!grid) return;

  grid.classList.toggle('list-view', recipeView === 'list');

  cardBtn?.classList.toggle('active', recipeView === 'card');
  listBtn?.classList.toggle('active', recipeView === 'list');
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
    BROWSE_RECIPES = recipes;
    renderBrowseRecipes();
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
function renderBrowseRecipes() {
  const grid = document.getElementById('resultsGrid');
  if (!grid) return;

  grid.innerHTML = BROWSE_RECIPES
    .map((recipe, index) => recipeCard(recipe, index))
    .join('');

  applyRecipeView();
}

document.addEventListener('DOMContentLoaded', () => {
  loadAllRecipes();
});
</script>

</body>
</html>
