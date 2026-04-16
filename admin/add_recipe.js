let ingredients = [];
let steps = [];
let ALL_ING = [];

// ── LOAD INGREDIENTS ──
async function loadIngredients() {
  const res = await fetch('../web/get_ingredients.php');
  ALL_ING = await res.json();

  const sel = document.getElementById('ingUnit');
  if (!sel) return;

  sel.innerHTML = `<option>g</option><option>kg</option><option>ml</option><option>L</option>
                   <option>cups</option><option>tbsp</option><option>tsp</option><option>pcs</option>`;
}

// ── AUTOCOMPLETE BASIC ──
function findIngredient(name) {
  return ALL_ING.find(i => i.name.toLowerCase() === name.toLowerCase());
}

// ── INGREDIENTS ──
function addIngredient() {
  const name = document.getElementById('ingName').value.trim();
  const qty = parseFloat(document.getElementById('ingQty').value);
  const unit = document.getElementById('ingUnit').value;

  if (!findIngredient(name)) return alert("Invalid ingredient");
  if (!qty || qty <= 0) return;

  ingredients.push({ name, qty, unit });
  renderIngredients();
}

function renderIngredients() {
  const list = document.getElementById('ingredientList');
  list.innerHTML = '';

  ingredients.forEach((i, idx) => {
    const div = document.createElement('div');
    div.textContent = `${i.name} - ${i.qty} ${i.unit}`;
    div.onclick = () => {
      ingredients.splice(idx, 1);
      renderIngredients();
    };
    list.appendChild(div);
  });
}

// ── STEPS ──
function addStep() {
  const time = parseInt(document.getElementById('stepTime').value);
  const type = document.getElementById('stepType').value;
  const text = document.getElementById('stepText').value.trim();

  if (!text || !time) return;

  steps.push({ time, type, text });
  renderSteps();
}

function renderSteps() {
  const list = document.getElementById('stepList');
  list.innerHTML = '';

  steps.forEach((s, idx) => {
    const div = document.createElement('div');
    div.textContent = `(${s.time} min, ${s.type}) ${s.text}`;
    div.onclick = () => {
      steps.splice(idx, 1);
      renderSteps();
    };
    list.appendChild(div);
  });
}

// ── SUBMIT ──
async function submitRecipe() {
  const title = document.getElementById('title').value.trim();
  const description = document.getElementById('description').value.trim();

  if (!title || !ingredients.length || !steps.length) {
    alert("Missing data");
    return;
  }

  const res = await fetch('add_recipe.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      title,
      description,
      ingredients,
      steps
    })
  });

  const data = await res.json();

  if (data.success) {
    alert("Recipe added!");
    ingredients = [];
    steps = [];
    renderIngredients();
    renderSteps();
  } else {
    alert(data.message || "Error");
  }
}

window.addEventListener('DOMContentLoaded', loadIngredients);
