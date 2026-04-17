// ============================================================
// PantryChef — Home / Recipe Search Logic
// ============================================================
const DEFAULT_UNITS = ["g", "kg", "ml", "L", "cups", "tbsp", "tsp", "pcs"];

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
let serverInventory = [];
let ALL_ING = [];
let RECIPES = [];
let selectedFlavors = [];
let FLAVORS = [];

// ── HELPERS ────────────────────────────────────────────────────
function showToast(message) {
  if (window.showToastMessage) {
    window.showToastMessage(message);
    return;
  }
  console.log(message);
}

function getIngredientObj(name) {
  const trimmed = (name || '').trim().toLowerCase();
  return ALL_ING.find(i => i.name.toLowerCase() === trimmed) || null;
}

function findCanonicalIngredientName(name) {
  const ing = getIngredientObj(name);
  return ing ? ing.name : '';
}

function getAllowedUnits(name) {
  const ing = getIngredientObj(name);
  return ing ? [ing.default_unit] : DEFAULT_UNITS;
}

function isUnitAllowed(name, unit) {
  const ing = getIngredientObj(name);
  return ing ? ing.default_unit === unit : false;
}

function refreshUnitOptions() {
  const name = document.getElementById('ingName')?.value.trim() || '';
  const unitSelect = document.getElementById('ingUnit');
  if (!unitSelect) return;

  const ing = getIngredientObj(name);
  const unit = ing ? ing.default_unit : 'pcs';

  unitSelect.innerHTML = `<option value="${unit}">${unit}</option>`;
  unitSelect.value = unit;
}

function convertToBase(amount, unit) {
  const value = parseFloat(amount) || 0;

  switch (unit) {
    case 'kg': return { value: value * 1000, base: 'g', group: 'weight' };
    case 'g': return { value, base: 'g', group: 'weight' };
    case 'L': return { value: value * 1000, base: 'ml', group: 'volume' };
    case 'ml': return { value, base: 'ml', group: 'volume' };
    case 'tbsp': return { value: value * 15, base: 'ml', group: 'volume' };
    case 'tsp': return { value: value * 5, base: 'ml', group: 'volume' };
    case 'cups': return { value: value * 240, base: 'ml', group: 'volume' };
    case 'pcs': return { value, base: 'pcs', group: 'count' };
    default: return { value, base: unit, group: UNIT_GROUPS[unit] || 'other' };
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
  if (ratio >= 1) return { status: 'have', ratio: 1, haveText: `${haveAmount} ${haveUnit}` };
  if (ratio > 0) return { status: 'partial', ratio, haveText: `${haveAmount} ${haveUnit}` };

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
    if (!canonicalName) return;

    const amount = Math.round((parseFloat(entry.amount) || 0) * 1000) / 1000;
    const allowedUnits = getAllowedUnits(canonicalName);
    const unit = allowedUnits.includes(entry.unit) ? entry.unit : allowedUnits[0];

    if (!amount || amount <= 0) return;

    cleaned.push({
      ...entry,
      name: canonicalName,
      amount,
      unit
    });
  });

  return cleaned;
}
function getInvMap() {
  return combineIngredientEntries([...serverInventory, ...searchIngs]);
}
// ── DATA LOADING ───────────────────────────────────────────────
async function loadIngredients() {
  const response = await fetch('get_ingredients.php', { cache: 'no-store' });
  if (!response.ok) throw new Error('Failed to load ingredients');

  const data = await response.json();
  ALL_ING = Array.isArray(data) ? data : [];
}

async function loadRecipes() {
  const response = await fetch('get_recipes.php', { cache: 'no-store' });
  if (!response.ok) throw new Error('Failed to load recipes');

  const data = await response.json();
  RECIPES = Array.isArray(data) ? data : [];
}

// ── COMMON GRID ────────────────────────────────────────────────
function buildCommonGrid() {
  const grid = document.getElementById('commonGrid');
  if (!grid) return;

  grid.innerHTML = '';

  ALL_ING.slice(0, 12).forEach(c => {
    const b = document.createElement('button');
    b.className = 'cmn-btn';
    b.innerHTML = `<span>${c.name}</span>`;
    b.onclick = () => {
      document.getElementById('ingName').value = c.name;
      refreshUnitOptions();
      validateForm();
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
  const box = document.getElementById('suggestions');
  if (!input || !box) return;

  input.addEventListener('input', function () {
    validateForm();
    refreshUnitOptions();

    const val = this.value.trim();
    box.innerHTML = '';

    if (val.length < 2) {
      box.style.display = 'none';
      return;
    }

    const hits = ALL_ING
      .filter(i => i.name.toLowerCase().includes(val.toLowerCase()))
      .slice(0, 7);

    if (!hits.length) {
      box.style.display = 'none';
      return;
    }

    hits.forEach((h, idx) => {
      const d = document.createElement('div');
      d.className = 'sug-item';
      d.textContent = h.name;
      d.tabIndex = 0;

      d.addEventListener('mousedown', () => {
        input.value = h.name;
        refreshUnitOptions();
        validateForm();
        box.style.display = 'none';
        document.getElementById('ingQty').focus();
      });

      d.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          input.value = h.name;
          refreshUnitOptions();
          validateForm();
          box.style.display = 'none';
        }
        if (e.key === 'ArrowDown' && box.children[idx + 1]) box.children[idx + 1].focus();
        if (e.key === 'ArrowUp') {
          if (idx === 0) input.focus();
          else box.children[idx - 1].focus();
        }
      });

      box.appendChild(d);
    });

    box.style.display = 'block';
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown' && box.children.length) {
      e.preventDefault();
      box.children[0].focus();
    }
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
  const exists = !!getIngredientObj(inputName);
  const valid = exists && qtyOk;

  if (btn) btn.disabled = !valid;

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
  const ingredient = getIngredientObj(rawName);
  const qty = parseFloat(document.getElementById('ingQty').value);
  const unit = document.getElementById('ingUnit').value;
  const expiration_date = document.getElementById('ingExpDate').value || null;

  if (!ingredient) {
    showToast('⚠️ Invalid ingredient');
    validateForm();
    return;
  }

  const name = ingredient.name;

  if (isNaN(qty) || qty <= 0) {
    validateForm();
    return;
  }

  if (!isUnitAllowed(name, unit)) {
    showToast(`⚠️ "${name}" cannot use unit "${unit}".`);
    refreshUnitOptions();
    return;
  }

  const btn = document.getElementById('btnAddIng');
  btn.disabled = true;

  try {
    const response = await fetch('add_inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, amount: qty, unit, expiration_date })
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

async function removeInventoryIng(ingredientId) {
  try {
    const response = await fetch('remove_inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ingredient_id: ingredientId })
    });

    const data = await response.json();

    if (!data.success) {
      showToast('⚠️ ' + (data.message || 'Failed to remove ingredient'));
      return;
    }

    await loadAndRenderInventory();
  } catch (err) {
    console.error('Error removing inventory ingredient:', err);
    showToast('⚠️ Connection error');
  }
}

async function loadFlavors() {
  const res = await fetch('get_flavors.php');
  FLAVORS = await res.json();
}

function removeIng(idx) {
  searchIngs.splice(idx, 1);
  renderChips();
  runSearch();
}

function clearForm() {
  document.getElementById('ingName').value = '';
  document.getElementById('ingQty').value = '';
  document.getElementById('ingExpDate').value = '';
  document.getElementById('suggestions').style.display = 'none';
  document.getElementById('ingName').classList.remove('field-err');
  document.getElementById('ingQty').classList.remove('field-err');
  refreshUnitOptions();
  validateForm();
}

function buildFlavorButtons() {
 const container = document.getElementById('flavorButtons');
    if (!container) return;
    container.innerHTML = '';

    const allBtn = document.createElement('button');
    allBtn.textContent = 'All';
    allBtn.type = 'button';
    allBtn.onclick = () => setFlavor('', allBtn);
    container.appendChild(allBtn);

    FLAVORS.forEach(flavor => {
        const btn = document.createElement('button');
        btn.textContent = flavor;
        btn.type = 'button';
        btn.onclick = () => setFlavor(flavor, btn);
        container.appendChild(btn);
    });
    updateFlavorUI();
}

function setFlavor(flavor, clickedBtn) {
  if (flavor === '') {
        selectedFlavors = [];
    } else {
        const f = flavor.toLowerCase();
        const index = selectedFlavors.indexOf(f);

        if (index > -1) {
            selectedFlavors.splice(index, 1);
        } else {
            selectedFlavors.push(f);
        }
    }
    updateFlavorUI(); 
    runSearch();
}

function updateFlavorUI() {
    const buttons = document.querySelectorAll('#flavorButtons button');
    buttons.forEach(btn => {
        if (btn.textContent.toLowerCase() === 'all') {
            btn.classList.toggle('active', selectedFlavors.length === 0);
        } else {
            btn.classList.toggle('active', selectedFlavors.includes(btn.textContent.toLowerCase()));
        }
    });
}

// ── CHIP RENDERING ─────────────────────────────────────────────
function renderChips() {
  const sec  = document.getElementById('chipsSection');
  const list = document.getElementById('chipsList');
  const lbl  = document.getElementById('chipsLabel');

  if (!sec || !list || !lbl) return;

  list.innerHTML = '';

  const combined = [...serverInventory, ...searchIngs];

  if (!combined.length) {
    sec.style.display = 'none';
    return;
  }

  sec.style.display = 'block';
  lbl.textContent = `YOUR INGREDIENTS (${combined.length})`;

  combined.forEach((i, idx) => {
    const isServerItem = idx < serverInventory.length;

    const chip = document.createElement('div');
    chip.className = 'ing-chip';

    const removeHandler = isServerItem
      ? `removeInventoryIng(${i.ingredient_id})`
      : `removeIng(${idx - serverInventory.length})`;

    chip.innerHTML = `
      <span>${i.name}</span>
      ${i.amount ? `<span class="ing-chip-qty">${i.amount} ${i.unit}</span>` : ''}
      <button class="chip-rm" onclick="${removeHandler}" title="Remove">✕</button>
    `;

    list.appendChild(chip);
  });
}

// ── RECIPE MATCHING ────────────────────────────────────────────
function matchRecipes() {
  const userMap = getInvMap();
  const maxTime = parseInt(document.getElementById('timeRange')?.value || '9999', 10);
  const maxCalories = parseInt(document.getElementById('calRange')?.value || '999999', 10);

  return RECIPES
    .filter(recipe => {
      const timeOk = recipe.time == null || recipe.time <= maxTime;
      const calOk = recipe.calories == null || recipe.calories <= maxCalories;
      const recipeFlavors = Array.isArray(recipe.flavors)  ? recipe.flavors.map(f => f.toLowerCase()) : (recipe.flavors ? recipe.flavors.toLowerCase().split(',') : []);
      const flavorOk = selectedFlavors.length === 0 ||  selectedFlavors.some(f => recipeFlavors.includes(f.toLowerCase()));
      return flavorOk && timeOk && calOk;
      
    })
    .map(recipe => {
      let score = 0;
      
     const uniqueIngredients = [];
      const seenNames = new Set();

      recipe.ingredients.forEach(ri => {
        const lowerName = ri.name.toLowerCase();
        if (!seenNames.has(lowerName)) {
          seenNames.add(lowerName);
          uniqueIngredients.push(ri);
        }
      });

    /*  const details = recipe.ingredients.map(ri => {
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
      });*/
     const details = uniqueIngredients.map(ri => {
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
      
      const pct = uniqueIngredients.length
        ? Math.round((score / recipe.ingredients.length) * 100)
        : 0;

      return {
        recipe,
        pct,
        details,
        missing: details.filter(d => d.status === 'missing'),
        partial: details.filter(d => d.status === 'partial')
      };
    })
    .filter(r => r.pct > 0)
    .sort((a, b) => b.pct - a.pct);
}

// ── RESULTS ────────────────────────────────────────────────────
function runSearch() {
  const results = matchRecipes();
  const sec = document.getElementById('resultsSection');
  const empty = document.getElementById('resultsEmpty');
  const grid = document.getElementById('resultsGrid');
  const lbl = document.getElementById('resultsLabel');

  grid.innerHTML = '';

  const hasAnyIngredients = serverInventory.length > 0 || searchIngs.length > 0;

  if (!hasAnyIngredients) {
    sec.style.display = 'none';
    empty.style.display = 'block';
    return;
  }

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
    const allHaveTags = r.details.filter(d => d.status === 'have').map(d => `<span class="tag tag-have">${d.name}</span>`).join('');

    const detailRows = r.details.map(d => {
      const dc = d.status === 'have' ? 'dot-have' : d.status === 'partial' ? 'dot-partial' : 'dot-missing';
      const qt = d.status === 'partial'
        ? `${d.haveText}/${d.amount} ${d.unit}`
        : d.status === 'have'
          ? `${d.haveText}`
          : `${d.amount} ${d.unit}`;

      return `
        <div class="detail-row">
          <span class="detail-name">${d.name}</span>
          <span class="detail-right">
            <span class="detail-qty">${qt}</span>
            <span class="status-dot ${dc}"></span>
          </span>
        </div>
      `;
    }).join('');
    
    const flavorTags = (r.recipe.flavors || [])
    .map(f => `<span class="tag tag-flavor">${f}</span>`)
    .join('');
    
    const card = document.createElement('div');
    card.className = `recipe-card${complete ? ' complete' : ''}`;
    card.innerHTML = `
      <div class="card-top">
        <div class="card-name">${complete ? '✅ ' : ''}${r.recipe.name}</div>
        <span class="card-pct ${pc}">${r.pct}%</span>
      </div>

      <div class="card-info">
        <span class="recipe-calories">🔥 ${r.recipe.calories || '0'} kcal</span>
        <span class="recipe-time">⏱️ ${r.recipe.time || 0} min</span>
      </div>
      ${flavorTags ? `<div class="tags flavor-tags-container">${flavorTags}</div>` : ''}
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
      
      <div class="card-detail" id="detail-${r.recipe.id}">
        <div class="time-breakdown">
          <div>Prep time: ${r.recipe.prep_time || 0} min</div>
          <div>Cook time: ${r.recipe.cook_time || 0} min</div>
        </div>
        ${detailRows}
      </div>
      <a class="btn-view-recipe" href="recipe.php?id=${r.recipe.id}">
      Detailed Recipe
    </a>
    `;

    grid.appendChild(card);

    requestAnimationFrame(() => {
      setTimeout(() => {
        const bar = card.querySelector('.prog-bar');
        if (bar) bar.style.width = r.pct + '%';
      }, 60);
    });
  });
}

function toggleDetail(id) {
  const d = document.getElementById(`detail-${id}`);
  const btn = document.getElementById(`ct-${id}`);
  d.classList.toggle('open');
  btn.querySelector('svg').style.transform = d.classList.contains('open') ? 'rotate(180deg)' : '';
  btn.lastChild.textContent = d.classList.contains('open') ? ' Hide ingredients' : ' Show all ingredients';
}

function updateTimeValue() {
  const val = document.getElementById("timeRange").value;
  document.getElementById("timeValue").innerText = val;
  runSearch();
}

function updateCalValue() {
  const val = document.getElementById("calRange").value;
  document.getElementById("calValue").innerText = val;
  runSearch();
}

// ── INVENTORY ──────────────────────────────────────────────────
async function loadAndRenderInventory() {
  try {
    const response = await fetch('get_inventory.php', { cache: 'no-store' });
    if (!response.ok) {
      console.error('Failed to fetch inventory:', response.status);
      return;
    }

    const inventory = await response.json();
    serverInventory = sanitizeIngredientList(inventory);

    renderChips();
    runSearch();
  } catch (err) {
    console.error('Error loading inventory:', err);
  }
}

// ── INIT ───────────────────────────────────────────────────────
// ── INIT ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  initAutofill();
  refreshUnitOptions();
  validateForm();
  updateTimeValue();
  updateCalValue();

  try {
    await loadIngredients();
    await loadFlavors(); 
    buildFlavorButtons();
    buildCommonGrid();
  } catch (err) {
    console.error('Failed to load ingredients:', err);
  }

  try {
    await loadRecipes();
  } catch (err) {
    console.error('Failed to load recipes:', err);
  }

  try {
    await loadAndRenderInventory();
  } catch (err) {
    console.error('Failed to load inventory:', err);
  }
});

window.addEventListener('pageshow', async () => {
  try {
    await loadAndRenderInventory();
  } catch (err) {
    console.error('Pageshow inventory reload error:', err);
  }
});
