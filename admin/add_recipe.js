let ALL_ING = [];

async function loadIngredients() {
  try {
    const res = await fetch('../web/get_ingredients.php', { cache: 'no-store' });
    if (!res.ok) throw new Error('Failed to load ingredients');
    const data = await res.json();
    ALL_ING = Array.isArray(data) ? data : [];
  } catch (err) {
    console.error('Ingredient load error:', err);
    ALL_ING = [];
  }
}

function findIngredientByName(name) {
  const trimmed = (name || '').trim().toLowerCase();
  return ALL_ING.find(i => i.name.toLowerCase() === trimmed) || null;
}

function addIngredientRow() {
  const container = document.getElementById("ingredients");

  const row = document.createElement("div");
  row.className = "ingredient-row";

  row.innerHTML = `
    <div class="af-wrap">
      <input placeholder="Ingredient name" class="ing-name">
      <div class="suggestions"></div>
    </div>
    <input placeholder="Amount" type="number" step="any" min="0.001" class="ing-amount">
    <select class="ing-unit" disabled>
      <option value="">Unit</option>
    </select>
    <button type="button" class="row-remove" onclick="this.parentElement.remove()">✕</button>
  `;

  container.appendChild(row);

  const nameInput = row.querySelector('.ing-name');
  const unitSelect = row.querySelector('.ing-unit');
  const suggestions = row.querySelector('.suggestions');

  nameInput.addEventListener('input', () => {
    const val = nameInput.value.trim().toLowerCase();
    suggestions.innerHTML = '';

    const matched = findIngredientByName(nameInput.value);
    if (matched) {
      unitSelect.innerHTML = `<option value="${matched.default_unit}">${matched.default_unit}</option>`;
      unitSelect.value = matched.default_unit;
    } else {
      unitSelect.innerHTML = `<option value="">Unit</option>`;
      unitSelect.value = '';
    }

    if (val.length < 2) {
      suggestions.style.display = 'none';
      return;
    }

    const hits = ALL_ING
      .filter(i => i.name.toLowerCase().includes(val))
      .slice(0, 7);

    if (!hits.length) {
      suggestions.style.display = 'none';
      return;
    }

    hits.forEach(hit => {
      const item = document.createElement('div');
      item.className = 'sug-item';
      item.textContent = hit.name;

      item.addEventListener('mousedown', e => {
        e.preventDefault();
        nameInput.value = hit.name;
        unitSelect.innerHTML = `<option value="${hit.default_unit}">${hit.default_unit}</option>`;
        unitSelect.value = hit.default_unit;
        suggestions.style.display = 'none';
      });

      suggestions.appendChild(item);
    });

    suggestions.style.display = 'block';
  });

  nameInput.addEventListener('blur', () => {
    setTimeout(() => {
      suggestions.style.display = 'none';
    }, 120);
  });
}

function addStepRow() {
  const container = document.getElementById("steps");

  const row = document.createElement("div");
  row.className = "step-row";

  row.innerHTML = `
    <input type="number" min="1" step="1" placeholder="#" class="step-number">
    <select class="step-type">
      <option value="prep">prep</option>
      <option value="cook">cook</option>
    </select>
    <input type="number" min="0" step="1" placeholder="Time (min)" class="step-time">
    <input placeholder="Instructions" class="step-text">
    <button type="button" class="row-remove" onclick="this.parentElement.remove()">✕</button>
  `;

  container.appendChild(row);
}

async function submitRecipe() {
  const title = document.getElementById("title").value.trim();
  const description = document.getElementById("description").value.trim();

  const ingredients = [...document.querySelectorAll("#ingredients .ingredient-row")].map(r => {
    const name = r.querySelector(".ing-name").value.trim();
    const amount = parseFloat(r.querySelector(".ing-amount").value);
    const unit = r.querySelector(".ing-unit").value;
    const ingredient = findIngredientByName(name);

    return {
      ingredient_id: ingredient ? ingredient.id : null,
      name,
      amount,
      unit
    };
  });

  const steps = [...document.querySelectorAll("#steps .step-row")].map(r => ({
    step_number: parseInt(r.querySelector(".step-number").value, 10),
    step_type: r.querySelector(".step-type").value,
    time_minutes: parseInt(r.querySelector(".step-time").value, 10),
    instructions: r.querySelector(".step-text").value.trim()
  }));

  if (!title) {
    alert("Recipe title is required.");
    return;
  }

  if (!ingredients.length) {
    alert("Add at least one ingredient.");
    return;
  }

  for (const ing of ingredients) {
    if (!ing.ingredient_id || !ing.amount || ing.amount <= 0 || !ing.unit) {
      alert("Every ingredient must use a valid ingredient from the database and have a valid amount.");
      return;
    }
  }

  if (!steps.length) {
    alert("Add at least one step.");
    return;
  }

  for (const step of steps) {
    if (!step.step_number || step.step_number <= 0 || !step.instructions || isNaN(step.time_minutes)) {
      alert("Each step must have a step number, time, and instructions.");
      return;
    }
  }

  const payload = {
    title,
    description,
    ingredients: ingredients.map(i => ({
      ingredient_id: i.ingredient_id,
      amount: i.amount,
      unit: i.unit
    })),
    steps
  };

  try {
    const res = await fetch("add_recipe_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    alert(data.success ? "Recipe added" : (data.message || "Error"));
  } catch (err) {
    console.error(err);
    alert("Request failed.");
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await loadIngredients();
  addIngredientRow();
  addStepRow();
});
