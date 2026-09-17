<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$name     = isset($input['name']) ? trim($input['name']) : '';
$email    = isset($input['email']) ? trim($input['email']) : '';
$phone    = isset($input['phone']) ? trim($input['phone']) : '';
$category = isset($input['category']) ? trim($input['category']) : '';
$address  = isset($input['address']) ? trim($input['address']) : '';

if (empty($username) || empty($password) || empty($name) || empty($email) || empty($phone) || empty($category) || empty($address)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields marked with * are required."]);
    exit;
}

try {
    // 1. Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Username is already taken."]);
        exit;
    }

    // 2. Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM players WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Email is already registered."]);
        exit;
    }

    // 3. Insert user and player profiles in a transaction
    $pdo->beginTransaction();

    $hashedPass = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'player')");
    $stmt->execute([$username, $hashedPass]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO players (id, name, email, phone, address, category, performance_notes) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    $stmt->execute([$userId, $name, $email, $phone, $address, $category]);

    $pdo->commit();

    // 4. Auto-login the newly registered player
    $_SESSION['user'] = [
        "id" => $userId,
        "username" => $username,
        "role" => 'player'
    ];

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Logging you in...",
        "user" => [
            "id" => $userId,
            "username" => $username,
            "role" => 'player'
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to create account: " . $e->getMessage()]);
}
exit;
