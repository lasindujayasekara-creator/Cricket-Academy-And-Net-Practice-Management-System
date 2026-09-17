<?php
session_start();
require_once 'config/db.php';

// Auto-redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
$academyName = get_academy_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Player Sign Up - <?php echo $academyName; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/auth.css">
  <style>
    .auth-card {
      max-width: 580px; /* Slightly wider registration card */
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-icon">🏏</div>
        <h1><?php echo explode(' ', $academyName)[0]; ?><span><?php echo implode(' ', array_slice(explode(' ', $academyName), 1)); ?></span></h1>
        <p>Player Registration Portal</p>
      </div>

      <!-- Error Message Box -->
      <div id="authError" class="auth-error" style="display:none;"></div>

      <!-- Registration Form -->
      <form id="registerForm" autocomplete="off">
        <div class="form-grid">
          <div class="form-group">
            <label for="username"><i class="fa-solid fa-user"></i> Username *</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
          </div>

          <div class="form-group">
            <label for="password"><i class="fa-solid fa-lock"></i> Password *</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Choose a password" required>
          </div>

          <div class="form-group">
            <label for="name"><i class="fa-solid fa-id-card"></i> Full Name *</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
          </div>

          <div class="form-group">
            <label for="email"><i class="fa-solid fa-envelope"></i> Email Address *</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter email address" required>
          </div>

          <div class="form-group">
            <label for="phone"><i class="fa-solid fa-phone"></i> Phone Number *</label>
            <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter phone number" required>
          </div>

          <div class="form-group">
            <label for="category"><i class="fa-solid fa-star"></i> Player Category *</label>
            <select id="category" name="category" class="form-control" required>
              <option value="">Select category...</option>
              <option value="Batsman">Batsman</option>
              <option value="Bowler">Bowler</option>
              <option value="All-Rounder">All-Rounder</option>
              <option value="Wicketkeeper">Wicketkeeper</option>
            </select>
          </div>

          <div class="form-group-full">
            <label for="address"><i class="fa-solid fa-house"></i> Home Address *</label>
            <textarea id="address" name="address" class="form-control" rows="2" placeholder="Enter your home address" required></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" id="registerBtn" style="width: 100%; margin-top: 15px; padding: 14px;">
          <i class="fa-solid fa-user-plus"></i> Create Player Account
        </button>
      </form>

      <div class="auth-footer" style="margin-top: 25px;">
        <p>Already have an account? <a href="index.php" style="font-weight: 600; color: var(--accent-green);">Sign In</a></p>
        <p style="margin-top: 10px; font-size: 0.78rem;">&copy; 2026 <?php echo $academyName; ?></p>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = document.getElementById('registerBtn');
      const errorDiv = document.getElementById('authError');
      
      const payload = {
        username: document.getElementById('username').value.trim(),
        password: document.getElementById('password').value.trim(),
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        category: document.getElementById('category').value,
        address: document.getElementById('address').value.trim()
      };

      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';
      btn.disabled = true;
      errorDiv.style.display = 'none';

      try {
        const res = await fetch('api/register.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          btn.innerHTML = '<i class="fa-solid fa-check"></i> Account Created! Redirecting...';
          setTimeout(() => window.location.href = 'dashboard.php', 1000);
        } else {
          errorDiv.textContent = data.message || 'Registration failed. Please try again.';
          errorDiv.style.display = 'block';
          btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Create Player Account';
          btn.disabled = false;
        }
      } catch(err) {
        errorDiv.textContent = 'Server error. Please ensure XAMPP is running.';
        errorDiv.style.display = 'block';
        btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Create Player Account';
        btn.disabled = false;
      }
    });
  </script>
</body>
</html>
