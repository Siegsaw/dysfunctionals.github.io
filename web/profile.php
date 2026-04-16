<?php
require 'session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PantryChef — Profile</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="profile.css">
  <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
</head>
<body>
<header>
  <div class="logo" onclick="location.href='index.php'">PantryChef</div>
  <nav class="h-nav">
    <button class="nav-btn" onclick="location.href='index.php'">Home</button>
    <button class="nav-btn" onclick="location.href='inventory.php'">Inventory</button>
    <button class="nav-btn active">Profile</button>
  </nav>
  <div class="h-right">
    <span id="userBadge"></span>
    <button class="btn-theme" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
    <button class="btn-signin" id="btnSignIn" onclick="location.href='login.php'">Sign In</button>
    <button class="btn-profile" id="btnProfile" onclick="location.href='profile.php'">Profile</button>
    <button class="btn-logout" id="btnLogout" onclick="doLogout()">Sign Out</button>
  </div>
</header>

<div class="toast" id="toast"></div>

<main class="profile-page">
  <section class="profile-hero">
    <div>
      <p class="eyebrow">Account settings</p>
      <h1>Your profile</h1>
      <p class="profile-sub">See your information, update it when needed, change your password, or permanently delete your account.</p>
    </div>
    <div class="avatar-placeholder" id="avatarPlaceholder">PC</div>
  </section>

  <div class="profile-grid">
    <section class="profile-card">
      <div class="card-head">
        <div>
          <h2>Personal information</h2>
          <p>Manage the main details connected to your account.</p>
        </div>
        <span class="status-pill">Editable</span>
      </div>

      <form id="profileForm" class="form-grid">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Your username" required>
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" placeholder="you@example.com" required>
        </div>
        <div class="field field-full">
          <label for="createdAt">Member since</label>
          <input id="createdAt" type="text" placeholder="Date placeholder" disabled>
        </div>
        <div class="actions field-full">
          <button type="submit" class="btn-primary" id="saveProfileBtn">Save changes</button>
        </div>
      </form>
    </section>

    <aside class="side-stack">
      <section class="profile-card">
        <div class="card-head">
          <div>
            <h2>Quick overview</h2>
            <p>Helpful placeholders for future account features.</p>
          </div>
        </div>
        <div class="placeholder-list">
          <div class="placeholder-item">
            <span>Preferred language</span>
            <strong>Placeholder</strong>
          </div>
          <div class="placeholder-item">
            <span>Notification settings</span>
            <strong>Placeholder</strong>
          </div>
          <div class="placeholder-item">
            <span>Saved recipes</span>
            <strong>Placeholder</strong>
          </div>
        </div>
      </section>

      <section class="profile-card danger-card">
        <div class="card-head">
          <div>
            <h2>Security & account removal</h2>
            <p>Use these actions carefully.</p>
          </div>
        </div>
        <div class="danger-actions">
          <button class="btn-secondary" id="openPasswordBtn" type="button">Change password</button>
          <button class="btn-danger-solid" id="deleteProfileBtn" type="button">Delete profile</button>
        </div>
      </section>
    </aside>
  </div>
</main>

<div class="modal-backdrop" id="passwordModal">
  <div class="modal-card">
    <div class="card-head">
      <div>
        <h2>Change password</h2>
        <p>Enter your new password twice.</p>
      </div>
      <button class="icon-btn" type="button" onclick="closePasswordModal()">✕</button>
    </div>
    <form id="passwordForm" class="form-grid single-col">
      <div class="field">
        <label for="newPassword">New password</label>
        <input id="newPassword" type="password" placeholder="At least 6 characters" required>
      </div>
      <div class="field">
        <label for="repeatPassword">Repeat new password</label>
        <input id="repeatPassword" type="password" placeholder="Repeat the new password" required>
      </div>
      <div class="actions">
        <button class="btn-secondary" type="button" onclick="closePasswordModal()">Cancel</button>
        <button class="btn-primary" type="submit">Save new password</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="deleteModal">
  <div class="modal-card danger-card">
    <div class="card-head">
      <div>
        <h2>Delete your profile?</h2>
        <p>This action is permanent and cannot be undone.</p>
      </div>
    </div>
    <p class="confirm-copy">Before deleting the profile, please confirm that you really want to do this.</p>
    <div class="actions">
      <button class="btn-secondary" type="button" onclick="closeDeleteModal()">Keep profile</button>
      <button class="btn-danger-solid" type="button" onclick="deleteProfile()">Yes, delete profile</button>
    </div>
  </div>
</div>

<script src="shared.js"></script>
<script src="profile.js"></script>
</body>
</html>
