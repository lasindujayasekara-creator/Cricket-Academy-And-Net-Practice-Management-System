<?php
session_start();
require_once 'config/db.php';
$academyName = get_academy_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?php echo $academyName; ?> | Net Practice Management</title>
  <meta name="description" content="Login to <?php echo $academyName; ?> Net Practice Management System - HND Final Project">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* ===== RESET & BASE ===== */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --green:        #10b981;
      --green-hover:  #059669;
      --green-glow:   rgba(16,185,129,0.30);
      --green-soft:   rgba(16,185,129,0.10);
      --blue-accent:  #38bdf8;
      --bg-dark:      #060d1a;
      --bg-card:      rgba(15,23,42,0.75);
      --border:       rgba(255,255,255,0.08);
      --text-1:       #f1f5f9;
      --text-2:       #94a3b8;
      --text-3:       #4b6080;
      --radius-lg:    20px;
      --radius-md:    14px;
      --radius-sm:    10px;
      --transition:   0.3s cubic-bezier(0.4,0,0.2,1);
    }

    html, body { height: 100%; font-family: 'Outfit', sans-serif; background: var(--bg-dark); color: var(--text-1); overflow: hidden; }

    /* ===== SPLIT LAYOUT ===== */
    .login-wrap {
      display: grid;
      grid-template-columns: 1fr 480px;
      height: 100vh;
      overflow: hidden;
    }

    /* ===========================
       LEFT HERO PANEL
    =========================== */
    .hero-panel {
      position: relative;
      overflow: hidden;
      background: #070f1e;
    }

    /* Background image */
    .hero-bg {
      position: absolute; inset: 0;
      background: url('img/bg.jpg') center center / cover no-repeat;
      transform: scale(1.06);
      animation: slowZoom 20s ease-in-out infinite alternate;
    }
    @keyframes slowZoom {
      from { transform: scale(1.06); }
      to   { transform: scale(1.12); }
    }

    /* Dark gradient overlay */
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(
        135deg,
        rgba(6,13,26,0.72) 0%,
        rgba(4,30,20,0.65) 50%,
        rgba(6,13,26,0.80) 100%
      );
    }

    /* Grid line texture */
    .hero-grid {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(16,185,129,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.04) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: gridDrift 30s linear infinite;
    }
    @keyframes gridDrift {
      from { transform: translate(0,0); }
      to   { transform: translate(50px,50px); }
    }

    /* Floating particle dots */
    .particles { position: absolute; inset: 0; pointer-events: none; }
    .particle {
      position: absolute;
      width: 4px; height: 4px;
      border-radius: 50%;
      background: var(--green);
      animation: floatUp linear infinite;
      opacity: 0;
    }
    @keyframes floatUp {
      0%   { opacity: 0; transform: translateY(0) scale(0); }
      10%  { opacity: 0.6; }
      90%  { opacity: 0.3; }
      100% { opacity: 0; transform: translateY(-120px) scale(1.5); }
    }

    /* Hero content */
    .hero-content {
      position: relative; z-index: 2;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
      padding: 44px 48px;
    }

    /* Top badge row */
    .hero-topbar {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(16,185,129,0.12);
      border: 1px solid rgba(16,185,129,0.25);
      padding: 6px 16px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--green);
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }
    .hero-badge .dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--green);
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%,100% { box-shadow: 0 0 0 0 var(--green-glow); }
      50%      { box-shadow: 0 0 0 6px transparent; }
    }

    /* Main hero text */
    .hero-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 20px 0;
    }
    .hero-icon {
      font-size: 4.5rem;
      margin-bottom: 20px;
      filter: drop-shadow(0 0 20px var(--green-glow));
      animation: iconFloat 4s ease-in-out infinite;
    }
    @keyframes iconFloat {
      0%,100% { transform: translateY(0); }
      50%      { transform: translateY(-8px); }
    }
    .hero-title {
      font-size: clamp(2rem, 3.5vw, 2.8rem);
      font-weight: 900;
      line-height: 1.1;
      letter-spacing: -0.03em;
      margin-bottom: 16px;
    }
    .hero-title .line-accent { color: var(--green); }
    .hero-subtitle {
      font-size: 1rem;
      color: var(--text-2);
      line-height: 1.7;
      max-width: 380px;
      margin-bottom: 36px;
      font-family: 'Inter', sans-serif;
      font-weight: 400;
    }

    /* Feature pills */
    .hero-features {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.88rem;
      color: var(--text-2);
      font-family: 'Inter', sans-serif;
    }
    .feature-icon {
      width: 34px; height: 34px;
      border-radius: 10px;
      background: rgba(16,185,129,0.10);
      border: 1px solid rgba(16,185,129,0.18);
      display: flex; align-items: center; justify-content: center;
      color: var(--green);
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    /* Bottom stats bar */
    .hero-stats {
      display: flex;
      gap: 32px;
      padding-top: 28px;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .stat-item { text-align: left; }
    .stat-num {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--green);
      line-height: 1;
    }
    .stat-label {
      font-size: 0.72rem;
      color: var(--text-3);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 4px;
      font-family: 'Inter', sans-serif;
    }

    /* ===========================
       RIGHT LOGIN PANEL
    =========================== */
    .login-panel {
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px 44px;
      background: rgba(9,16,32,0.97);
      border-left: 1px solid var(--border);
      overflow-y: auto;
      overflow-x: hidden;
    }

    /* Subtle glow top-right */
    .login-panel::before {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 240px; height: 240px;
      background: radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .login-panel::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -60px;
      width: 200px; height: 200px;
      background: radial-gradient(circle, rgba(56,189,248,0.07) 0%, transparent 70%);
      pointer-events: none;
    }

    /* Slide-in animation */
    .login-inner {
      position: relative; z-index: 1;
      animation: slideInRight 0.6s cubic-bezier(0.4,0,0.2,1) both;
    }
    @keyframes slideInRight {
      from { opacity: 0; transform: translateX(30px); }
      to   { opacity: 1; transform: translateX(0); }
    }

    /* HND Banner */
    .hnd-banner {
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(56,189,248,0.06) 100%);
      border: 1px solid rgba(16,185,129,0.18);
      border-radius: var(--radius-md);
      padding: 10px 16px;
      margin-bottom: 32px;
    }
    .hnd-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--green), #0ea5e9);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    .hnd-text { flex: 1; }
    .hnd-text strong {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-1);
      letter-spacing: 0.01em;
    }
    .hnd-text span {
      font-size: 0.72rem;
      color: var(--text-2);
      font-family: 'Inter', sans-serif;
    }
    .hnd-badge {
      font-size: 0.65rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--green);
      background: rgba(16,185,129,0.12);
      border: 1px solid rgba(16,185,129,0.2);
      padding: 3px 8px;
      border-radius: 5px;
    }

    /* Section headings */
    .login-heading {
      margin-bottom: 28px;
    }
    .login-heading h1 {
      font-size: 1.75rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.2;
      color: var(--text-1);
    }
    .login-heading h1 span { color: var(--green); }
    .login-heading p {
      margin-top: 8px;
      font-size: 0.88rem;
      color: var(--text-2);
      font-family: 'Inter', sans-serif;
    }

    /* Error / Success message */
    #authError {
      display: none;
      background: rgba(239,68,68,0.09);
      border: 1px solid rgba(239,68,68,0.35);
      color: #fca5a5;
      padding: 11px 14px;
      border-radius: var(--radius-sm);
      font-size: 0.83rem;
      margin-bottom: 18px;
      display: none;
      align-items: center;
      gap: 8px;
      animation: shake 0.35s ease;
    }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25%      { transform: translateX(-6px); }
      75%      { transform: translateX(6px); }
    }

    /* Form */
    .form-group { margin-bottom: 20px; }
    .form-label {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-2);
      margin-bottom: 8px;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }
    .form-label i { color: var(--green); font-size: 0.8rem; }

    .input-wrap { position: relative; }
    .form-input {
      width: 100%;
      background: rgba(15,23,42,0.6);
      border: 1.5px solid rgba(255,255,255,0.07);
      padding: 13px 16px;
      border-radius: var(--radius-sm);
      color: var(--text-1);
      font-family: 'Outfit', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: all var(--transition);
    }
    .form-input::placeholder { color: var(--text-3); }
    .form-input:focus {
      border-color: var(--green);
      background: rgba(16,185,129,0.04);
      box-shadow: 0 0 0 3px rgba(16,185,129,0.12);
    }
    .form-input:focus + .input-icon,
    .input-icon { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); }

    /* Toggle password */
    .btn-toggle-pw {
      position: absolute; right: 14px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--text-3);
      cursor: pointer;
      padding: 4px;
      transition: color var(--transition);
      font-size: 0.85rem;
    }
    .btn-toggle-pw:hover { color: var(--text-2); }

    /* Remember me row */
    .form-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }
    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.83rem;
      color: var(--text-2);
      cursor: pointer;
      user-select: none;
      font-family: 'Inter', sans-serif;
    }
    .checkbox-label input[type="checkbox"] {
      width: 16px; height: 16px;
      accent-color: var(--green);
      cursor: pointer;
    }

    /* Submit button */
    .btn-login {
      width: 100%;
      padding: 14px 20px;
      background: linear-gradient(135deg, var(--green) 0%, #0ea5e9 100%);
      border: none;
      border-radius: var(--radius-sm);
      color: #fff;
      font-family: 'Outfit', sans-serif;
      font-size: 0.97rem;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.02em;
      transition: all var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      position: relative;
      overflow: hidden;
    }
    .btn-login::before {
      content: '';
      position: absolute; inset: 0;
      background: rgba(255,255,255,0.08);
      opacity: 0;
      transition: opacity var(--transition);
    }
    .btn-login:hover::before { opacity: 1; }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(16,185,129,0.4), 0 4px 12px rgba(14,165,233,0.25);
    }
    .btn-login:active { transform: translateY(0); }
    .btn-login:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 24px 0;
      color: var(--text-3);
      font-size: 0.75rem;
      font-family: 'Inter', sans-serif;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Register link */
    .register-link {
      text-align: center;
      font-size: 0.85rem;
      color: var(--text-2);
      font-family: 'Inter', sans-serif;
    }
    .register-link a {
      color: var(--green);
      font-weight: 600;
      text-decoration: none;
      transition: color var(--transition);
    }
    .register-link a:hover { color: #34d399; text-decoration: underline; }

    /* Footer */
    .login-footer {
      margin-top: 32px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }
    .footer-copy {
      font-size: 0.72rem;
      color: var(--text-3);
      font-family: 'Inter', sans-serif;
    }
    .footer-tags {
      display: flex;
      gap: 6px;
    }
    .footer-tag {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 3px 8px;
      border-radius: 5px;
    }
    .tag-hnd  { background: rgba(16,185,129,0.10); color: var(--green); border: 1px solid rgba(16,185,129,0.2); }
    .tag-year { background: rgba(56,189,248,0.10); color: var(--blue-accent); border: 1px solid rgba(56,189,248,0.2); }

    /* ===========================
       RESPONSIVE – Mobile stack
    =========================== */
    @media (max-width: 860px) {
      html, body { overflow: auto; }
      .login-wrap { grid-template-columns: 1fr; height: auto; }
      .hero-panel { height: 300px; }
      .hero-content { padding: 28px 28px; }
      .hero-main { justify-content: flex-end; }
      .hero-icon { font-size: 2.5rem; margin-bottom: 10px; }
      .hero-title { font-size: 1.6rem; margin-bottom: 8px; }
      .hero-subtitle, .hero-features, .hero-stats { display: none; }
      .login-panel { padding: 36px 28px; }
    }
    @media (max-width: 480px) {
      .hero-panel { height: 220px; }
      .login-panel { padding: 28px 20px; }
      .hnd-banner { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- ══════════════ LEFT: HERO PANEL ══════════════ -->
  <div class="hero-panel">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-grid"></div>

    <!-- Animated Particles -->
    <div class="particles" id="particles"></div>

    <div class="hero-content">

      <!-- Top badge -->
      <div class="hero-topbar">
        <div class="hero-badge">
          <span class="dot"></span>
          HND Final Project — 2026
        </div>
      </div>

      <!-- Main text -->
      <div class="hero-main">
        <div class="hero-icon">🏏</div>
        <h2 class="hero-title">
          Net Practice<br>
          <span class="line-accent">Management</span><br>
          System
        </h2>
        <p class="hero-subtitle">
          A comprehensive digital platform to manage cricket academy training sessions, player attendance, bookings, and performance analytics.
        </p>

        <div class="hero-features">
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <span>Smart Session Scheduling & Attendance Tracking</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-users"></i></div>
            <span>Player Registration & Profile Management</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
            <span>Real-time Reports & Performance Analytics</span>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <span>Role-based Access Control (Admin / Player)</span>
          </div>
        </div>
      </div>

      <!-- Bottom stats -->
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-num">100%</div>
          <div class="stat-label">PHP & MySQL</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">RESTful</div>
          <div class="stat-label">API Architecture</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">HND</div>
          <div class="stat-label">Final Project</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══════════════ RIGHT: LOGIN PANEL ══════════════ -->
  <div class="login-panel">
    <div class="login-inner">

      <!-- HND Project Banner -->
      <div class="hnd-banner">
        <div class="hnd-icon">🎓</div>
        <div class="hnd-text">
          <strong>Higher National Diploma (HND)</strong>
          <span><?php echo htmlspecialchars($academyName); ?> — Net Practice Management System</span>
        </div>
        <span class="hnd-badge">Final&nbsp;Project</span>
      </div>

      <!-- Welcome heading -->
      <div class="login-heading">
        <h1>Welcome <span>Back</span> 👋</h1>
        <p>Sign in to access your academy portal and manage training sessions.</p>
      </div>

      <!-- Error box -->
      <div id="authError" style="display:none;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span id="authErrorMsg">Login failed. Please try again.</span>
      </div>

      <!-- Login Form -->
      <form id="loginForm" autocomplete="off" novalidate>

        <div class="form-group">
          <label for="username" class="form-label">
            <i class="fa-solid fa-user"></i> Username
          </label>
          <div class="input-wrap">
            <input
              type="text"
              id="username"
              name="username"
              class="form-input"
              placeholder="Enter your username"
              autocomplete="username"
              required>
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fa-solid fa-lock"></i> Password
          </label>
          <div class="input-wrap">
            <input
              type="password"
              id="password"
              name="password"
              class="form-input"
              placeholder="Enter your password"
              autocomplete="current-password"
              style="padding-right: 44px;"
              required>
            <button type="button" id="togglePassword" class="btn-toggle-pw" aria-label="Toggle password">
              <i class="fa-solid fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="form-row">
          <label class="checkbox-label">
            <input type="checkbox" id="rememberMe">
            Remember me
          </label>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <i class="fa-solid fa-right-to-bracket"></i>
          Sign In to Portal
        </button>

      </form>

      <div class="divider">OR</div>

      <div class="register-link">
        New Player? <a href="register.php">Create an account</a>
      </div>

      <!-- Footer -->
      <div class="login-footer">
        <span class="footer-copy">&copy; 2026 <?php echo htmlspecialchars($academyName); ?>. All rights reserved.</span>
        <div class="footer-tags">
          <span class="footer-tag tag-hnd">HND</span>
          <span class="footer-tag tag-year">2026</span>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ======================================================
     JAVASCRIPT
====================================================== -->
<script>
  /* ── Particle generator ── */
  (function() {
    const container = document.getElementById('particles');
    const COUNT = 22;
    for (let i = 0; i < COUNT; i++) {
      const p = document.createElement('span');
      p.className = 'particle';
      const size = Math.random() * 4 + 2;
      p.style.cssText = `
        left: ${Math.random() * 100}%;
        bottom: ${Math.random() * 30}%;
        width: ${size}px;
        height: ${size}px;
        animation-duration: ${6 + Math.random() * 10}s;
        animation-delay: ${Math.random() * 8}s;
        opacity: ${0.3 + Math.random() * 0.5};
      `;
      container.appendChild(p);
    }
  })();

  /* ── Auto-redirect if already logged in ── */
  fetch('api/auth.php?action=me')
    .then(r => r.json())
    .then(data => {
      if (data.success) window.location.href = 'dashboard.php';
    }).catch(() => {});

  /* ── Toggle password visibility ── */
  document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    const isHidden = input.type === 'password';
    input.type   = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
  });

  /* ── Show/hide error helper ── */
  function showError(msg) {
    const box = document.getElementById('authError');
    document.getElementById('authErrorMsg').textContent = msg;
    box.style.display = 'flex';
    // re-trigger shake animation
    box.style.animation = 'none';
    box.offsetHeight; // reflow
    box.style.animation = '';
  }
  function hideError() {
    document.getElementById('authError').style.display = 'none';
  }

  /* ── Login form submit ── */
  document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    hideError();

    const btn      = document.getElementById('loginBtn');
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    if (!username || !password) {
      showError('Please enter both username and password.');
      return;
    }

    btn.innerHTML  = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in…';
    btn.disabled   = true;

    try {
      const res  = await fetch('api/auth.php?action=login', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ username, password })
      });
      const data = await res.json();

      if (data.success) {
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Success! Redirecting…';
        btn.style.background = 'linear-gradient(135deg,#059669,#0284c7)';
        setTimeout(() => window.location.href = 'dashboard.php', 600);
      } else {
        showError(data.message || 'Invalid credentials. Please try again.');
        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal';
        btn.disabled  = false;
      }
    } catch (err) {
      showError('Server error. Please ensure XAMPP is running.');
      btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal';
      btn.disabled  = false;
    }
  });

  /* ── Input focus micro-interactions ── */
  document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', () => {
      input.closest('.input-wrap').style.transform = 'scale(1.01)';
    });
    input.addEventListener('blur', () => {
      input.closest('.input-wrap').style.transform = '';
    });
  });
</script>

</body>
</html>
