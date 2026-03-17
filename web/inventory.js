// ============================================================
// PantryChef — Inventory Page Logic
// ============================================================

let items     = [];
let undoStack = null;
let undoTimer = null;

function save() { saveUserIng(items); }

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
        <!-- Current total amount (editable) -->
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

        <!-- Step controls: − [step input] + -->
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
    list.appendChild(d);

    // Dynamically resize the "In stock" input to fit its content
    const curInp = d.querySelector('.cur-val-inp');
    if (curInp) {
      const resize = (el) => { el.style.width = Math.max(2, el.value.length + 0.5) + 'ch'; };
      resize(curInp);
      curInp.addEventListener('input', () => resize(curInp));
    }
  });
}

// ── APPLY STEP ────────────────────────────────────────────────
// Reads the step input, adds or subtracts from current amount.
function applyStep(idx, direction) {
  const stepEl  = document.getElementById(`step-${idx}`);
  const step    = parseFloat(stepEl.value);

  // Validate step
  if (isNaN(step) || step <= 0) {
    stepEl.classList.add('inp-err');
    setTimeout(() => stepEl.classList.remove('inp-err'), 1200);
    return;
  }

  const current = parseFloat(items[idx].amount) || 0;
  const newVal  = current + direction * step;

  // Prevent negative amounts
  if (newVal < 0) {
    stepEl.classList.add('inp-err');
    showToast(`⚠️ Can't go below 0. You only have ${current} ${items[idx].unit}.`);
    setTimeout(() => stepEl.classList.remove('inp-err'), 1200);
    return;
  }

  items[idx].amount = Math.round(newVal * 1000) / 1000; // avoid floating point noise
  save();

  // Update just the displayed amount without full re-render (smooth UX)
  const curEl = document.getElementById(`cur-${idx}`);
  if (curEl) {
    const inp = curEl.querySelector('.cur-val-inp');
    inp.value = items[idx].amount;
    inp.style.width = Math.max(2, inp.value.length + 0.5) + 'ch';
  }
}

// ── SET DIRECTLY ──────────────────────────────────────────────
// Called when user edits the "In stock" field directly.
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
    inp.style.width = Math.max(2, inp.value.length + 0.5) + 'ch';
  }
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

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  items = loadUserIng();
  document.getElementById('search').addEventListener('input', render);
  document.getElementById('overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('overlay'))
      document.getElementById('overlay').classList.remove('show');
  });
  render();
});
