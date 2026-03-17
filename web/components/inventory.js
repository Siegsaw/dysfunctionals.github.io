// ============================================================
// PantryChef — Inventory Page Logic
// ============================================================

let items      = [];
let undoStack  = null;
let undoTimer  = null;

function save() {
  saveUserIng(items);
}

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
      </div>
      <div class="inv-ctrl">
        <button class="btn-qty btn-minus" onclick="changeQty(${ri}, -1)">−</button>
        <input class="qty-inp" type="number" value="${item.amount}" min="0"
          onchange="setQty(${ri}, this.value)"
          oninput="setQty(${ri}, this.value)">
        <button class="btn-qty btn-plus" onclick="changeQty(${ri}, 1)">+</button>
      </div>
      <button class="btn-rm" onclick="askRemove(${ri})" title="Remove">✕</button>`;
    list.appendChild(d);
  });
}

// ── QTY CONTROLS ─────────────────────────────────────────────
function changeQty(idx, delta) {
  const newVal = parseFloat(items[idx].amount) + delta;
  if (newVal < 0) return;
  items[idx].amount = newVal;
  save(); render();
}

function setQty(idx, val) {
  const n = parseFloat(val);
  if (isNaN(n) || n < 0) return;
  items[idx].amount = n;
  save();
}

// ── REMOVE + CONFIRM + UNDO ───────────────────────────────────
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
  const bar = document.getElementById('undoBar');
  document.getElementById('undoMsg').textContent = `"${removed.name}" removed.`;
  bar.classList.add('show');
  if (undoTimer) clearTimeout(undoTimer);
  undoTimer = setTimeout(() => { bar.classList.remove('show'); undoStack = null; }, 5000);
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

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  items = loadUserIng();

  document.getElementById('search').addEventListener('input', render);

  document.getElementById('overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('overlay')) {
      document.getElementById('overlay').classList.remove('show');
    }
  });

  render();
});
