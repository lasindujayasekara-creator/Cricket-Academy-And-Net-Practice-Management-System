<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Verify authentication for settings changes
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$currentUser = $_SESSION['user'];

// 1. Get Settings (Publicly readable inside the application once logged in)
if ($method === 'GET') {
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(["success" => true, "settings" => $settings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to load settings: " . $e->getMessage()]);
    }
    exit;
}

// 2. Update Settings (Admin Only)
if ($method === 'POST') {
    if ($currentUser['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Forbidden: Admin privileges required."]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid payload."]);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($input as $key => $value) {
            $valStr = trim(strval($value));
            $stmt->execute([$key, $valStr, $valStr]);
        }
        
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Settings updated successfully."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update settings: " . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Method not allowed."]);
exit;
