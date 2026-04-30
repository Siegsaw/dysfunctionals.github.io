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

// ── AUTH (Session-based via PHP) ──────────────────────────────
async function getAuthStatus() {
  const response = await fetch('auth_status.php');
  return await response.json();
}

async function doLogout() {
  await fetch('logout.php');
  showToast('✓ You have been successfully signed out.');
  setTimeout(() => location.href = 'login.php', 1800);
}

function toggleProfileMenu(event) {
  event.stopPropagation();

  const dropdown = document.getElementById('profileDropdown');
  const btn = document.getElementById('profileMenuBtn');

  if (!dropdown || !btn) return;

  dropdown.classList.toggle('show');
  btn.classList.toggle('open');
}

function closeProfileMenu() {
  const dropdown = document.getElementById('profileDropdown');
  const btn = document.getElementById('profileMenuBtn');

  if (dropdown) dropdown.classList.remove('show');
  if (btn) btn.classList.remove('open');
}

async function updateAuthUI() {
  const auth = await getAuthStatus();

  const signInBtn = document.getElementById('btnSignIn');
  const badge = document.getElementById('userBadge');
  const profileMenuWrap = document.getElementById('profileMenuWrap');

  if (signInBtn) {
    signInBtn.style.display = auth.loggedIn ? 'none' : 'inline-flex';
  }

  if (badge) {
    badge.style.display = 'inline-block';
    badge.textContent = auth.loggedIn
      ? (auth.username || auth.email || '')
      : 'Guest';
  }

  if (profileMenuWrap) {
    profileMenuWrap.style.display = auth.loggedIn ? 'block' : 'none';
  }
}

// ── PER-USER INGREDIENT STORAGE (API-based) ────────────────────
async function loadUserIng() {
  try {
    const response = await fetch('get_inventory.php');
    if (!response.ok) throw new Error('Failed to load inventory');
    return await response.json();
  } catch (err) {
    console.error('Error loading inventory:', err);
    return [];
  }
}

async function saveUserIng(arr) {
  try {
    await fetch('save_inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(arr)
    });
  } catch (err) {
    console.error('Error saving inventory:', err);
  }
}

// ── INIT ON EVERY PAGE ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initThemeBtn();
  updateAuthUI();

  document.addEventListener('click', function (event) {
    const wrap = document.getElementById('profileMenuWrap');

    if (!wrap) return;

    if (!wrap.contains(event.target)) {
      closeProfileMenu();
    }
  });
});
