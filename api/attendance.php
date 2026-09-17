<?php
// Attendance Management sheets API
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
$playerId = isset($_GET['playerId']) ? intval($_GET['playerId']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // --- 1. GET (Read Attendance logs) ---
    if ($method === 'GET') {
        // A. View specific player attendance history
        if ($playerId > 0) {
            // Access verification: Players can only view their own attendance log
            if ($currentUser['role'] === 'player' && $currentUser['id'] !== $playerId) {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access denied."]);
                exit;
            }

            // Fetch history
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE player_id = ? ORDER BY attendance_date DESC");
            $stmt->execute([$playerId]);
            $history = $stmt->fetchAll();

            // Fetch statistics
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
                FROM attendance WHERE player_id = ?");
            $stmt->execute([$playerId]);
            $stats = $stmt->fetch();
            
            $stats['attendance_rate'] = $stats['total_days'] > 0 
                ? round(($stats['present_days'] / $stats['total_days']) * 100) 
                : 100;

            echo json_encode(["success" => true, "stats" => $stats, "data" => $history]);
            exit;
        }

        // B. Chart Analytics Feed
        if ($action === 'analytics') {
            if ($currentUser['role'] === 'player') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden."]);
                exit;
            }

            $stmt = $pdo->query("SELECT attendance_date,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
                FROM attendance
                GROUP BY attendance_date
                ORDER BY attendance_date DESC LIMIT 10");
            $analytics = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => array_reverse($analytics)]);
            exit;
        }

        // C. Daily sheet list by date
        if ($currentUser['role'] === 'player') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        $stmt = $pdo->prepare("SELECT p.id as player_id, p.name as player_name, p.category, a.status, a.id as attendance_id
            FROM players p
            LEFT JOIN attendance a ON p.id = a.player_id AND a.attendance_date = ?
            ORDER BY p.name ASC");
        $stmt->execute([$date]);
        $records = $stmt->fetchAll();

        echo json_encode(["success" => true, "date" => $date, "data" => $records]);
        exit;
    }

    // --- 2. POST (Mark Attendance - Admin or Coach Only) ---
    if ($method === 'POST') {
        if ($currentUser['role'] === 'player') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $date = isset($input['date']) ? trim($input['date']) : '';
        $records = isset($input['records']) ? $input['records'] : null;

        if (empty($date) || empty($records) || !is_array($records)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Date and records array are required."]);
            exit;
        }

        // Start database transaction
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO attendance (player_id, attendance_date, status) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = ?");

        foreach ($records as $record) {
            $pId = intval($record['player_id']);
            $status = trim($record['status']);
            $stmt->execute([$pId, $date, $status, $status]);
        }

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Attendance sheets updated successfully."]);
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
