<?php
$recipeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PantryChef — Recipe</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="recipe.css">
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
    <a id="userBadge" href="profile.php"></a>
    <button class="btn-theme" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
    <button class="btn-signin" id="btnSignIn" onclick="location.href='login.php'">Sign In</button>
    <button class="btn-logout" id="btnLogout" onclick="doLogout()">Sign Out</button>
  </div>
</header>

<div class="recipe-page">
  <div id="recipeShell" class="recipe-shell">
    <div class="recipe-loading">Loading recipe...</div>
  </div>
</div>

<div id="cookModeOverlay" class="cook-mode-overlay" aria-hidden="true">
  <div class="cook-mode-card">
    <div class="cook-mode-top">
      <div>
        <div class="cook-mode-label">Cooking mode</div>
        <h2 id="cookModeRecipeTitle" class="cook-mode-title">Recipe</h2>
      </div>
      <button class="cook-close-btn" type="button" onclick="closeCookMode()">✕</button>
    </div>

    <div class="cook-progress-row">
      <div id="cookProgressText" class="cook-progress-text">Step 1 of 1</div>
      <div class="cook-progress-track">
        <div id="cookProgressBar" class="cook-progress-bar"></div>
      </div>
    </div>

    <div class="cook-step-panel">
      <div class="cook-step-meta">
        <span id="cookStepNumber" class="cook-step-badge">Step 1</span>
        <span id="cookStepType" class="cook-step-type">prep</span>
        <span id="cookStepTime" class="cook-step-time"></span>
      </div>

      <div id="cookStepText" class="cook-step-text"></div>
    </div>

    <div class="cook-nav">
      <button id="cookPrevBtn" class="cook-nav-btn cook-secondary" type="button" onclick="previousCookStep()">Previous</button>
      <button class="cook-nav-btn cook-secondary" type="button" onclick="closeCookMode()">Exit</button>
      <button id="cookNextBtn" class="cook-nav-btn cook-primary" type="button" onclick="nextCookStep()">Next</button>
    </div>
  </div>
</div>

<script>
  const RECIPE_ID = <?php echo $recipeId; ?>;
</script>

<script src="shared.js"></script>
<script>
let currentRecipe = null;
let cookModeIndex = 0;

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

function openCookMode() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps) || currentRecipe.steps.length === 0) {
    showToast('This recipe has no steps yet.');
    return;
  }

  cookModeIndex = 0;
  document.body.classList.add('cook-mode-open');
  document.getElementById('cookModeOverlay').classList.add('show');
  document.getElementById('cookModeOverlay').setAttribute('aria-hidden', 'false');
  renderCookModeStep();
}

function closeCookMode() {
  document.body.classList.remove('cook-mode-open');
  document.getElementById('cookModeOverlay').classList.remove('show');
  document.getElementById('cookModeOverlay').setAttribute('aria-hidden', 'true');
}

function renderCookModeStep() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps) || currentRecipe.steps.length === 0) return;

  const step = currentRecipe.steps[cookModeIndex];
  const total = currentRecipe.steps.length;
  const percent = ((cookModeIndex + 1) / total) * 100;

  document.getElementById('cookModeRecipeTitle').textContent = currentRecipe.name || 'Recipe';
  document.getElementById('cookProgressText').textContent = `Step ${cookModeIndex + 1} of ${total}`;
  document.getElementById('cookProgressBar').style.width = `${percent}%`;

  document.getElementById('cookStepNumber').textContent = `Step ${step.step_number}`;
  document.getElementById('cookStepText').textContent = step.instructions || '';
  document.getElementById('cookStepTime').textContent = step.time_minutes > 0 ? `⏱️ ${step.time_minutes} min` : '';

  const typeEl = document.getElementById('cookStepType');
  const stepType = (step.step_type || 'step').toLowerCase();
  typeEl.textContent = stepType;
  typeEl.className = `cook-step-type ${stepType}`;

  document.getElementById('cookPrevBtn').disabled = cookModeIndex === 0;
  document.getElementById('cookNextBtn').textContent = cookModeIndex === total - 1 ? 'Finish' : 'Next';
}

function nextCookStep() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps)) return;

  if (cookModeIndex < currentRecipe.steps.length - 1) {
    cookModeIndex++;
    renderCookModeStep();
  } else {
    closeCookMode();
    showToast('Recipe completed. Nice work!');
  }
}

function previousCookStep() {
  if (cookModeIndex > 0) {
    cookModeIndex--;
    renderCookModeStep();
  }
}

document.addEventListener('keydown', (event) => {
  const overlay = document.getElementById('cookModeOverlay');
  if (!overlay.classList.contains('show')) return;

  if (event.key === 'Escape') {
    closeCookMode();
  } else if (event.key === 'ArrowRight' || event.key === 'Enter') {
    nextCookStep();
  } else if (event.key === 'ArrowLeft') {
    previousCookStep();
  }
});

async function loadRecipe() {
  try {
    const response = await fetch(`get_recipe.php?id=${RECIPE_ID}`, { cache: 'no-store' });
    const recipe = await response.json();

    const shell = document.getElementById('recipeShell');

    if (!recipe || recipe.error) {
      shell.innerHTML = `
        <div class="recipe-error">
          <h2>Recipe not found</h2>
          <p>The recipe you are looking for does not exist.</p>
        </div>
      `;
      return;
    }

    currentRecipe = recipe;

    const ingredients = (recipe.ingredients || []).map(ing => `
      <div class="recipe-ing">
        <span class="recipe-ing-name">${escapeHtml(ing.name)}</span>
        <span class="recipe-ing-qty">${formatNumber(ing.amount)} ${escapeHtml(ing.unit)}</span>
      </div>
    `).join('');

    const steps = (recipe.steps || []).map(step => `
      <div class="step-card">
        <div class="step-top">
          <div class="step-number">Step ${escapeHtml(step.step_number)}</div>
          <div class="step-meta">
            <span class="step-type ${escapeHtml((step.step_type || '').toLowerCase())}">
              ${escapeHtml(step.step_type || 'step')}
            </span>
            ${Number(step.time_minutes) > 0 ? `<span class="step-time">⏱️ ${formatNumber(step.time_minutes)} min</span>` : ''}
          </div>
        </div>
        <div class="step-text">${escapeHtml(step.instructions)}</div>
      </div>
    `).join('');

    shell.innerHTML = `
      <div class="recipe-header-card">
        <div class="recipe-breadcrumb">
          <a href="browse_recipes.php">← Back to recipes</a>
        </div>

        <h1 class="recipe-title">${escapeHtml(recipe.name)}</h1>

        <div class="recipe-meta-row">
          <div class="recipe-meta">🔥 ${formatNumber(recipe.calories)} kcal</div>
          <div class="recipe-meta">⏱️ ${formatNumber(recipe.total_time)} min</div>
          <div class="recipe-meta">🥣 ${(recipe.ingredients || []).length} ingredients</div>
          <div class="recipe-meta">📝 ${(recipe.steps || []).length} steps</div>
        </div>

        <p class="recipe-description">
          ${escapeHtml(recipe.description || 'No description available for this recipe.')}
        </p>

        <div class="recipe-action-row">
          <button class="btn-cook-mode" type="button" onclick="openCookMode()">Start cooking mode</button>
        </div>
      </div>

      <div class="recipe-grid">
        <aside class="recipe-card-side">
          <div class="section-label">Ingredients</div>
          <div class="ingredients-list">
            ${ingredients || `<div class="recipe-ing"><span class="recipe-ing-name">No ingredients listed</span></div>`}
          </div>

          <div class="section-label section-gap">Nutrition</div>
          <div class="nutrition-box">
            <div class="nutrition-row">
              <span class="recipe-ing-name">Calories</span>
              <span class="recipe-ing-qty">${formatNumber(recipe.calories)} kcal</span>
            </div>
            <div class="nutrition-row">
              <span class="recipe-ing-name">Protein</span>
              <span class="recipe-ing-qty">${formatNumber(recipe.protein)} g</span>
            </div>
            <div class="nutrition-row">
              <span class="recipe-ing-name">Carbs</span>
              <span class="recipe-ing-qty">${formatNumber(recipe.carbs)} g</span>
            </div>
            <div class="nutrition-row">
              <span class="recipe-ing-name">Fat</span>
              <span class="recipe-ing-qty">${formatNumber(recipe.fat)} g</span>
            </div>
          </div>
        </aside>

        <section class="recipe-card-main">
          <div class="instructions-head">
            <div class="section-label section-label-no-margin">Instructions</div>
            <button class="btn-cook-inline" type="button" onclick="openCookMode()">Open cooking mode</button>
          </div>

          <div class="steps-list">
            ${steps || `<div class="recipe-error">No steps available for this recipe.</div>`}
          </div>
        </section>
      </div>
    `;
  } catch (error) {
    console.error(error);
    document.getElementById('recipeShell').innerHTML = `
      <div class="recipe-error">
        <h2>Failed to load recipe</h2>
        <p>Please try again later.</p>
      </div>
    `;
  }
}

loadRecipe();
</script>
</body>
</html>
