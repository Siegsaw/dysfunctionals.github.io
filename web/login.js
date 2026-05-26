// ============================================================
// PantryChef — Login / Register Logic
// ============================================================

// Redirect if already logged in
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const response = await fetch('auth_status.php');
    const auth = await response.json();

    if (auth.loggedIn) {
      location.href = 'index.php';
      return;
    }
  } catch (err) {
    console.error('Auth status check failed:', err);
  }

  renderLogin();
});

// ── RENDER LOGIN FORM ─────────────────────────────────────────
function renderLogin(prefillEmail = '') {
  document.getElementById('authCard').innerHTML = `
    <h2>Welcome back</h2>
    <p class="auth-sub">Sign in to access your pantry and recipes.</p>

    <div class="fields">
      <div>
        <label class="f-label" for="email">Email</label>
        <div class="f-wrap">
          <input type="email" id="email" placeholder="you@example.com"
            value="${prefillEmail}" oninput="clearErrors(); validateLogin()">
        </div>
        <span class="f-err" id="emailErr"></span>
      </div>
      <div>
        <label class="f-label" for="password">Password</label>
        <div class="f-wrap">
          <input type="password" id="password" placeholder="Your password"
            oninput="clearErrors(); validateLogin()">
          <button class="btn-eye" id="btnEye" onclick="togglePw('password','btnEye')">👁</button>
        </div>
        <span class="f-err" id="pwErr"></span>
      </div>
    </div>

    <button class="btn-submit" id="btnSubmit" onclick="doLogin()" disabled>
      Sign In <span class="btn-spinner" id="spinner"></span>
    </button>

    <div id="msg"></div>
    <div class="register-link">
      Don't have an account? <a onclick="renderRegister()">Register here</a>
    </div>`;

  validateLogin();
}

// ── RENDER REGISTER FORM ──────────────────────────────────────
function renderRegister() {
  document.getElementById('authCard').innerHTML = `
    <h2>Create account</h2>
    <p class="auth-sub">Join PantryChef — it's free.</p>

    <div class="fields">
      <div>
        <label class="f-label" for="regUsername">Username</label>
        <div class="f-wrap">
          <input type="text" id="regUsername" placeholder="Your display name"
            oninput="validateRegister()">
        </div>
        <span class="f-err" id="usernameErr"></span>
      </div>
      <div>
        <label class="f-label" for="regEmail">Email</label>
        <div class="f-wrap">
          <input type="email" id="regEmail" placeholder="you@example.com"
            oninput="validateRegister()">
        </div>
        <span class="f-err" id="regEmailErr"></span>
      </div>
      <div>
        <label class="f-label" for="regPassword">Password</label>
        <div class="f-wrap">
          <input type="password" id="regPassword" placeholder="At least 6 characters"
            oninput="validateRegister()">
          <button class="btn-eye" id="btnEyeReg" onclick="togglePw('regPassword','btnEyeReg')">👁</button>
        </div>
        <span class="f-err" id="regPwErr"></span>
      </div>
      <div>
        <label class="f-label" for="regPasswordConf">Confirm Password</label>
        <div class="f-wrap">
          <input type="password" id="regPasswordConf" placeholder="Repeat your password"
            oninput="validateRegister()">
          <button class="btn-eye" id="btnEyeConf" onclick="togglePw('regPasswordConf','btnEyeConf')">👁</button>
        </div>
        <span class="f-err" id="regPwConfErr"></span>
      </div>
    </div>

    <button class="btn-submit" id="btnRegSubmit" onclick="doRegister()" disabled>
      Create Account <span class="btn-spinner" id="regSpinner"></span>
    </button>

    <div id="msg"></div>
    <div class="register-link">
      Already have an account? <a onclick="renderLogin()">Sign in</a>
    </div>`;
}

// ── HELPERS ───────────────────────────────────────────────────
function togglePw(inputId, btnId) {
  const i = document.getElementById(inputId);
  const b = document.getElementById(btnId);
  i.type = i.type === 'password' ? 'text' : 'password';
  b.textContent = i.type === 'password' ? '👁' : '🙈';
}

function clearErrors() {
  ['email', 'password'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('err');
  });
}

// ── VALIDATION ────────────────────────────────────────────────
function validateLogin() {
  const email = document.getElementById('email')?.value.trim() || '';
  const pw    = document.getElementById('password')?.value || '';
  const eOk   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  const pOk   = pw.length >= 6;
  const btn   = document.getElementById('btnSubmit');
  if (btn) btn.disabled = !(eOk && pOk);
}

function validateRegister() {
  const u   = document.getElementById('regUsername')?.value.trim() || '';
  const e   = document.getElementById('regEmail')?.value.trim() || '';
  const p   = document.getElementById('regPassword')?.value || '';
  const c   = document.getElementById('regPasswordConf')?.value || '';
  const eOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
  const btn = document.getElementById('btnRegSubmit');
  if (btn) btn.disabled = !(u.length >= 2 && eOk && p.length >= 6 && p === c);
}

// ── LOGIN ─────────────────────────────────────────────────────
async function doLogin() {
  const btn = document.getElementById('btnSubmit');
  const spinner = document.getElementById('spinner');
  const msg = document.getElementById('msg');
  
  btn.disabled = true;
  spinner.style.display = 'inline-block';
  msg.textContent = '';

  try {
    const email = document.getElementById('email').value.trim();
    const pw = document.getElementById('password').value;
    
    const response = await fetch('login_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: pw })
    });
    
    const data = await response.json();
    
    if (!data.success) {
      msg.textContent = data.message;
      msg.style.color = 'var(--red)';
      btn.disabled = false;
      spinner.style.display = 'none';
      return;
    }
    
    msg.textContent = '✓ Logging in...';
    msg.style.color = '#16a34a';
    setTimeout(() => location.href = 'index.php', 600);
  } catch (err) {
    console.error('Login error:', err);
    msg.textContent = '⚠️ Connection error';
    msg.style.color = 'var(--red)';
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

// ── REGISTER ──────────────────────────────────────────────────
async function doRegister() {
  const btn = document.getElementById('btnRegSubmit');
  const spinner = document.getElementById('regSpinner');
  const msg = document.getElementById('msg');
  
  btn.disabled = true;
  spinner.style.display = 'inline-block';
  msg.textContent = '';

  try {
    const username = document.getElementById('regUsername').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    
    const response = await fetch('register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, email, password })
    });
    
    const data = await response.json();
    
    if (!data.success) {
      msg.textContent = data.message;
      msg.style.color = 'var(--red)';
      btn.disabled = false;
      spinner.style.display = 'none';
      return;
    }
    
    msg.textContent = '✓ Account created! Redirecting...';
    msg.style.color = '#16a34a';
    setTimeout(() => location.href = 'index.php', 600);
  } catch (err) {
    console.error('Register error:', err);
    msg.textContent = '⚠️ Connection error';
    msg.style.color = 'var(--red)';
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

// ── INIT ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  renderLogin();
});
