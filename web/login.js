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

    <button type="button" class="btn-google" id="btnGoogle" onclick="googleSignIn()">
      <span class="g-icon"></span>
      <span id="gText">Continue with Google</span>
      <span class="g-spinner" id="gSpinner"></span>
    </button>

    <div class="divider">or</div>

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
          <button type="button" class="btn-eye" id="btnEye" onclick="togglePw('password','btnEye')">👁</button>
        </div>
        <span class="f-err" id="pwErr"></span>
      </div>
    </div>

    <button type="button" class="btn-submit" id="btnSubmit" onclick="doLogin()" disabled>
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
          <button type="button" class="btn-eye" id="btnEyeReg" onclick="togglePw('regPassword','btnEyeReg')">👁</button>
        </div>
        <span class="f-err" id="regPwErr"></span>
      </div>
      <div>
        <label class="f-label" for="regPasswordConf">Confirm Password</label>
        <div class="f-wrap">
          <input type="password" id="regPasswordConf" placeholder="Repeat your password"
            oninput="validateRegister()">
          <button type="button" class="btn-eye" id="btnEyeConf" onclick="togglePw('regPasswordConf','btnEyeConf')">👁</button>
        </div>
        <span class="f-err" id="regPwConfErr"></span>
      </div>
    </div>

    <button type="button" class="btn-submit" id="btnRegSubmit" onclick="doRegister()" disabled>
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
const email = document.getElementById('email').value.trim();
const pw = document.getElementById('password').value;
const response = await fetch('login_api.php', {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify({ email, password: pw })
});
const data = await response.json();
if (!data.success) {
document.getElementById('msg').textContent = data.message;
return;
}
location.href = 'index.php';
}


// ── REGISTER ──────────────────────────────────────────────────
async function doRegister() {
  const username = document.getElementById('regUsername').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const password = document.getElementById('regPassword').value;
  const confirm = document.getElementById('regPasswordConf').value;

  if (password !== confirm) return;

  const response = await fetch('register.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, email, password })
  });

  const data = await response.json();

  if (!data.success) {
    document.getElementById('msg').textContent = data.message;
    return;
  }

  location.href = 'index.php';
}


// ── GOOGLE OAUTH 2.0 ──────────────────────────────────────────
// Replace the Client ID below with your own from:
// console.cloud.google.com → APIs & Services → Credentials
const GOOGLE_CLIENT_ID = '1050170511740-mt281jgcf5kha02e91l8dic149nup4fl.apps.googleusercontent.com';

function googleSignIn() {
  const btn  = document.getElementById('btnGoogle');
  const text = document.getElementById('gText');
  const spin = document.getElementById('gSpinner');
  const msg  = document.getElementById('msg');

  // Guard — show error if Client ID not set yet
  if (GOOGLE_CLIENT_ID.startsWith('YOUR_CLIENT_ID')) {
    msg.style.color = 'var(--red)';
    msg.textContent = '⚠️ Paste your Google Client ID into login.js first.';
    return;
  }

  btn.disabled = true;
  text.textContent = 'Connecting…';
  spin.style.display = 'block';
  msg.textContent = '';

  // Initialize Google token client and request access token
  const client = google.accounts.oauth2.initTokenClient({
    client_id: GOOGLE_CLIENT_ID,
    scope: 'email profile',
    callback: (response) => {
      if (response.error) {
        spin.style.display = 'none';
        btn.disabled = false;
        text.textContent = 'Continue with Google';
        msg.style.color = 'var(--red)';
        msg.textContent = '⚠️ Google Sign-In failed. Please try again.';
        return;
      }

      // Fetch the user's profile info using the access token
      fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
        headers: { Authorization: `Bearer ${response.access_token}` }
      })
      .then(r => r.json())
      .then(user => {
        spin.style.display = 'none';

        // Save session
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userEmail', user.email);
        localStorage.setItem('userName', user.name || user.email.split('@')[0]);

        // If account doesn't exist yet, auto-register it
        const accounts = getAccounts();
        const key = user.email.toLowerCase();
        if (!accounts[key]) {
          accounts[key] = { username: user.name || user.email.split('@')[0], passwordHash: null, google: true };
          saveAccounts(accounts);
        }

        msg.style.color = '#16a34a';
        msg.textContent = `✓ Welcome, ${user.name}! Redirecting…`;
        setTimeout(() => location.href = 'index.php', 900);
      })
      .catch(() => {
        spin.style.display = 'none';
        btn.disabled = false;
        text.textContent = 'Continue with Google';
        msg.style.color = 'var(--red)';
        msg.textContent = '⚠️ Could not get user info. Try again.';
      });
    }
  });

  client.requestAccessToken({ prompt: 'select_account' });
}
