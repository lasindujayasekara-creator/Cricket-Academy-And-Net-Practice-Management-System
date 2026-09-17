<?php
// PHP Database Connection Configuration using PDO
// AUTO-SETUP: Creates database and tables automatically on first run

$host    = 'localhost';
$db      = 'cricket_academy_db';
$user    = 'root';
$pass    = ''; // Default XAMPP MySQL password is empty
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function db_error_response($message) {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(["success" => false, "message" => $message]);
    } else {
        die("
        <div style='font-family:Arial,sans-serif;padding:30px;background:#fef2f2;color:#991b1b;
                    border:1px solid #fca5a5;border-radius:10px;max-width:640px;margin:60px auto;'>
            <h3 style='margin-top:0;'>⚠️ Database Error</h3>
            <p>$message</p>
            <p style='font-size:0.9rem;'>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are both running in XAMPP Control Panel.</p>
        </div>");
    }
    exit;
}

try {
    // Step 1: Connect WITHOUT selecting a database to allow auto-creation
    $pdoInit = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);

    // Step 2: Create the database if it doesn't exist
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Step 3: Connect to the actual database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);

    // Step 4: Auto-install tables if they don't exist yet
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tableCheck === 0) {
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        
        // Execute the entire schema script in one go
        $pdo->exec($schema);

        // Set correct password hashes (password_hash cannot run in SQL)
        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ?")->execute([$hash]);
    }

} catch (\PDOException $e) {
    db_error_response("MySQL connection failed: " . $e->getMessage() . 
        "<br><br><strong>Fix:</strong> Open XAMPP Control Panel → Start MySQL.");
}

// Helper to retrieve the current Academy Name setting dynamically
function get_academy_name() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'academy_name'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val ? htmlspecialchars($val) : 'Cricket Academy';
    } catch (Exception $e) {
        return 'Cricket Academy';
    }
}
