<?php
// ONE-TIME SETUP: Update passwords in the database to the correct hash
// Run this ONCE from: http://localhost/cricketacademy/fix_passwords.php
// Then DELETE this file for security!
session_start();
require_once 'config/db.php';

$correct_hash = password_hash('password123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = ?");
$stmt->execute([$correct_hash]);

echo '<div style="font-family:Arial;padding:30px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;max-width:500px;margin:50px auto;">';
echo '<h3 style="color:#166534;">✅ Passwords Updated Successfully!</h3>';
echo '<p>All demo accounts now use: <code>password123</code></p>';
echo '<p>Affected rows: <strong>' . $stmt->rowCount() . '</strong></p>';
echo '<p style="margin-top:20px;"><a href="index.php" style="background:#10b981;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;">→ Go to Login</a></p>';
echo '<p style="color:#6b7280;font-size:0.85rem;margin-top:16px;">⚠️ IMPORTANT: Delete this file now for security!</p>';
echo '</div>';
