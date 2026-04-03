// ============================================================
// PantryChef — Home / Recipe Search Logic
// ============================================================

// ── INGREDIENT & RECIPE DATA ───────────────────────────────────
const ALL_ING = [
  "Chicken Breast","Chicken Thigh","Ground Beef","Salmon","Shrimp","Tofu",
  "Rice","Pasta","Bread","Flour","Sugar","Salt","Pepper",
  "Olive Oil","Vegetable Oil","Butter","Milk","Heavy Cream","Eggs",
  "Onion","Garlic","Tomato","Potato","Carrot","Broccoli","Spinach",
  "Bell Pepper","Mushroom","Zucchini","Cucumber","Lettuce","Corn",
  "Cheese","Mozzarella","Parmesan","Cheddar",
  "Soy Sauce","Vinegar","Lemon","Lime","Ginger","Cumin","Paprika",
  "Oregano","Basil","Thyme","Rosemary","Chili Flakes",
  "Chickpeas","Black Beans","Lentils","Coconut Milk",
  "Honey","Maple Syrup","Vanilla Extract","Baking Powder","Baking Soda",
  "Avocado","Sweet Potato","Celery","Green Beans","Peas"
];

const COMMON_LIST = [
  {name:"Eggs",icon:"🥚"},{name:"Milk",icon:"🥛"},{name:"Butter",icon:"🧈"},
  {name:"Salt",icon:"🧂"},{name:"Pepper",icon:"🌶️"},{name:"Onion",icon:"🧅"},
  {name:"Garlic",icon:"🧄"},{name:"Olive Oil",icon:"🫒"},{name:"Sugar",icon:"🍬"},
  {name:"Flour",icon:"🌾"},{name:"Rice",icon:"🍚"},{name:"Cheese",icon:"🧀"},
  {name:"Tomato",icon:"🍅"},{name:"Chicken Breast",icon:"🍗"},{name:"Pasta",icon:"🍝"}
];

const RECIPES = [
  {id:"1",name:"Scrambled Eggs",ingredients:[{name:"Eggs",amount:3,unit:"pcs"},{name:"Butter",amount:15,unit:"g"},{name:"Salt",amount:1,unit:"tsp"},{name:"Pepper",amount:0.5,unit:"tsp"},{name:"Milk",amount:30,unit:"ml"}]},
  {id:"2",name:"Pasta Aglio e Olio",ingredients:[{name:"Pasta",amount:200,unit:"g"},{name:"Garlic",amount:4,unit:"pcs"},{name:"Olive Oil",amount:60,unit:"ml"},{name:"Chili Flakes",amount:1,unit:"tsp"},{name:"Parmesan",amount:30,unit:"g"},{name:"Salt",amount:1,unit:"tsp"}]},
  {id:"3",name:"Chicken Stir-Fry",ingredients:[{name:"Chicken Breast",amount:300,unit:"g"},{name:"Bell Pepper",amount:2,unit:"pcs"},{name:"Broccoli",amount:150,unit:"g"},{name:"Soy Sauce",amount:30,unit:"ml"},{name:"Garlic",amount:3,unit:"pcs"},{name:"Ginger",amount:10,unit:"g"},{name:"Vegetable Oil",amount:15,unit:"ml"},{name:"Rice",amount:200,unit:"g"}]},
  {id:"4",name:"Tomato Soup",ingredients:[{name:"Tomato",amount:6,unit:"pcs"},{name:"Onion",amount:1,unit:"pcs"},{name:"Garlic",amount:3,unit:"pcs"},{name:"Olive Oil",amount:30,unit:"ml"},{name:"Basil",amount:1,unit:"tbsp"},{name:"Salt",amount:1,unit:"tsp"},{name:"Heavy Cream",amount:60,unit:"ml"}]},
  {id:"5",name:"Simple Pancakes",ingredients:[{name:"Flour",amount:150,unit:"g"},{name:"Eggs",amount:2,unit:"pcs"},{name:"Milk",amount:200,unit:"ml"},{name:"Sugar",amount:30,unit:"g"},{name:"Butter",amount:30,unit:"g"},{name:"Baking Powder",amount:2,unit:"tsp"}]},
  {id:"6",name:"Caesar Salad",ingredients:[{name:"Lettuce",amount:1,unit:"pcs"},{name:"Chicken Breast",amount:200,unit:"g"},{name:"Parmesan",amount:50,unit:"g"},{name:"Bread",amount:2,unit:"pcs"},{name:"Olive Oil",amount:30,unit:"ml"},{name:"Garlic",amount:2,unit:"pcs"},{name:"Lemon",amount:1,unit:"pcs"}]},
  {id:"7",name:"Fried Rice",ingredients:[{name:"Rice",amount:300,unit:"g"},{name:"Eggs",amount:2,unit:"pcs"},{name:"Soy Sauce",amount:30,unit:"ml"},{name:"Garlic",amount:2,unit:"pcs"},{name:"Carrot",amount:1,unit:"pcs"},{name:"Peas",amount:100,unit:"g"},{name:"Vegetable Oil",amount:15,unit:"ml"}]},
  {id:"8",name:"Honey Garlic Salmon",ingredients:[{name:"Salmon",amount:400,unit:"g"},{name:"Honey",amount:45,unit:"ml"},{name:"Garlic",amount:4,unit:"pcs"},{name:"Soy Sauce",amount:30,unit:"ml"},{name:"Butter",amount:15,unit:"g"},{name:"Lemon",amount:1,unit:"pcs"}]},
  {id:"9",name:"Vegetable Omelette",ingredients:[{name:"Eggs",amount:3,unit:"pcs"},{name:"Bell Pepper",amount:1,unit:"pcs"},{name:"Spinach",amount:50,unit:"g"},{name:"Mushroom",amount:100,unit:"g"},{name:"Cheese",amount:30,unit:"g"},{name:"Butter",amount:10,unit:"g"},{name:"Salt",amount:1,unit:"tsp"},{name:"Pepper",amount:0.5,unit:"tsp"}]},
  {id:"10",name:"Garlic Mashed Potatoes",ingredients:[{name:"Potato",amount:500,unit:"g"},{name:"Butter",amount:60,unit:"g"},{name:"Milk",amount:100,unit:"ml"},{name:"Garlic",amount:3,unit:"pcs"},{name:"Salt",amount:1,unit:"tsp"},{name:"Pepper",amount:0.5,unit:"tsp"}]}
];

const DEFAULT_UNITS = ["g", "kg", "ml", "L", "cups", "tbsp", "tsp", "pcs"];

const INGREDIENT_UNIT_RULES = {
  "Chicken Breast": ["g", "kg"],
  "Chicken Thigh": ["g", "kg"],
  "Ground Beef": ["g", "kg"],
  "Salmon": ["g", "kg"],
  "Shrimp": ["g", "kg"],
  "Tofu": ["g", "kg"],
  "Rice": ["g", "kg", "cups"],
  "Pasta": ["g", "kg", "cups"],
  "Bread": ["pcs"],
  "Flour": ["g", "kg", "cups", "tbsp", "tsp"],
  "Sugar": ["g", "kg", "cups", "tbsp", "tsp"],
  "Salt": ["g", "kg", "tbsp", "tsp"],
  "Pepper": ["g", "tbsp", "tsp"],
  "Olive Oil": ["ml", "L", "tbsp", "tsp"],
  "Vegetable Oil": ["ml", "L", "tbsp", "tsp"],
  "Butter": ["g", "kg", "tbsp"],
  "Milk": ["ml", "L"],
  "Heavy Cream": ["ml", "L"],
  "Eggs": ["pcs"],
  "Onion": ["pcs", "g", "kg"],
  "Garlic": ["pcs", "g"],
  "Tomato": ["pcs", "g", "kg"],
  "Potato": ["pcs", "g", "kg"],
  "Carrot": ["pcs", "g", "kg"],
  "Broccoli": ["pcs", "g", "kg"],
  "Spinach": ["g", "kg", "cups"],
  "Bell Pepper": ["pcs", "g"],
  "Mushroom": ["g", "kg", "pcs"],
  "Zucchini": ["pcs", "g", "kg"],
  "Cucumber": ["pcs", "g", "kg"],
  "Lettuce": ["pcs"],
  "Corn": ["pcs", "g"],
  "Cheese": ["g", "kg"],
  "Mozzarella": ["g", "kg"],
  "Parmesan": ["g", "kg", "tbsp"],
  "Cheddar": ["g", "kg"],
  "Soy Sauce": ["ml", "L", "tbsp", "tsp"],
  "Vinegar": ["ml", "L", "tbsp", "tsp"],
  "Lemon": ["pcs"],
  "Lime": ["pcs"],
  "Ginger": ["g", "kg"],
  "Cumin": ["g", "tbsp", "tsp"],
  "Paprika": ["g", "tbsp", "tsp"],
  "Oregano": ["g", "tbsp", "tsp"],
  "Basil": ["g", "tbsp", "tsp"],
  "Thyme": ["g", "tbsp", "tsp"],
  "Rosemary": ["g", "tbsp", "tsp"],
  "Chili Flakes": ["g", "tbsp", "tsp"],
  "Chickpeas": ["g", "kg", "cups"],
  "Black Beans": ["g", "kg", "cups"],
  "Lentils": ["g", "kg", "cups"],
  "Coconut Milk": ["ml", "L"],
  "Honey": ["ml", "tbsp", "tsp"],
  "Maple Syrup": ["ml", "tbsp", "tsp"],
  "Vanilla Extract": ["ml", "tbsp", "tsp"],
  "Baking Powder": ["g", "tbsp", "tsp"],
  "Baking Soda": ["g", "tbsp", "tsp"],
  "Avocado": ["pcs", "g"],
  "Sweet Potato": ["pcs", "g", "kg"],
  "Celery": ["pcs", "g"],
  "Green Beans": ["g", "kg"],
  "Peas": ["g", "kg", "cups"]
};

const UNIT_GROUPS = {
  g: "weight",
  kg: "weight",
  ml: "volume",
  L: "volume",
  tbsp: "volume",
  tsp: "volume",
  cups: "volume",
  pcs: "count"
};

// ── STATE ──────────────────────────────────────────────────────
let searchIngs = [];

// ── UNIT HELPERS ───────────────────────────────────────────────
function findCanonicalIngredientName(name) {
  const trimmed = (name || '').trim();
  if (!trimmed) return '';
  const exact = ALL_ING.find(i => i === trimmed);
  if (exact) return exact;
  const insensitive = ALL_ING.find(i => i.toLowerCase() === trimmed.toLowerCase());
  return insensitive || trimmed;
}

function getAllowedUnits(name) {
  const canonical = findCanonicalIngredientName(name);
  return INGREDIENT_UNIT_RULES[canonical] || DEFAULT_UNITS;
}

function isUnitAllowed(name, unit) {
  return getAllowedUnits(name).includes(unit);
}

function refreshUnitOptions() {
  const name = document.getElementById('ingName')?.value.trim() || '';
  const unitSelect = document.getElementById('ingUnit');
  if (!unitSelect) return;

  const allowed = getAllowedUnits(name);
  const current = unitSelect.value;

  unitSelect.innerHTML = allowed
    .map(unit => `<option value="${unit}">${unit}</option>`)
    .join('');

  unitSelect.value = allowed.includes(current) ? current : allowed[0];
}

function convertToBase(amount, unit) {
  const value = parseFloat(amount) || 0;

  switch (unit) {
    case 'kg':
      return { value: value * 1000, base: 'g', group: 'weight' };
    case 'g':
      return { value, base: 'g', group: 'weight' };
    case 'L':
      return { value: value * 1000, base: 'ml', group: 'volume' };
    case 'ml':
      return { value, base: 'ml', group: 'volume' };
    case 'tbsp':
      return { value: value * 15, base: 'ml', group: 'volume' };
    case 'tsp':
      return { value: value * 5, base: 'ml', group: 'volume' };
    case 'cups':
      return { value: value * 240, base: 'ml', group: 'volume' };
    case 'pcs':
      return { value, base: 'pcs', group: 'count' };
    default:
      return { value, base: unit, group: UNIT_GROUPS[unit] || 'other' };
  }
}

function calculateCoverage(haveAmount, haveUnit, needAmount, needUnit) {
  const have = convertToBase(haveAmount, haveUnit);
  const need = convertToBase(needAmount, needUnit);

  if (!need.value) {
    return { status: 'have', ratio: 1, haveText: `${haveAmount} ${haveUnit}` };
  }

  if (have.group !== need.group) {
    return { status: 'missing', ratio: 0, haveText: `${haveAmount} ${haveUnit}` };
  }

  const ratio = have.value / need.value;
  if (ratio >= 1) {
    return { status: 'have', ratio: 1, haveText: `${haveAmount} ${haveUnit}` };
  }

  if (ratio > 0) {
    return { status: 'partial', ratio, haveText: `${haveAmount} ${haveUnit}` };
  }

  return { status: 'missing', ratio: 0, haveText: `${haveAmount} ${haveUnit}` };
}

function combineIngredientEntries(entries) {
  const map = {};

  entries.forEach(entry => {
    const canonicalName = findCanonicalIngredientName(entry.name);
    const key = canonicalName.toLowerCase();
    const amount = parseFloat(entry.amount) || 0;
    const unit = entry.unit;

    if (!canonicalName || !amount || !unit || !isUnitAllowed(canonicalName, unit)) return;

    const converted = convertToBase(amount, unit);
    if (!map[key]) {
      map[key] = {
        name: canonicalName,
        group: converted.group,
        totalBase: 0,
        displayAmount: amount,
        displayUnit: unit
      };
    }

    if (map[key].group !== converted.group) return;

    map[key].totalBase += converted.value;
    map[key].displayAmount = Math.round(amount * 1000) / 1000;
    map[key].displayUnit = unit;
  });

  return map;
}

function sanitizeIngredientList(entries) {
  const cleaned = [];

  entries.forEach(entry => {
    const canonicalName = findCanonicalIngredientName(entry.name);
    const amount = Math.round((parseFloat(entry.amount) || 0) * 1000) / 1000;
    const allowedUnits = getAllowedUnits(canonicalName);
    const unit = allowedUnits.includes(entry.unit) ? entry.unit : allowedUnits[0];

    if (!canonicalName || !amount || amount <= 0) return;
    cleaned.push({ name: canonicalName, amount, unit });
  });

  return cleaned;
}

// ── INVENTORY MAP ──────────────────────────────────────────────
function getInvMap() {
  return combineIngredientEntries(loadUserIng());
}

// ── COMMON INGREDIENTS GRID ────────────────────────────────────
function buildCommonGrid() {
  const grid = document.getElementById('commonGrid');
  if (!grid) return;
  COMMON_LIST.forEach(c => {
    const b = document.createElement('button');
    b.className = 'cmn-btn';
    b.innerHTML = `<span>${c.icon}</span><span>${c.name}</span>`;
    b.onclick = () => {
      document.getElementById('ingName').value = c.name;
      refreshUnitOptions();
      document.getElementById('ingQty').focus();
    };
    grid.appendChild(b);
  });
}

function toggleCommon() {
  document.getElementById('commonToggle').classList.toggle('open');
  document.getElementById('commonGrid').classList.toggle('open');
}

// ── AUTOFILL ──────────────────────────────────────────────────
function initAutofill() {
  const input = document.getElementById('ingName');
  const box   = document.getElementById('suggestions');
  if (!input || !box) return;

  input.addEventListener('input', function () {
    validateForm();
    refreshUnitOptions();
    const val = this.value.trim();
    box.innerHTML = '';
    if (val.length < 2) { box.style.display = 'none'; return; }
    const hits = ALL_ING.filter(i => i.toLowerCase().includes(val.toLowerCase())).slice(0, 7);
    if (!hits.length) { box.style.display = 'none'; return; }
    hits.forEach((h, idx) => {
      const d = document.createElement('div');
      d.className = 'sug-item'; d.textContent = h; d.tabIndex = 0;
      d.addEventListener('mousedown', () => {
        input.value = h;
        refreshUnitOptions();
        box.style.display = 'none';
        document.getElementById('ingQty').focus();
      });
      d.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          input.value = h;
          refreshUnitOptions();
          box.style.display = 'none';
        }
        if (e.key === 'ArrowDown' && box.children[idx + 1]) box.children[idx + 1].focus();
        if (e.key === 'ArrowUp') { if (idx === 0) input.focus(); else box.children[idx - 1].focus(); }
      });
      box.appendChild(d);
    });
    box.style.display = 'block';
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown' && box.children.length) { e.preventDefault(); box.children[0].focus(); }
    if (e.key === 'Escape') box.style.display = 'none';
    if (e.key === 'Enter') addIng();
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.af-wrap')) box.style.display = 'none';
  });

  document.getElementById('ingQty').addEventListener('input', validateForm);
  document.getElementById('ingQty').addEventListener('keydown', e => {
    if (e.key === 'Enter') addIng();
  });
}

// ── FORM VALIDATION ───────────────────────────────────────────
function validateForm() {
  const inputName = document.getElementById('ingName').value.trim();
  const qty = parseFloat(document.getElementById('ingQty').value);
  const btn = document.getElementById('btnAddIng');

  const qtyOk = !isNaN(qty) && qty > 0;
  const exists = ALL_ING.some(ing => ing.toLowerCase() === inputName.toLowerCase());
  const valid = exists && qtyOk;

  if (btn) {
    btn.disabled = !valid;
  }
  
  const nameInput = document.getElementById('ingName');
  if (inputName.length > 2 && !exists) {
    nameInput.style.borderColor = "var(--err, #ff4d4d)";
  } else {
    nameInput.style.borderColor = "";
  }
}

// ── ADD / REMOVE ───────────────────────────────────────────────
async function addIng() {
  const rawName = document.getElementById('ingName').value.trim();
  const exists = ALL_ING.some(ing => ing.toLowerCase() === rawName.toLowerCase());
  const name = findCanonicalIngredientName(rawName);
  const qty  = parseFloat(document.getElementById('ingQty').value);
  const unit = document.getElementById('ingUnit').value;

  if (!name || isNaN(qty) || qty <= 0) {
    validateForm();
    return;
  }

  if (!isUnitAllowed(name, unit)) {
    showToast(`⚠️ "${name}" negali būti matuojamas vienetu "${unit}".`);
    refreshUnitOptions();
    return;
  }

  // Disable button during submission
  const btn = document.getElementById('btnAddIng');
  btn.disabled = true;

  try {
    const response = await fetch('add_inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, amount: qty, unit })
    });

    const data = await response.json();

    if (data.success) {
      showToast(`✓ Added ${qty} ${unit} of ${name}`);
      clearForm();
      await loadAndRenderInventory();
    } else {
      showToast('⚠️ ' + (data.message || 'Failed to add ingredient'));
      btn.disabled = false;
    }
  } catch (err) {
    console.error('Error adding ingredient:', err);
    showToast('⚠️ Connection error');
    btn.disabled = false;
  }
}

function clearForm() {
  document.getElementById('ingName').value = '';
  document.getElementById('ingQty').value  = '';
  document.getElementById('suggestions').style.display = 'none';
  document.getElementById('ingName').classList.remove('field-err');
  document.getElementById('ingQty').classList.remove('field-err');
  refreshUnitOptions();
  validateForm();
}

function removeIng(idx) {
  searchIngs.splice(idx, 1);
  persistSearchIngs();
  renderChips();
  runSearch();
}

// ── RENDER CHIPS ───────────────────────────────────────────────
function renderChips() {
  const sec  = document.getElementById('chipsSection');
  const list = document.getElementById('chipsList');
  const lbl  = document.getElementById('chipsLabel');
  list.innerHTML = '';
  if (!searchIngs.length) { sec.style.display = 'none'; return; }
  sec.style.display = 'block';
  lbl.textContent = `YOUR INGREDIENTS (${searchIngs.length})`;
  searchIngs.forEach((i, idx) => {
    const chip = document.createElement('div');
    chip.className = 'ing-chip';
    chip.innerHTML = `<span>${i.name}</span>${i.amount ? `<span class="ing-chip-qty">${i.amount} ${i.unit}</span>` : ''}<button class="chip-rm" onclick="removeIng(${idx})" title="Remove">✕</button>`;
    list.appendChild(chip);
  });
}

// ── RECIPE MATCHING ────────────────────────────────────────────
function matchRecipes() {
  const userMap = combineIngredientEntries([...loadUserIng(), ...searchIngs]);

  return RECIPES.map(recipe => {
    let score = 0;
    const details = recipe.ingredients.map(ri => {
      const key = ri.name.toLowerCase();
      const have = userMap[key];

      if (!have) return { ...ri, status: 'missing' };

      const coverage = calculateCoverage(have.displayAmount, have.displayUnit, ri.amount, ri.unit);
      score += coverage.ratio;

      return {
        ...ri,
        status: coverage.status,
        have: have.displayAmount,
        haveUnit: have.displayUnit,
        haveText: coverage.haveText
      };
    });

    const pct = Math.round((score / recipe.ingredients.length) * 100);
    return {
      recipe,
      pct,
      details,
      missing: details.filter(d => d.status === 'missing'),
      partial: details.filter(d => d.status === 'partial')
    };
  }).filter(r => r.pct > 0).sort((a, b) => b.pct - a.pct);
}

// ── RENDER RESULTS ─────────────────────────────────────────────
function runSearch() {
  const results = matchRecipes();
  const sec   = document.getElementById('resultsSection');
  const empty = document.getElementById('resultsEmpty');
  const grid  = document.getElementById('resultsGrid');
  const lbl   = document.getElementById('resultsLabel');
  grid.innerHTML = '';

  if (!searchIngs.length) { sec.style.display = 'none'; empty.style.display = 'block'; return; }
  empty.style.display = 'none';

  if (!results.length) {
    sec.style.display = 'block';
    lbl.textContent = 'No matching recipes found.';
    grid.innerHTML = `<div style="color:var(--muted);font-size:14px;grid-column:1/-1">Try adding more ingredients.</div>`;
    return;
  }

  sec.style.display = 'block';
  lbl.textContent = `${results.length} Matching Recipe${results.length !== 1 ? 's' : ''}`;

  results.forEach(r => {
    const complete = r.pct === 100;
    const pc = complete ? 'pct-high' : r.pct >= 40 ? 'pct-mid' : 'pct-low';
    const bc = complete ? 'prog-high' : r.pct >= 40 ? 'prog-mid' : 'prog-low';
    const mTags = r.missing.map(m => `<span class="tag tag-missing">${m.name}</span>`).join('');
    const pTags = r.partial.map(p => `<span class="tag tag-partial">${p.name} (${p.haveText}/${p.amount} ${p.unit})</span>`).join('');
    const allHaveTags = r.details.map(d => `<span class="tag tag-have">${d.name}</span>`).join('');
    const detailRows = r.details.map(d => {
      const dc = d.status === 'have' ? 'dot-have' : d.status === 'partial' ? 'dot-partial' : 'dot-missing';
      const qt = d.status === 'partial'
        ? `${d.haveText}/${d.amount} ${d.unit}`
        : d.status === 'have'
          ? `${d.haveText}`
          : `${d.amount} ${d.unit}`;
      return `<div class="detail-row"><span class="detail-name">${d.name}</span><span class="detail-right"><span class="detail-qty">${qt}</span><span class="status-dot ${dc}"></span></span></div>`;
    }).join('');

    const card = document.createElement('div');
    card.className = `recipe-card${complete ? ' complete' : ''}`;
    card.innerHTML = `
      <div class="card-top">
        <div class="card-name">${complete ? '✅ ' : ''}${r.recipe.name}</div>
        <span class="card-pct ${pc}">${r.pct}%</span>
      </div>
      <div class="prog-wrap"><div class="prog-bar ${bc}"></div></div>
      ${complete
        ? `<div class="card-sec-lbl green">✓ You have everything!</div><div class="tags">${allHaveTags}</div>`
        : `${r.missing.length ? `<div class="card-sec-lbl">Missing:</div><div class="tags">${mTags}</div>` : ''}
           ${r.partial.length ? `<div class="card-sec-lbl">Partial:</div><div class="tags">${pTags}</div>` : ''}`
      }
      <button class="card-toggle" id="ct-${r.recipe.id}" onclick="toggleDetail('${r.recipe.id}')">
        <svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 3l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
        Show all ingredients
      </button>
      <div class="card-detail" id="detail-${r.recipe.id}">${detailRows}</div>`;

    grid.appendChild(card);
    requestAnimationFrame(() => {
      setTimeout(() => { const bar = card.querySelector('.prog-bar'); if (bar) bar.style.width = r.pct + '%'; }, 60);
    });
  });
}

function toggleDetail(id) {
  const d   = document.getElementById(`detail-${id}`);
  const btn = document.getElementById(`ct-${id}`);
  d.classList.toggle('open');
  btn.querySelector('svg').style.transform = d.classList.contains('open') ? 'rotate(180deg)' : '';
  btn.lastChild.textContent = d.classList.contains('open') ? ' Hide ingredients' : ' Show all ingredients';
}

function updateTimeValue() {
  var val = document.getElementById("timeRange").value;
  document.getElementById("timeValue").innerText = val;
  runSearch();
}

function updateCalValue() {
  const val = document.getElementById("calRange").value;
  document.getElementById("calValue").innerText = val;
  runSearch(); 
}

// ── LOAD INVENTORY FROM SERVER ─────────────────────────────────
async function loadAndRenderInventory() {
  try {
    const response = await fetch('get_inventory.php');
    const inventory = await response.json();
    
    // Sanitize and update searchIngs
    searchIngs = sanitizeIngredientList(inventory);
    
    // Re-render everything
    renderChips();
    runSearch();
    
  } catch (err) {
    console.error('Error loading inventory:', err);
  }
}

// ── PERSIST SEARCH INGREDIENTS ─────────────────────────────────
function persistSearchIngs() {
  // This is no longer needed since we use add_inventory.php
  // But keeping for compatibility with removeIng()
}

// ── INIT ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  buildCommonGrid();
  initAutofill();
  refreshUnitOptions();
  validateForm();
  updateTimeValue();
  updateCalValue();
  
  // Load inventory from server on page load
  await loadAndRenderInventory();
});
