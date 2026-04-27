<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - PantryChef</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="profile.css">
  <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
</head>
<body>
  <header>
    <div class="logo" onclick="location.href='index.php'">PantryChef</div>

    <nav class="h-nav">
      <button class="nav-btn" onclick="location.href='index.php'">Home</button>
      <button class="nav-btn" onclick="location.href='browse_recipes.php'">Browse Recipes</button>
      <button class="nav-btn" onclick="location.href='inventory.php'">User Ingredients</button>
    </nav>

    <div class="h-right">
      <a id="userBadge" href="profile.php"></a>
      <button class="btn-theme" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
      <button class="btn-signin" id="btnSignIn" onclick="location.href='login.php'">Sign In</button>
      <button class="btn-logout" id="btnLogout" onclick="doLogout()">Sign Out</button>
    </div>
  </header>

  <div class="toast" id="toast"></div>

  <main class="profile-page">
    <section class="profile-hero">
      <div class="profile-eyebrow">Account settings</div>
      <h1 class="profile-title">Your profile</h1>
      <p class="profile-subtitle">
        See your information, update it when needed, change your password, or permanently delete your account.
      </p>
    </section>

    <section class="profile-grid">
      <div class="profile-main">
        <div class="profile-card">
          <h2>Personal information</h2>
          <p class="card-desc">Manage the main details connected to your account.</p>

          <form id="profileForm">
            <div class="form-grid">
              <div class="form-group">
                <label for="profileUsername">Username</label>
                <input class="form-input" id="profileUsername" type="text" placeholder="Your username">
              </div>

              <div class="form-group">
                <label for="profileEmail">Email address</label>
                <input class="form-input" id="profileEmail" type="email" placeholder="you@example.com">
              </div>

              <div class="form-group full">
                <label for="profileMemberSince">Member since</label>
                <input class="form-input" id="profileMemberSince" type="text" readonly placeholder="Date placeholder">
              </div>
            </div>

            <div class="btn-row">
              <button type="submit" class="btn-primary">Save changes</button>
            </div>
          </form>
        </div>
                  <div class="profile-card">
          <h2>Allergens</h2>
          <p class="card-desc">Select ingredients you are allergic to so recipes can avoid them.</p>

          <form id="allergensForm">
            <div class="allergen-grid" id="allergenGrid"></div>

            <div class="btn-row">
              <button type="submit" class="btn-primary">Save allergens</button>
            </div>
          </form>
        </div>
      </div>
     
      <aside class="profile-side">
        <div class="profile-card">
          <h2>Security & account removal</h2>
          <p class="card-desc">Use these actions carefully.</p>

          <div class="btn-row">
            <button type="button" class="btn-secondary" onclick="togglePasswordBox()">Change password</button>
            <button type="button" class="btn-danger" onclick="toggleDeleteBox()">Delete profile</button>
          </div>

          <div id="passwordBox" class="hidden">
            <form id="passwordForm" style="margin-top:20px;">
              <div class="form-grid">
                <div class="form-group">
                  <label for="newPassword">New password</label>
                  <input class="form-input" id="newPassword" type="password" placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                  <label for="repeatPassword">Repeat new password</label>
                  <input class="form-input" id="repeatPassword" type="password" placeholder="Repeat the new password">
                </div>
              </div>

              <div class="btn-row">
                <button type="button" class="btn-secondary" onclick="togglePasswordBox()">Cancel</button>
                <button type="submit" class="btn-primary">Save new password</button>
              </div>
            </form>
          </div>

          <div id="deleteBox" class="danger-box hidden">
            <h3>Delete your profile?</h3>
            <p>
              This action is permanent and cannot be undone.
              Before deleting the profile, please confirm that you really want to do this.
            </p>

            <div class="btn-row">
              <button type="button" class="btn-secondary" onclick="toggleDeleteBox()">Keep profile</button>
              <button type="button" class="btn-danger" onclick="deleteProfileNow()">Yes, delete profile</button>
            </div>
          </div>
        </div>
      </aside>
    </section>
  </main>

  <script src="shared.js"></script>
  <script src="profile.js"></script>
</body>
</html>
