<?php
// Bookings & Double Booking Prevention API
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
    // --- 1. GET (Read Bookings) ---
    if ($method === 'GET') {
        if ($id > 0) {
            // View single booking
            $stmt = $pdo->prepare("SELECT b.*, p.name as player_name, p.email as player_email, p.phone as player_phone, p.category as player_category, c.name as coach_name 
                FROM bookings b 
                JOIN players p ON b.player_id = p.id 
                LEFT JOIN coaches c ON b.coach_id = c.id 
                WHERE b.id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Booking not found."]);
                exit;
            }

            // Role constraints
            if ($currentUser['role'] === 'player' && $booking['player_id'] !== $currentUser['id']) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied."]);
                exit;
            }
            if ($currentUser['role'] === 'coach' && $booking['coach_id'] !== $currentUser['id']) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied."]);
                exit;
            }

            echo json_encode(["success" => true, "data" => $booking]);
            exit;
        } else {
            // View bookings list with filters
            $player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
            $coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;
            $booking_date = isset($_GET['booking_date']) ? $_GET['booking_date'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $net_no = isset($_GET['net_no']) ? intval($_GET['net_no']) : 0;

            $query = "SELECT b.*, p.name as player_name, p.phone as player_phone, c.name as coach_name 
                FROM bookings b 
                JOIN players p ON b.player_id = p.id 
                LEFT JOIN coaches c ON b.coach_id = c.id 
                WHERE 1=1";
            $params = [];

            // Role visibility rules
            if ($currentUser['role'] === 'player') {
                $query .= " AND b.player_id = ?";
                $params[] = $currentUser['id'];
            } elseif ($currentUser['role'] === 'coach') {
                $query .= " AND b.coach_id = ?";
                $params[] = $currentUser['id'];
            } else {
                // Admin can query specifics
                if ($player_id > 0) {
                    $query .= " AND b.player_id = ?";
                    $params[] = $player_id;
                }
                if ($coach_id > 0) {
                    $query .= " AND b.coach_id = ?";
                    $params[] = $coach_id;
                }
            }

            if (!empty($booking_date)) {
                $query .= " AND b.booking_date = ?";
                $params[] = $booking_date;
            }
            if (!empty($status)) {
                $query .= " AND b.status = ?";
                $params[] = $status;
            }
            if ($net_no > 0) {
                $query .= " AND b.net_no = ?";
                $params[] = $net_no;
            }

            $query .= " ORDER BY b.booking_date DESC, b.time_slot ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $bookings]);
            exit;
        }
    }

    // --- 2. POST (Book Practice Session) ---
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $booking_date = isset($input['booking_date']) ? trim($input['booking_date']) : '';
        $time_slot = isset($input['time_slot']) ? trim($input['time_slot']) : '';
        $net_no = isset($input['net_no']) ? intval($input['net_no']) : 0;
        $coach_id = isset($input['coach_id']) ? intval($input['coach_id']) : null;
        
        // Resolve player_id
        if ($currentUser['role'] === 'player') {
            $player_id = $currentUser['id'];
        } else {
            $player_id = isset($input['player_id']) ? intval($input['player_id']) : 0;
        }

        if (empty($booking_date) || empty($time_slot) || $net_no <= 0 || $player_id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Date, slot, net lane, and player are required."]);
            exit;
        }

        // DOUBLE BOOKING CONFLICT CHECK
        // Check if there is already an active (Pending/Approved) booking for the same date, time, and net lane
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND time_slot = ? AND net_no = ? AND status IN ('Pending', 'Approved')");
        $stmt->execute([$booking_date, $time_slot, $net_no]);
        $hasConflict = $stmt->fetchColumn() > 0;

        if ($hasConflict) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Booking Conflict: Net Lane $net_no is already booked for $time_slot on $booking_date. Please choose another slot, date, or net lane."
            ]);
            exit;
        }

        // Set status
        $status = ($currentUser['role'] === 'admin') ? 'Approved' : 'Pending';

        $stmt = $pdo->prepare("INSERT INTO bookings (player_id, coach_id, booking_date, time_slot, net_no, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$player_id, $coach_id ?: null, $booking_date, $time_slot, $net_no, $status]);

        echo json_encode(["success" => true, "message" => "Booking request recorded successfully."]);
        exit;
    }

    // --- 3. PUT (Update Booking configurations) ---
    if ($method === 'PUT') {
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Booking ID parameter is required."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // A. Update Status Action
        if ($action === 'status') {
            $status = isset($input['status']) ? trim($input['status']) : '';
            if (!in_array($status, ['Pending', 'Approved', 'Cancelled'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid status target."]);
                exit;
            }

            // Fetch current booking row
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Booking not found."]);
                exit;
            }

            // Access Rules: Players can only cancel their own sessions, Admins can do any status
            if ($currentUser['role'] === 'player') {
                if ($booking['player_id'] !== $currentUser['id']) {
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "Access denied. You can only modify your own bookings."]);
                    exit;
                }
                if ($status !== 'Cancelled') {
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "Players are only permitted to cancel their bookings."]);
                    exit;
                }
            } elseif ($currentUser['role'] === 'coach') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Coaches cannot update booking status."]);
                exit;
            }

            // Double check conflict before approving
            if ($status === 'Approved') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND time_slot = ? AND net_no = ? AND status IN ('Pending', 'Approved') AND id != ?");
                $stmt->execute([$booking['booking_date'], $booking['time_slot'], $booking['net_no'], $id]);
                if ($stmt->fetchColumn() > 0) {
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "Cannot approve booking. Net lane is already occupied during this slot."]);
                    exit;
                }
            }

            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            echo json_encode(["success" => true, "message" => "Booking status updated to $status."]);
            exit;
        }

        // B. Assign Coach Action (Admin only)
        if ($action === 'assign') {
            if ($currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden."]);
                exit;
            }

            $coach_id = isset($input['coach_id']) ? intval($input['coach_id']) : null;
            $stmt = $pdo->prepare("UPDATE bookings SET coach_id = ? WHERE id = ?");
            $stmt->execute([$coach_id ?: null, $id]);

            echo json_encode(["success" => true, "message" => "Coach assignment updated successfully."]);
            exit;
        }

        // C. Record Session Feedback Action (Coach only)
        if ($action === 'feedback') {
            $coach_feedback = isset($input['coach_feedback']) ? trim($input['coach_feedback']) : '';

            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Booking not found."]);
                exit;
            }

            // Verify if coach matches or admin
            if ($currentUser['role'] === 'coach' && $booking['coach_id'] !== $currentUser['id']) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied. You are not the assigned coach for this session."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE bookings SET coach_feedback = ? WHERE id = ?");
            $stmt->execute([$coach_feedback, $id]);

            echo json_encode(["success" => true, "message" => "Session performance review recorded."]);
            exit;
        }
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
