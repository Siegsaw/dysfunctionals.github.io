async function loadProfile() {
  try {
    const res = await fetch('get_profile.php', { cache: 'no-store' });
    const data = await res.json();

    if (!res.ok || data.success === false) {
      showToast(data.message || 'Failed to load profile');
      return;
    }

    document.getElementById('profileUsername').value = data.username || '';
    document.getElementById('profileEmail').value = data.email || '';
    document.getElementById('profileMemberSince').value = data.created_at || '—';
  } catch (err) {
    console.error(err);
    showToast('Failed to load profile');
  }
}

async function saveProfileChanges(e) {
  e.preventDefault();

  const username = document.getElementById('profileUsername').value.trim();
  const email = document.getElementById('profileEmail').value.trim();

  if (!username || !email) {
    showToast('Please fill in username and email');
    return;
  }

  try {
    const res = await fetch('update_profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, email })
    });

    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Profile updated successfully');
      await updateAuthUI();
    } else {
      showToast(data.message || 'Failed to update profile');
    }
  } catch (err) {
    console.error(err);
    showToast('Connection error');
  }
}

function togglePasswordBox() {
  document.getElementById('passwordBox').classList.toggle('hidden');
}

async function saveNewPassword(e) {
  e.preventDefault();

  const password = document.getElementById('newPassword').value;
  const repeat = document.getElementById('repeatPassword').value;

  if (!password || !repeat) {
    showToast('Please fill both password fields');
    return;
  }

  if (password.length < 6) {
    showToast('Password must be at least 6 characters');
    return;
  }

  if (password !== repeat) {
    showToast('Passwords do not match');
    return;
  }

  try {
    const res = await fetch('change_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ new_password: password, repeat_password: repeat })
    });

    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Password changed successfully');
      document.getElementById('newPassword').value = '';
      document.getElementById('repeatPassword').value = '';
      document.getElementById('passwordBox').classList.add('hidden');
    } else {
      showToast(data.message || 'Failed to change password');
    }
  } catch (err) {
    console.error(err);
    showToast('Connection error');
  }
}

function toggleDeleteBox() {
  document.getElementById('deleteBox').classList.toggle('hidden');
}

async function deleteProfileNow() {
  const confirmed = confirm('Are you sure you want to permanently delete your profile?');

  if (!confirmed) return;

  try {
    const res = await fetch('delete_profile.php', {
      method: 'POST'
    });

    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Profile deleted');
      setTimeout(() => {
        location.href = 'login.php';
      }, 1200);
    } else {
      showToast(data.message || 'Failed to delete profile');
    }
  } catch (err) {
    console.error(err);
    showToast('Connection error');
  }
}

async function loadAllergens() {
  try {
    const res = await fetch('get_allergens.php', { cache: 'no-store' });
    const data = await res.json();

    if (!data.success) {
      showToast(data.message || 'Failed to load allergens');
      return;
    }

    const grid = document.getElementById('allergenGrid');
    if (!grid) return;

    const selected = new Set(data.selected || []);
    grid.innerHTML = '';

    (data.allergens || []).forEach(allergen => {
      const label = document.createElement('label');
      label.className = 'allergen-tag';

      label.innerHTML = `
        <input type="checkbox" name="allergens" value="${allergen.name}" ${selected.has(allergen.name) ? 'checked' : ''}>
        <span>${allergen.name}</span>
      `;

      grid.appendChild(label);
    });
  } catch (err) {
    console.error('Failed to load allergens', err);
    showToast('Failed to load allergens');
  }
}

async function saveAllergens(e) {
  e.preventDefault();

  const allergens = [...document.querySelectorAll('#allergensForm input[name="allergens"]:checked')]
    .map(cb => cb.value);

  try {
    const res = await fetch('save_allergens.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ allergens })
    });

    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Allergens updated');
    } else {
      showToast(data.message || 'Failed to save allergens');
    }
  } catch (err) {
    console.error(err);
    showToast('Failed to save allergens');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await loadProfile();

  const profileForm = document.getElementById('profileForm');
  const passwordForm = document.getElementById('passwordForm');
  const allergensForm = document.getElementById('allergensForm');

  if (profileForm) {
    profileForm.addEventListener('submit', saveProfileChanges);
  }

  if (passwordForm) {
    passwordForm.addEventListener('submit', saveNewPassword);
  }
  if (allergensForm) {
  allergensForm.addEventListener('submit', saveAllergens);
}
  await loadAllergens();
});
