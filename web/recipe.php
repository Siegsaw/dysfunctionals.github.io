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

    <div class="cook-step-panel" id="cookStepPanel">
      <div class="cook-step-meta">
        <span id="cookStepNumber" class="cook-step-badge">Step 1</span>
        <span id="cookStepType" class="cook-step-type">prep</span>
        <span id="cookStepTime" class="cook-step-time"></span>
      </div>

      <div id="cookStepText" class="cook-step-text"></div>
    </div>

    <div class="cook-complete-panel" id="cookCompletePanel" hidden>
      <div class="cook-complete-icon">🍽️</div>
      <div class="cook-complete-title">Recipe Complete!</div>
      <div class="cook-complete-text" id="cookCompleteText">
        You finished all the cooking steps. Confirm to deduct the used ingredients from your inventory.
      </div>
      <div class="cook-complete-status" id="cookCompleteStatus" hidden></div>
    </div>

    <div class="cook-nav">
      <button id="cookPrevBtn" class="cook-nav-btn cook-secondary" type="button" onclick="previousCookStep()">Previous</button>
      <button id="cookExitBtn" class="cook-nav-btn cook-secondary" type="button" onclick="closeCookMode()">Exit</button>
      <button id="cookNextBtn" class="cook-nav-btn cook-primary" type="button" onclick="nextCookStep()">Next</button>
      <button id="cookConfirmBtn" class="cook-nav-btn cook-primary" type="button" onclick="confirmRecipeCompletion()" hidden>
        Confirm and use ingredients
      </button>
    </div>
  </div>
</div>

<script>
  const RECIPE_ID = <?php echo $recipeId; ?>;
</script>

<script src="shared.js"></script>
<script>
let currentRecipe = null;
let currentServings = 1;
let originalServings = 1;
let userAllergens = [];
let cookModeIndex = 0;
let cookCompletionScreen = false;
let cookConfirmBusy = false;

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

function scaleAmount(amount) {
  const base = Number(amount || 0);
  return base * (currentServings / originalServings);
}

function changeServings(delta) {
  setServings(currentServings + delta);
}

function setServings(value) {
  const parsed = parseInt(value, 10);
  currentServings = Number.isInteger(parsed) && parsed >= 1 ? parsed : 1;

  const input = document.getElementById('servingsInput');
  if (input) input.value = currentServings;

  renderRecipeContent();
}

function sanitizeServingsInput(input) {
  input.value = input.value.replace(/[^0-9]/g, '');

  if (input.value === '') return;

  setServings(input.value);
}

function normalizeValue(value) {
  return String(value ?? '').trim().toLowerCase();
}

function isAllergicIngredient(name) {
  const ingredientName = normalizeValue(name);
  if (!ingredientName || !Array.isArray(userAllergens) || userAllergens.length === 0) {
    return false;
  }

  return userAllergens.some(allergen => ingredientName.includes(normalizeValue(allergen)));
}

async function loadUserAllergens() {
  try {
    const response = await fetch('get_allergens.php', { cache: 'no-store' });
    if (!response.ok) {
      userAllergens = [];
      return;
    }

    const data = await response.json();
    userAllergens = Array.isArray(data.selected) ? data.selected : [];
  } catch (error) {
    console.error('Failed to load allergens', error);
    userAllergens = [];
  }
}

function clearCookCompletionStatus() {
  const box = document.getElementById('cookCompleteStatus');
  if (!box) return;

  box.hidden = true;
  box.className = 'cook-complete-status';
  box.textContent = '';
}

function setCookCompletionStatus(message, type = 'error') {
  const box = document.getElementById('cookCompleteStatus');
  if (!box) return;

  box.hidden = false;
  box.className = `cook-complete-status ${type}`;
  box.textContent = message;
}

function openCookMode() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps) || currentRecipe.steps.length === 0) {
    showToast('This recipe has no steps yet.');
    return;
  }

  cookModeIndex = 0;
  cookCompletionScreen = false;
  cookConfirmBusy = false;

  const confirmBtn = document.getElementById('cookConfirmBtn');
  confirmBtn.disabled = false;
  confirmBtn.textContent = 'Confirm and use ingredients';

  clearCookCompletionStatus();

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

  const stepPanel = document.getElementById('cookStepPanel');
  const completePanel = document.getElementById('cookCompletePanel');
  const prevBtn = document.getElementById('cookPrevBtn');
  const nextBtn = document.getElementById('cookNextBtn');
  const confirmBtn = document.getElementById('cookConfirmBtn');
  const exitBtn = document.getElementById('cookExitBtn');

  document.getElementById('cookModeRecipeTitle').textContent = currentRecipe.name || 'Recipe';

  if (cookCompletionScreen) {
    stepPanel.hidden = true;
    completePanel.hidden = false;

    document.getElementById('cookProgressText').textContent = `Finished • ${currentRecipe.steps.length} steps completed`;
    document.getElementById('cookProgressBar').style.width = '100%';

    prevBtn.hidden = true;
    nextBtn.hidden = true;
    confirmBtn.hidden = false;
    exitBtn.textContent = 'Close';
    return;
  }

  const step = currentRecipe.steps[cookModeIndex];
  const total = currentRecipe.steps.length;
  const percent = ((cookModeIndex + 1) / total) * 100;

  stepPanel.hidden = false;
  completePanel.hidden = true;

  document.getElementById('cookProgressText').textContent = `Step ${cookModeIndex + 1} of ${total}`;
  document.getElementById('cookProgressBar').style.width = `${percent}%`;

  document.getElementById('cookStepNumber').textContent = `Step ${step.step_number}`;
  document.getElementById('cookStepText').textContent = step.instructions || '';
  document.getElementById('cookStepTime').textContent = Number(step.time_minutes) > 0 ? `⏱️ ${formatNumber(step.time_minutes)} min` : '';

  const typeEl = document.getElementById('cookStepType');
  const stepType = (step.step_type || 'step').toLowerCase();
  typeEl.textContent = stepType;
  typeEl.className = `cook-step-type ${stepType}`;

  prevBtn.hidden = false;
  nextBtn.hidden = false;
  confirmBtn.hidden = true;
  exitBtn.textContent = 'Exit';

  prevBtn.disabled = cookModeIndex === 0;
  nextBtn.textContent = cookModeIndex === total - 1 ? 'Finish' : 'Next';
}

function nextCookStep() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps)) return;
  if (cookCompletionScreen) return;

  if (cookModeIndex < currentRecipe.steps.length - 1) {
    cookModeIndex++;
    renderCookModeStep();
  } else {
    cookCompletionScreen = true;
    renderCookModeStep();
  }
}

function previousCookStep() {
  if (!currentRecipe || !Array.isArray(currentRecipe.steps)) return;

  if (cookCompletionScreen) {
    cookCompletionScreen = false;
    cookModeIndex = Math.max(0, currentRecipe.steps.length - 1);
    renderCookModeStep();
    return;
  }

  if (cookModeIndex > 0) {
    cookModeIndex--;
    renderCookModeStep();
  }
}

async function confirmRecipeCompletion() {
  if (!currentRecipe || cookConfirmBusy) return;

  cookConfirmBusy = true;
  clearCookCompletionStatus();

  const confirmBtn = document.getElementById('cookConfirmBtn');
  confirmBtn.disabled = true;
  confirmBtn.textContent = 'Updating inventory...';

  try {
    const response = await fetch('complete_recipe.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        recipe_id: currentRecipe.id
      })
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      let message = result.message || 'Failed to update inventory.';

      if (response.status === 401) {
        message = 'Please sign in to update your inventory.';
      } else if (Array.isArray(result.problems) && result.problems.length > 0) {
        message = result.problems.join(', ');
      }

      setCookCompletionStatus(message, 'error');
      showToast(message);

      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm and use ingredients';
      cookConfirmBusy = false;
      return;
    }

    setCookCompletionStatus('Ingredients were deducted from your inventory successfully.', 'success');
    showToast('Recipe complete! Ingredients deducted from inventory.');

    confirmBtn.textContent = 'Done';

    setTimeout(() => {
      closeCookMode();
    }, 900);

  } catch (error) {
    console.error(error);

    const message = 'Failed to update inventory.';
    setCookCompletionStatus(message, 'error');
    showToast(message);

    confirmBtn.disabled = false;
    confirmBtn.textContent = 'Confirm and use ingredients';
    cookConfirmBusy = false;
  }
}

document.addEventListener('keydown', (event) => {
  const overlay = document.getElementById('cookModeOverlay');
  if (!overlay.classList.contains('show')) return;

  if (event.key === 'Escape') {
    closeCookMode();
  } else if (!cookCompletionScreen && (event.key === 'ArrowRight' || event.key === 'Enter')) {
    nextCookStep();
  } else if (event.key === 'ArrowLeft') {
    previousCookStep();
  }
});

function renderRecipeContent() {
  const recipe = currentRecipe;
  const shell = document.getElementById('recipeShell');
  if (!recipe || !shell) return;

  const allergicCount = (recipe.ingredients || []).filter(ing => ing.is_allergic).length;

  const ingredients = (recipe.ingredients || []).map(ing => {
    const isAllergic = Boolean(ing.is_allergic);

    return `
      <div class="recipe-ing ${isAllergic ? 'recipe-ing-allergic' : ''}">
        <span class="recipe-ing-name-wrap">
          <span class="recipe-ing-name">${escapeHtml(ing.name)}</span>
          ${isAllergic ? '<span class="allergen-pill">Allergic</span>' : ''}
        </span>
        <span class="recipe-ing-qty">${formatNumber(scaleAmount(ing.amount))} ${escapeHtml(ing.unit)}</span>
      </div>
    `;
  }).join('');

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
        <div class="recipe-meta">🍽️ ${currentServings} servings</div>
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

        <div class="servings-control">
          <button type="button" class="servings-btn" onclick="changeServings(-1)">−</button>
          <input
            id="servingsInput"
            class="servings-input"
            type="text"
            inputmode="numeric"
            value="${currentServings}"
            oninput="sanitizeServingsInput(this)"
            onblur="setServings(this.value)"
          >
          <button type="button" class="servings-btn" onclick="changeServings(1)">+</button>
        </div>

        ${allergicCount > 0 ? `
          <div class="allergen-summary" role="status" aria-live="polite">
            ${allergicCount} ingredient${allergicCount === 1 ? '' : 's'} match your saved allergens.
          </div>
        ` : ''}

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
}
  
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
    originalServings = Number(recipe.servings) || 1;
    currentServings = originalServings;
    renderRecipeContent();
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

document.addEventListener('DOMContentLoaded', async () => {
  await loadUserAllergens();
  await loadRecipe();
});
</script>
</body>
</html>
