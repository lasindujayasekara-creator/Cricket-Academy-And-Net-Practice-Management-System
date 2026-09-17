<?php
// Session Authentication API endpoint
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Get Logged in Session (GET /api/auth.php?action=me)
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Not authenticated."]);
        exit;
    }

    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];

    // Retrieve corresponding profile information
    $profile = null;
    if ($role === 'player') {
        $stmt = $pdo->prepare("SELECT p.*, u.username FROM players p JOIN users u ON p.id = u.id WHERE p.id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    } elseif ($role === 'coach') {
        $stmt = $pdo->prepare("SELECT c.*, u.username FROM coaches c JOIN users u ON c.id = u.id WHERE c.id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    }

    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $userId,
            "username" => $_SESSION['user']['username'],
            "role" => $role,
            "profile" => $profile
        ]
    ]);
    exit;
}

// 2. Login Endpoint (POST /api/auth.php)
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Username and password are required."]);
        exit;
    }

    // Query user credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify bcrypt password hash
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid username or password."]);
        exit;
    }

    // Bind PHP Session details
    $_SESSION['user'] = [
        "id" => $user['id'],
        "username" => $user['username'],
        "role" => $user['role']
    ];

    $profile = null;
    if ($user['role'] === 'player') {
        $stmt = $pdo->prepare("SELECT p.*, u.username FROM players p JOIN users u ON p.id = u.id WHERE p.id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
    } elseif ($user['role'] === 'coach') {
        $stmt = $pdo->prepare("SELECT c.*, u.username FROM coaches c JOIN users u ON c.id = u.id WHERE c.id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "user" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "role" => $user['role'],
            "profile" => $profile
        ]
    ]);
    exit;
}

// 3. Logout Endpoint (POST /api/auth.php?action=logout)
if ($method === 'POST' && $action === 'logout') {
    session_unset();
    session_destroy();
    
    // Clear Session cookies
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    echo json_encode(["success" => true, "message" => "Logged out successfully."]);
    exit;
}

http_response_code(400);
echo json_encode(["success" => false, "message" => "Invalid action request."]);
exit;
