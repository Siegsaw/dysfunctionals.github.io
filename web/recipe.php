<?php
$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
    <button class="nav-btn" onclick="location.href='browse_recipes.php'">Browse</button>
    <button class="nav-btn" onclick="location.href='inventory.php'">Inventory</button>
  </nav>

  <div class="h-right">
    <a id="userBadge" href="profile.php"></a>
    <button class="btn-theme" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
    <button class="btn-signin" id="btnSignIn" onclick="location.href='login.php'">Sign In</button>
    <button class="btn-logout" id="btnLogout" onclick="doLogout()">Sign Out</button>
  </div>
</header>

<div class="recipe-page">
  <div class="recipe-shell" id="recipeShell">
    <div class="recipe-loading">Loading recipe...</div>
  </div>
</div>

<div class="cook-mode-overlay" id="cookModeOverlay" aria-hidden="true">
  <div class="cook-mode-card">
    <div class="cook-mode-top">
      <div>
        <div class="cook-mode-label">Cooking mode</div>
        <h2 class="cook-mode-title" id="cookModeRecipeTitle">Recipe</h2>
      </div>
      <button class="cook-close-btn" onclick="closeCookMode()">✕</button>
    </div>

    <div class="cook-progress-row">
      <div class="cook-progress-text" id="cookProgressText">Step 1 of 1</div>
      <div class="cook-progress-track">
        <div class="cook-progress-bar" id="cookProgressBar"></div>
      </div>
    </div>

    <div class="cook-step-panel">
      <div class="cook-step-meta">
        <span class="cook-step-badge" id="cookStepNumber">Step 1</span>
        <span class="cook-step-type" id="cookStepType">prep</span>
        <span class="cook-step-time" id="cookStepTime"></span>
      </div>

      <div class="cook-step-text" id="cookStepText"></div>
    </div>

    <div class="cook-nav">
      <button class="cook-nav-btn cook-secondary" id="cookPrevBtn" onclick="previousCookStep()">Previous</button>
      <button class="cook-nav-btn cook-secondary" onclick="closeCookMode()">Exit</button>
      <button class="cook-nav-btn cook-primary" id="cookNextBtn" onclick="nextCookStep()">Next</button>
    </div>
  </div>
</div>

<script>
  const RECIPE_ID = <?php echo $recipeId; ?>;
</script>
<script src="shared.js"></script>
<script>
let currentRecipe = null;
let cookModeStepIndex = 0;

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function openCookMode() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps) || currentRecipe.steps.length === 0) {
    showToast('This recipe has no steps yet.');
    return;
  }

  cookModeStepIndex = 0;
  document.getElementById('cookModeOverlay').classList.add('show');
  document.body.classList.add('cook-mode-open');
  renderCookModeStep();
}

function closeCookMode() {
  document.getElementById('cookModeOverlay').classList.remove('show');
  document.body.classList.remove('cook-mode-open');
}

function renderCookModeStep() {
  const overlay = document.getElementById('cookModeOverlay');
  if (!currentRecipe || !currentRecipe.steps || currentRecipe.steps.length === 0) return;

  const steps = currentRecipe.steps;
  const step = steps[cookModeStepIndex];
  const total = steps.length;
  const isLast = cookModeStepIndex === total - 1;

  overlay.setAttribute('aria-hidden', 'false');

  document.getElementById('cookModeRecipeTitle').textContent = currentRecipe.name || 'Recipe';
  document.getElementById('cookProgressText').textContent = `Step ${cookModeStepIndex + 1} of ${total}`;
  document.getElementById('cookProgressBar').style.width = `${((cookModeStepIndex + 1) / total) * 100}%`;

  document.getElementById('cookStepNumber').textContent = `Step ${step.step_number}`;
  document.getElementById('cookStepType').textContent = step.step_type || 'step';
  document.getElementById('cookStepType').className = `cook-step-type ${step.step_type || ''}`;
  document.getElementById('cookStepTime').textContent = step.time_minutes > 0 ? `⏱️ ${step.time_minutes} min` : '';
  document.getElementById('cookStepText').textContent = step.instructions || '';

  document.getElementById('cookPrevBtn').disabled = cookModeStepIndex === 0;
  document.getElementById('cookNextBtn').textContent = isLast ? 'Finish' : 'Next';
}

function nextCookStep() {
  if (!currentRecipe || !currentRecipe.steps) return;

  if (cookModeStepIndex < currentRecipe.steps.length - 1) {
    cookModeStepIndex++;
    renderCookModeStep();
  } else {
    closeCookMode();
    showToast('Recipe completed. Nice work!');
  }
}

function previousCookStep() {
  if (cookModeStepIndex > 0) {
    cookModeStepIndex--;
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

    const ingredients = recipe.ingredients.map(ing => `
      <div class="recipe-ing">
        <span class="recipe-ing-name">${escapeHtml(ing.name)}</span>
        <span class="recipe-ing-qty">${ing.amount} ${escapeHtml(ing.unit)}</span>
      </div>
    `).join('');

    const steps = recipe.steps.map(step => `
      <div class="step-card">
        <div class="step-top">
          <div class="step-number">Step ${step.step_number}</div>
          <div class="step-meta">
            <span class="step-type ${escapeHtml(step.step_type)}">${escapeHtml(step.step_type)}</span>
            ${step.time_minutes > 0 ? `<span class="step-time">⏱️ ${step.time_minutes} min</span>` : ''}
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
          <div class="recipe-meta">🔥 ${recipe.calories || 0} kcal</div>
          <div class="recipe-meta">⏱️ ${recipe.total_time || 0} min</div>
          <div class="recipe-meta">🥣 ${recipe.ingredients.length} ingredients</div>
          <div class="recipe-meta">📝 ${recipe.steps.length} steps</div>
        </div>

        <p class="recipe-description">
          ${escapeHtml(recipe.description || 'No description available for this recipe.')}
        </p>

        <div class="recipe-action-row">
          <button class="btn-cook-mode" onclick="openCookMode()">Start cooking mode</button>
        </div>
      </div>

      <div class="recipe-grid">
        <div class="recipe-card-side">
          <div class="section-label">Ingredients</div>
          <div class="ingredients-list">
            ${ingredients}
          </div>

          <div class="section-label">Nutrition</div>
          <div class="ingredients-list">
            <div class="recipe-ing">
              <span class="recipe-ing-name">Calories</span>
              <span class="recipe-ing-qty">${recipe.calories || 0} kcal</span>
            </div>

            <div class="recipe-ing">
              <span class="recipe-ing-name">Protein</span>
              <span class="recipe-ing-qty">${recipe.protein || 0} g</span>
            </div>

            <div class="recipe-ing">
              <span class="recipe-ing-name">Carbs</span>
              <span class="recipe-ing-qty">${recipe.carbs || 0} g</span>
            </div>

            <div class="recipe-ing">
              <span class="recipe-ing-name">Fat</span>
              <span class="recipe-ing-qty">${recipe.fat || 0} g</span>
            </div>
          </div>
        </div>

        <div class="recipe-card-main">
          <div class="instructions-head">
            <div class="section-label">Instructions</div>
            <button class="btn-cook-inline" onclick="openCookMode()">Open cooking mode</button>
          </div>

          <div class="steps-list">
            ${steps}
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    console.error(err);
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
