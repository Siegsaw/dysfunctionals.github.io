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

// ── STATE ──────────────────────────────────────────────────────
let searchIngs = [];

// ── INVENTORY MAP ──────────────────────────────────────────────
function getInvMap() {
  const inv = loadUserIng();
  const m = {};
  inv.forEach(i => { m[i.name.toLowerCase()] = parseFloat(i.amount) || 0; });
  return m;
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
    const val = this.value.trim();
    box.innerHTML = '';
    if (val.length < 2) { box.style.display = 'none'; return; }
    const hits = ALL_ING.filter(i => i.toLowerCase().includes(val.toLowerCase())).slice(0, 7);
    if (!hits.length) { box.style.display = 'none'; return; }
    hits.forEach((h, idx) => {
      const d = document.createElement('div');
      d.className = 'sug-item'; d.textContent = h; d.tabIndex = 0;
      d.addEventListener('mousedown', () => { input.value = h; box.style.display = 'none'; document.getElementById('ingQty').focus(); });
      d.addEventListener('keydown', e => {
        if (e.key === 'Enter') { input.value = h; box.style.display = 'none'; }
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

  document.getElementById('ingQty').addEventListener('keydown', e => {
    if (e.key === 'Enter') addIng();
  });
}

// ── ADD / REMOVE ───────────────────────────────────────────────
function addIng() {
  const name = document.getElementById('ingName').value.trim();
  const qty  = parseFloat(document.getElementById('ingQty').value) || null;
  const unit = document.getElementById('ingUnit').value;
  if (!name) { document.getElementById('ingName').focus(); return; }

  const ex = searchIngs.findIndex(i => i.name.toLowerCase() === name.toLowerCase());
  if (ex >= 0) { if (qty) searchIngs[ex].amount = (parseFloat(searchIngs[ex].amount) || 0) + qty; }
  else searchIngs.push({ name, amount: qty, unit });

  persistSearchIngs();
  clearForm();
  renderChips();
  runSearch();
}

function persistSearchIngs() {
  const saved = loadUserIng();
  searchIngs.forEach(si => {
    const ex = saved.findIndex(s => s.name.toLowerCase() === si.name.toLowerCase());
    if (ex >= 0) saved[ex].amount = si.amount ?? saved[ex].amount;
    else saved.push({ name: si.name, amount: si.amount, unit: si.unit });
  });
  saveUserIng(saved);
}

function clearForm() {
  document.getElementById('ingName').value = '';
  document.getElementById('ingQty').value  = '';
  document.getElementById('suggestions').style.display = 'none';
}

function removeIng(idx) {
  const removed = searchIngs.splice(idx, 1)[0];
  const saved = loadUserIng().filter(s => s.name.toLowerCase() !== removed.name.toLowerCase());
  saveUserIng(saved);
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
  const inv = getInvMap();
  const userQty = {};
  searchIngs.forEach(i => { userQty[i.name.toLowerCase()] = parseFloat(i.amount) || 0; });
  Object.entries(inv).forEach(([k, v]) => { userQty[k] = (userQty[k] || 0) + v; });
  const userNames = new Set(Object.keys(userQty));

  return RECIPES.map(recipe => {
    let score = 0;
    const details = recipe.ingredients.map(ri => {
      const key = ri.name.toLowerCase();
      if (!userNames.has(key)) return { ...ri, status: 'missing' };
      const have = userQty[key];
      if (ri.amount && have < ri.amount) { score += have / ri.amount; return { ...ri, status: 'partial', have }; }
      score += 1;
      return { ...ri, status: 'have' };
    });
    const pct = Math.round((score / recipe.ingredients.length) * 100);
    return { recipe, pct, details, missing: details.filter(d => d.status === 'missing'), partial: details.filter(d => d.status === 'partial') };
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
    const pTags = r.partial.map(p => `<span class="tag tag-partial">${p.name} (${p.have || '?'}/${p.amount} ${p.unit})</span>`).join('');
    const allHaveTags = r.details.map(d => `<span class="tag tag-have">${d.name}</span>`).join('');
    const detailRows = r.details.map(d => {
      const dc = d.status === 'have' ? 'dot-have' : d.status === 'partial' ? 'dot-partial' : 'dot-missing';
      const qt = d.status === 'partial' ? `${d.have || '?'}/${d.amount} ${d.unit}` : `${d.amount} ${d.unit}`;
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

// ── INIT ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  buildCommonGrid();
  initAutofill();

  // Restore saved ingredients for this user
  const saved = loadUserIng();
  if (saved.length > 0) {
    searchIngs = saved.map(i => ({ name: i.name, amount: i.amount, unit: i.unit }));
    renderChips();
    runSearch();
  }
});
