<?php
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
