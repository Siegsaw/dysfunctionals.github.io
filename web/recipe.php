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
    if(localStorage.getItem('theme')==='dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body>

<header>
  <div class="logo" onclick="location.href='index.php'">PantryChef</div>

  <nav class="h-nav">
    <button class="nav-btn" onclick="location.href='index.php'">Home</button>
    <button class="nav-btn" onclick="location.href='inventory.php'">Inventory</button>
  </nav>

  <div class="h-right">
    <span id="userBadge"></span>
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

<script>
  const RECIPE_ID = <?php echo $recipeId; ?>;
</script>
<script src="shared.js"></script>
<script>
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

    const ingredients = recipe.ingredients.map(ing => `
      <div class="recipe-ing">
        <span class="recipe-ing-name">${ing.name}</span>
        <span class="recipe-ing-qty">${ing.amount} ${ing.unit}</span>
      </div>
    `).join('');

    const steps = recipe.steps.map(step => `
      <div class="step-card">
        <div class="step-top">
          <div class="step-number">Step ${step.step_number}</div>
          <div class="step-meta">
            <span class="step-type ${step.step_type}">${step.step_type}</span>
            ${step.time_minutes > 0 ? `<span class="step-time">⏱️ ${step.time_minutes} min</span>` : ''}
          </div>
        </div>

        <div class="step-text">${step.instructions}</div>
      </div>
    `).join('');

    shell.innerHTML = `
      <div class="recipe-header-card">
        <div class="recipe-breadcrumb">
          <a href="index.php">← Back to recipes</a>
        </div>

        <h1 class="recipe-title">${recipe.name}</h1>

        <div class="recipe-meta-row">
          <div class="recipe-meta">🔥 ${recipe.calories || 0} kcal</div>
          <div class="recipe-meta">⏱️ ${recipe.total_time || 0} min</div>
          <div class="recipe-meta">🥣 ${recipe.ingredients.length} ingredients</div>
        </div>

        <p class="recipe-description">
          ${recipe.description || 'No description available for this recipe.'}
        </p>
      </div>

      <div class="recipe-grid">
        <div class="recipe-card-side">
          <div class="section-label">Ingredients</div>
          <div class="ingredients-list">
            ${ingredients}
          </div>
          <div class="nutrition-box">
            <div class="section-label">Nutrition</div>
        
            <div class="nutrition-row">
              <span>Calories</span>
              <span>${recipe.calories || 0} kcal</span>
            </div>
        
            <div class="nutrition-row">
              <span>Protein</span>
              <span>${recipe.protein || 0} g</span>
            </div>
        
            <div class="nutrition-row">
              <span>Carbs</span>
              <span>${recipe.carbs || 0} g</span>
            </div>
        
            <div class="nutrition-row">
              <span>Fat</span>
              <span>${recipe.fat || 0} g</span>
    </div>
  </div>
 </div>

        <div class="recipe-card-main">
          <div class="section-label">Instructions</div>
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
