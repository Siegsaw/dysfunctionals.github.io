let ALL_ING = [];
let lastValidatedPayload = null;

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
    <input placeholder="Amount" type="number" step="1" min="0.001" class="ing-amount">
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

function buildRawPayload() {
  const title = document.getElementById("title").value.trim();
  const description = document.getElementById("description").value.trim();
  
  const flavors = [...document.getElementById("flavors").selectedOptions].map(o => parseInt(o.value));
  const regions = [...document.getElementById("regions").selectedOptions].map(o => parseInt(o.value));
  
  const ingredients = [...document.querySelectorAll("#ingredients .ingredient-row")].map(r => {
    const name = r.querySelector(".ing-name").value.trim();
    const amountRaw = r.querySelector(".ing-amount").value;
    const unit = r.querySelector(".ing-unit").value;
    const ingredient = findIngredientByName(name);

    return {
      ingredient_id: ingredient ? ingredient.id : null,
      name,
      amount: parseFloat(amountRaw),
      unit,
      flavors,
      regions
    };
  });

  const steps = [...document.querySelectorAll("#steps .step-row")].map(r => ({
    step_number: parseInt(r.querySelector(".step-number").value, 10),
    step_type: r.querySelector(".step-type").value,
    time_minutes: parseInt(r.querySelector(".step-time").value, 10),
    instructions: r.querySelector(".step-text").value.trim()
  }));

  return { title, description, ingredients, steps };
}

function renderPreview(data) {
  const previewCard = document.getElementById('previewCard');
  const previewContent = document.getElementById('previewContent');
  const confirmBtn = document.getElementById('confirmSaveBtn');

  previewCard.style.display = 'block';

  let html = '';

  if (data.errors && data.errors.length) {
    html += `
      <div class="preview-errors">
        <h4>Validation errors</h4>
        <ul>${data.errors.map(e => `<li>${e}</li>`).join('')}</ul>
      </div>
    `;
    confirmBtn.disabled = true;
    lastValidatedPayload = null;
  } else {
    html += `<div class="preview-ok">Data is valid. This is what will be inserted into the database.</div>`;
    confirmBtn.disabled = false;
    lastValidatedPayload = data.payload;
  }

  if (data.preview) {
    html += `
      <div class="preview-block">
        <div class="preview-title">${escapeHtml(data.preview.title)}</div>
        <div class="preview-sub">${escapeHtml(data.preview.description || 'No description')}</div>

        <div class="meta-grid">
          <div class="meta-pill">Ingredients: ${data.preview.ingredients.length}</div>
          <div class="meta-pill">Steps: ${data.preview.steps.length}</div>
          <div class="meta-pill">Total time: ${data.preview.total_time_minutes} min</div>
          <div class="meta-pill">Calories: ${data.preview.calories} kcal</div>
          <div class="meta-pill">Protein: ${data.preview.protein} g</div>
          <div class="meta-pill">Fat: ${data.preview.fat} g</div>
          <div class="meta-pill">Carbs: ${data.preview.carbs} g</div>
        </div>
      </div>

      <div class="preview-block">
        <div class="section">Recipe row</div>
        <div class="preview-list">
          <div class="preview-row">
            <div class="preview-row-left">title</div>
            <div class="preview-row-right">${escapeHtml(data.preview.title)}</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">description</div>
            <div class="preview-row-right">${escapeHtml(data.preview.description || '')}</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">created_at</div>
            <div class="preview-row-right">AUTO (CURRENT_TIMESTAMP)</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">total_time_minutes</div>
            <div class="preview-row-right">${data.preview.total_time_minutes}</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">calories</div>
            <div class="preview-row-right">${data.preview.calories} kcal</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">protein</div>
            <div class="preview-row-right">${data.preview.protein} g</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">fat</div>
            <div class="preview-row-right">${data.preview.fat} g</div>
          </div>
          <div class="preview-row">
            <div class="preview-row-left">carbs</div>
            <div class="preview-row-right">${data.preview.carbs} g</div>
          </div>
        </div>
      </div>

      <div class="preview-block">
        <div class="section">recipe_ingredients rows</div>
        <div class="preview-list">
          ${data.preview.ingredients.map(ing => `
            <div class="preview-row">
              <div class="preview-row-left">${escapeHtml(ing.name)} (ingredient_id: ${ing.ingredient_id})</div>
              <div class="preview-row-right">${ing.amount} ${escapeHtml(ing.unit)}</div>
            </div>
          `).join('')}
        </div>
      </div>

      <div class="preview-block">
        <div class="section">recipe_steps rows</div>
        <div class="preview-list">
          ${data.preview.steps.map(step => `
            <div class="preview-row">
              <div class="preview-row-left">#${step.step_number} · ${escapeHtml(step.step_type)} · ${step.time_minutes} min</div>
              <div class="preview-row-right">${escapeHtml(step.instructions)}</div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }

  previewContent.innerHTML = html;
}

async function previewRecipe() {
  const payload = buildRawPayload();

  try {
    const res = await fetch('preview_recipe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    renderPreview(data);
  } catch (err) {
    console.error(err);
    alert('Preview request failed.');
  }
}

async function confirmSaveRecipe() {
  if (!lastValidatedPayload) {
    alert('Please run preview first and fix any validation issues.');
    return;
  }

  try {
    const res = await fetch('add_recipe_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(lastValidatedPayload)
    });

    const data = await res.json();

    if (data.success) {
      alert(`Recipe added successfully. New recipe_id: ${data.recipe_id}`);
      window.location.reload();
      return;
    }

    alert(data.message || 'Insert failed.');
  } catch (err) {
    console.error(err);
    alert('Save request failed.');
  }
}

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

document.addEventListener('DOMContentLoaded', async () => {
  await loadIngredients();
  addIngredientRow();
  addStepRow();
});
