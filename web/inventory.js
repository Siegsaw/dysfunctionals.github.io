// ============================================================
// PantryChef — Inventory Page Logic
// ============================================================

const INVENTORY_DEFAULT_UNITS = ["g", "kg", "ml", "L", "cups", "tbsp", "tsp", "pcs"];
const INVENTORY_UNIT_RULES = {
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

let items     = [];
let undoStack = null;
let undoTimer = null;

function inventoryAllowedUnits(name) {
  return INVENTORY_UNIT_RULES[name] || INVENTORY_DEFAULT_UNITS;
}

function sanitizeInventory(entries) {
  return entries
    .map(entry => {
      const amount = Math.round((parseFloat(entry.amount) || 0) * 1000) / 1000;
      const unit = inventoryAllowedUnits(entry.name).includes(entry.unit)
        ? entry.unit
        : inventoryAllowedUnits(entry.name)[0];
      return amount > 0 ? { ...entry, amount, unit } : null;
    })
    .filter(Boolean);
}

function save() { saveUserIng(sanitizeInventory(items)); }

// ── RENDER ────────────────────────────────────────────────────
function render() {
  const q    = document.getElementById('search').value.toLowerCase();
  const list = document.getElementById('list');
  list.innerHTML = '';

  const filtered = items.filter(i => i.name.toLowerCase().includes(q));

  if (!filtered.length) {
    list.innerHTML = `<div class="empty">
      <span class="empty-ico">🥣</span>
      <p>${q ? 'No ingredients match your search.' : 'Your inventory is empty.'}</p>
    </div>`;
    return;
  }

  filtered.forEach(item => {
    const ri = items.indexOf(item);
    const d  = document.createElement('div');
    d.className = 'inv-item';
    d.innerHTML = `
      <div class="inv-info">
        <span class="inv-name">${item.name}</span>
        <span class="inv-unit">${item.unit}</span>
        ${item.expiration_date ? `<span class="item-exp">Exp: ${item.expiration_date}</span>` : ''}
      </div>

      <div class="inv-ctrl">
        <div class="inv-current" id="cur-${ri}">
          <span class="cur-label">In stock:</span>
          <input
            class="cur-val-inp"
            type="number"
            value="${item.amount}"
            min="0"
            step="any"
            title="Edit amount directly"
            onchange="setDirect(${ri}, this.value)"
            onblur="setDirect(${ri}, this.value)"
          >
        </div>

        <div class="inv-step">
          <button class="btn-qty btn-minus" onclick="applyStep(${ri}, -1)" title="Subtract">−</button>
          <input
            class="step-inp"
            id="step-${ri}"
            type="number"
            value="1"
            min="1"
            step="any"
            title="Amount to add or subtract"
          >
          <button class="btn-qty btn-plus" onclick="applyStep(${ri}, 1)" title="Add">+</button>
        </div>
      </div>

      <button class="btn-rm" onclick="askRemove(${ri})" title="Remove">✕</button>`;
    list.appendChild(d); // append first

    const curInp = d.querySelector('.cur-val-inp');
    if (curInp) {
      const resize = (el) => {
        el.style.width = Math.max(2.5, el.value.length + 0.8) + 'ch';
      };
      resize(curInp); // initial resize
      curInp.addEventListener('input', () => resize(curInp)); // resize on input
    }
  });
}

function applyStep(idx, direction) {
  const stepEl  = document.getElementById(`step-${idx}`);
  const step    = parseFloat(stepEl.value);

  if (isNaN(step) || step <= 0) {
    stepEl.classList.add('inp-err');
    setTimeout(() => stepEl.classList.remove('inp-err'), 1200);
    return;
  }

  const current = parseFloat(items[idx].amount) || 0;
  const newVal  = current + direction * step;

  if (newVal < 0) {
    stepEl.classList.add('inp-err');
    showToast(`⚠️ Can't go below 0. You only have ${current} ${items[idx].unit}.`);
    setTimeout(() => stepEl.classList.remove('inp-err'), 1200);
    return;
  }

  items[idx].amount = Math.round(newVal * 1000) / 1000;
  save();

  const curEl = document.getElementById(`cur-${idx}`);
  if (curEl) {
    const inp = curEl.querySelector('.cur-val-inp');
    inp.value = items[idx].amount;
    inp.style.width = Math.max(2.5, inp.value.length + 0.8) + 'ch';
  }
}

function sortInventoryByExpiry(items) {
  return [...items].sort((a, b) => {
    const aDate = a.expiration_date ? new Date(a.expiration_date) : null;
    const bDate = b.expiration_date ? new Date(b.expiration_date) : null;

    if (!aDate && !bDate) return 0;
    if (!aDate) return 1;
    if (!bDate) return -1;

    return aDate - bDate;
  });
}

function applyInventorySort() {
  const sortValue = document.getElementById('sortExpiry')?.value || 'default';

  let itemsToRender = [...inventoryItems];

  if (sortValue === 'expiry_asc') {
    itemsToRender = sortInventoryByExpiry(itemsToRender);
  }

  renderInventory(itemsToRender);
}

function setDirect(idx, val) {
  const n = parseFloat(val);
  const inp = document.getElementById(`cur-${idx}`)?.querySelector('.cur-val-inp');
  if (isNaN(n) || n < 0) {
    if (inp) { inp.classList.add('inp-err'); setTimeout(() => inp.classList.remove('inp-err'), 1200); inp.value = items[idx].amount; }
    return;
  }
  items[idx].amount = Math.round(n * 1000) / 1000;
  if (inp) {
    inp.value = items[idx].amount;
    inp.style.width = Math.max(2.5, inp.value.length + 0.8) + 'ch';
  }
  save();
}

function askRemove(idx) {
  document.getElementById('dlgMsg').textContent = `Remove "${items[idx].name}" from inventory?`;
  document.getElementById('overlay').classList.add('show');
  document.getElementById('btnYes').onclick = () => {
    document.getElementById('overlay').classList.remove('show');
    doRemove(idx);
  };
  document.getElementById('btnNo').onclick = () => {
    document.getElementById('overlay').classList.remove('show');
  };
}

function doRemove(idx) {
  const removed = items.splice(idx, 1)[0];
  save(); render();
  undoStack = { item: removed, idx };
  document.getElementById('undoMsg').textContent = `"${removed.name}" removed.`;
  document.getElementById('undoBar').classList.add('show');
  if (undoTimer) clearTimeout(undoTimer);
  undoTimer = setTimeout(() => {
    document.getElementById('undoBar').classList.remove('show');
    undoStack = null;
  }, 5000);
  showToast(`✓ "${removed.name}" removed.`);
}

function undoRemove() {
  if (!undoStack) return;
  items.splice(undoStack.idx, 0, undoStack.item);
  save(); render();
  document.getElementById('undoBar').classList.remove('show');
  if (undoTimer) clearTimeout(undoTimer);
  undoStack = null;
}

document.addEventListener('DOMContentLoaded', async () => {
  items = await sanitizeInventory(await loadUserIng());
  document.getElementById('search').addEventListener('input', render);
  document.getElementById('overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('overlay'))
      document.getElementById('overlay').classList.remove('show');
  });
  render();
});
