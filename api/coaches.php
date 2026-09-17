<?php
// Coaches Staff Directory API
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
    // --- 1. GET (Read Coach & Schedule) ---
    if ($method === 'GET') {
        if ($id > 0) {
            // Check Schedule Action
            if ($action === 'schedule') {
                // Access constraint: Coaches can only view their own schedule, Admins can view any
                if ($currentUser['role'] === 'coach' && $currentUser['id'] !== $id) {
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "Access denied. You can only view your own schedule."]);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT b.*, p.name as player_name, p.category as player_category 
                    FROM bookings b 
                    JOIN players p ON b.player_id = p.id 
                    WHERE b.coach_id = ? 
                    ORDER BY b.booking_date ASC, b.time_slot ASC");
                $stmt->execute([$id]);
                $schedule = $stmt->fetchAll();

                echo json_encode(["success" => true, "data" => $schedule]);
                exit;
            }

            // Get single coach info
            $stmt = $pdo->prepare("SELECT c.*, u.username FROM coaches c JOIN users u ON c.id = u.id WHERE c.id = ?");
            $stmt->execute([$id]);
            $coach = $stmt->fetch();

            if (!$coach) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Coach not found."]);
                exit;
            }

            echo json_encode(["success" => true, "data" => $coach]);
            exit;
        } else {
            // Get all coaches list
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $query = "SELECT c.*, u.username FROM coaches c JOIN users u ON c.id = u.id";
            $params = [];

            if (!empty($search)) {
                $query .= " WHERE c.name LIKE ? OR c.email LIKE ? OR c.specialization LIKE ?";
                $wildcard = "%$search%";
                $params = [$wildcard, $wildcard, $wildcard];
            }

            $query .= " ORDER BY c.name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $coaches = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $coaches]);
            exit;
        }
    }

    // --- 2. POST (Register Coach - Admin Only) ---
    if ($method === 'POST') {
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $specialization = isset($input['specialization']) ? trim($input['specialization']) : '';

        if (empty($username) || empty($password) || empty($name) || empty($email) || empty($phone) || empty($specialization)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "All fields (username, password, name, email, phone, specialization) are required."]);
            exit;
        }

        // Username uniqueness check
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Username is already taken."]);
            exit;
        }

        // Email uniqueness check
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email is already registered."]);
            exit;
        }

        // Create transaction
        $pdo->beginTransaction();

        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'coach')");
        $stmt->execute([$username, $hashedPass]);
        $newUserId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO coaches (id, name, email, phone, specialization) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$newUserId, $name, $email, $phone, $specialization]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Coach registered successfully.", "id" => $newUserId]);
        exit;
    }

    // --- 3. PUT (Update Coach - Admin or Self Coach) ---
    if ($method === 'PUT') {
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Coach ID parameter is required."]);
            exit;
        }

        if ($currentUser['role'] === 'coach' && $currentUser['id'] !== $id) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Access denied. You can only update your own profile."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = isset($input['name']) ? trim($input['name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $specialization = isset($input['specialization']) ? trim($input['specialization']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($name) || empty($email) || empty($phone) || empty($specialization)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "All fields (name, email, phone, specialization) are required."]);
            exit;
        }

        // Email uniqueness check
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email is already in use by another coach."]);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE coaches SET name = ?, email = ?, phone = ?, specialization = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $specialization, $id]);

        if (!empty($password)) {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPass, $id]);
        }

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Coach profile updated successfully."]);
        exit;
    }

    // --- 4. DELETE (Remove Coach - Admin Only) ---
    if ($method === 'DELETE') {
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Coach ID parameter is required."]);
            exit;
        }

        // Delete user row cascades deletion to coach profile
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Coach staff profile removed."]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Coach profile not found."]);
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
