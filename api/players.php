<?php
// Players CRUD & Profile Management API
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit;
}

$currentUser = $_SESSION['user'];
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // --- 1. GET (Read Player) ---
    if ($method === 'GET') {
        if ($id > 0) {
            // Read single profile
            // Authorization Check: Players can only view their own profile, Admins & Coaches can view any
            if ($currentUser['role'] === 'player' && $currentUser['id'] !== $id) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied. You can only view your own profile."]);
                exit;
            }

            $stmt = $pdo->prepare("SELECT p.*, u.username FROM players p JOIN users u ON p.id = u.id WHERE p.id = ?");
            $stmt->execute([$id]);
            $player = $stmt->fetch();

            if (!$player) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Player profile not found."]);
                exit;
            }

            echo json_encode(["success" => true, "data" => $player]);
            exit;
        } else {
            // List players
            // Authorization: Only Admin and Coach can view the player list
            if ($currentUser['role'] === 'player') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden."]);
                exit;
            }

            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $query = "SELECT p.*, u.username FROM players p JOIN users u ON p.id = u.id";
            $params = [];

            if (!empty($search)) {
                $query .= " WHERE p.name LIKE ? OR p.email LIKE ? OR p.category LIKE ?";
                $wildcard = "%$search%";
                $params = [$wildcard, $wildcard, $wildcard];
            }
            
            $query .= " ORDER BY p.name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $players = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $players]);
            exit;
        }
    }

    // --- 2. POST (Register Player - Admin Only) ---
    if ($method === 'POST') {
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden. Admin access required."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $category = isset($input['category']) ? trim($input['category']) : '';
        $address = isset($input['address']) ? trim($input['address']) : '';
        $performance_notes = isset($input['performance_notes']) ? trim($input['performance_notes']) : null;

        if (empty($username) || empty($password) || empty($name) || empty($email) || empty($phone) || empty($category) || empty($address)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "All fields (username, password, name, email, phone, category, address) are required."]);
            exit;
        }

        // Validate username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Username is already taken."]);
            exit;
        }

        // Validate email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM players WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email is already registered."]);
            exit;
        }

        // Begin transaction
        $pdo->beginTransaction();

        // 1. Insert user credentials (Bcrypt password)
        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'player')");
        $stmt->execute([$username, $hashedPass]);
        $newUserId = $pdo->lastInsertId();

        // 2. Insert player profile details
        $stmt = $pdo->prepare("INSERT INTO players (id, name, email, phone, address, category, performance_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$newUserId, $name, $email, $phone, $address, $category, $performance_notes]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Player registered successfully.", "id" => $newUserId]);
        exit;
    }

    // --- 3. PUT (Update Player details) ---
    if ($method === 'PUT') {
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Player ID parameter is required."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Action: Performance comments update (Admin or Coach only)
        if ($action === 'performance') {
            if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'coach') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied. Only coaches and admins can evaluate performance."]);
                exit;
            }

            $performance_notes = isset($input['performance_notes']) ? trim($input['performance_notes']) : '';
            $stmt = $pdo->prepare("UPDATE players SET performance_notes = ? WHERE id = ?");
            $stmt->execute([$performance_notes, $id]);

            echo json_encode(["success" => true, "message" => "Player performance evaluation notes updated."]);
            exit;
        }

        // Action: Complete details update (Admin or self player only)
        if ($currentUser['role'] === 'player' && $currentUser['id'] !== $id) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Access denied. You can only update your own profile."]);
            exit;
        }

        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $category = isset($input['category']) ? trim($input['category']) : '';
        $address = isset($input['address']) ? trim($input['address']) : '';
        $performance_notes = isset($input['performance_notes']) ? trim($input['performance_notes']) : null;

        if (empty($name) || empty($email) || empty($phone) || empty($category) || empty($address)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "All fields (name, email, phone, category, address) are required."]);
            exit;
        }

        // Email uniqueness check (excluding self)
        $stmt = $pdo->prepare("SELECT id FROM players WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email is already in use by another member."]);
            exit;
        }

        // Retrieve existing performance notes if user is a player to prevent overwriting
        if ($currentUser['role'] === 'player') {
            $stmt = $pdo->prepare("SELECT performance_notes FROM players WHERE id = ?");
            $stmt->execute([$id]);
            $performance_notes = $stmt->fetchColumn();
        }

        $stmt = $pdo->prepare("UPDATE players SET name = ?, email = ?, phone = ?, address = ?, category = ?, performance_notes = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $category, $performance_notes, $id]);

        echo json_encode(["success" => true, "message" => "Player profile updated successfully."]);
        exit;
    }

    // --- 4. DELETE (Remove Player - Admin Only) ---
    if ($method === 'DELETE') {
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Player ID parameter is required."]);
            exit;
        }

        // Delete from users table cascades deletion to player profile table due to FOREIGN KEY CONSTRAINT
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Player profile deleted successfully."]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Player profile not found."]);
        }
        exit;
    }

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
