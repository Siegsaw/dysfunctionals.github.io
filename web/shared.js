// ============================================================
// PantryChef — Shared JS
// Auth, theme toggle, toast — used by all pages
// ============================================================

// ── THEME (must run before DOM paints to avoid flash) ──────────
(function () {
  if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
  }
})();

function toggleTheme() {
  const dark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', dark ? 'dark' : 'light');
  document.querySelectorAll('.btn-theme').forEach(btn => {
    btn.textContent = dark ? '☀️' : '🌙';
  });
}

function initThemeBtn() {
  const dark = document.documentElement.classList.contains('dark');
  document.querySelectorAll('.btn-theme').forEach(btn => {
    btn.textContent = dark ? '☀️' : '🌙';
  });
}

// ── TOAST ──────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ── AUTH ───────────────────────────────────────────────────────
async function getAuthStatus() {
  const response = await fetch('auth_status.php');
  return await response.json();
}

async function doLogout() {
  await fetch('logout.php');
  location.href = 'login.php';
}


async function updateAuthUI() {
  const auth = await getAuthStatus();

  const signInBtn = document.getElementById('btnSignIn');
  const logoutBtn = document.getElementById('btnLogout');
  const badge     = document.getElementById('userBadge');

  if (signInBtn) signInBtn.style.display = on ? 'none'         : 'inline-flex';
  if (logoutBtn) logoutBtn.style.display = on ? 'inline-flex'  : 'none';
  if (badge) {
    badge.style.display = on ? 'inline-block' : 'none';
    badge.textContent = auth.username || auth.email || '';
  }
}

// ── PER-USER INGREDIENT STORAGE ────────────────────────────────
function ingKey() {
  const email = localStorage.getItem('userEmail') || 'guest';
  return 'ingredients_' + email.toLowerCase();
}

async function loadUserIng() {
  const response = await fetch('get_inventory.php');
  return await response.json();
}
async function saveUserIng(arr) {
  await fetch('save_inventory.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(arr)
  });
}

// ── ACCOUNT STORAGE ────────────────────────────────────────────
function getAccounts() {
  return JSON.parse(localStorage.getItem('pc_accounts') || '{}');
}

function saveAccounts(a) {
  localStorage.setItem('pc_accounts', JSON.stringify(a));
}

// Simple non-cryptographic hash (fine for localStorage demo)
function hashPw(pw) {
  let h = 0;
  for (let i = 0; i < pw.length; i++) {
    h = Math.imul(31, h) + pw.charCodeAt(i) | 0;
  }
  return h.toString(36);
}

// ── INIT ON EVERY PAGE ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initThemeBtn();
  updateAuthUI();
});
