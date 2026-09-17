<?php
// Payments ledger & Outstanding lists API
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
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // --- 1. GET (Read Payments details) ---
    if ($method === 'GET') {
        // A. View Pending / Unpaid players this month (Admin only)
        if ($action === 'pending') {
            if ($currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden."]);
                exit;
            }

            // Query players who have not paid in current calendar month
            $stmt = $pdo->query("SELECT p.id as player_id, p.name as player_name, p.email, p.phone, p.category 
                FROM players p 
                WHERE p.id NOT IN (
                    SELECT DISTINCT player_id FROM payments 
                    WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                ) 
                ORDER BY p.name ASC");
            $pending = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => $pending]);
            exit;
        }

        // B. Chart Analytics (Monthly summaries past 6 months - Admin only)
        if ($action === 'analytics') {
            if ($currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Forbidden."]);
                exit;
            }

            $stmt = $pdo->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total_amount 
                FROM payments 
                GROUP BY month 
                ORDER BY month DESC LIMIT 6");
            $analytics = $stmt->fetchAll();

            echo json_encode(["success" => true, "data" => array_reverse($analytics)]);
            exit;
        }

        // C. Standard Payments Listing
        $player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
        
        $query = "SELECT pay.*, p.name as player_name, p.email as player_email 
            FROM payments pay 
            JOIN players p ON pay.player_id = p.id";
        $params = [];

        // Role restriction: Players can only view their own payment log
        if ($currentUser['role'] === 'player') {
            $query .= " WHERE pay.player_id = ?";
            $params[] = $currentUser['id'];
        } else {
            if ($player_id > 0) {
                $query .= " WHERE pay.player_id = ?";
                $params[] = $player_id;
            }
        }

        $query .= " ORDER BY pay.payment_date DESC, pay.id DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();

        echo json_encode(["success" => true, "data" => $payments]);
        exit;
    }

    // --- 2. POST (Record fee collection - Admin or Player) ---
    if ($method === 'POST') {
        if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'player') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        // Assign player ID dynamically
        if ($currentUser['role'] === 'player') {
            $player_id = $currentUser['id'];
        } else {
            $player_id = isset($input['player_id']) ? intval($input['player_id']) : 0;
        }
        
        $amount = isset($input['amount']) ? floatval($input['amount']) : 0.00;
        $payment_date = isset($input['payment_date']) ? trim($input['payment_date']) : date('Y-m-d');

        if ($player_id <= 0 || $amount <= 0 || empty($payment_date)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Player ID, amount, and payment date are required."]);
            exit;
        }

        // Start transaction
        $pdo->beginTransaction();

        // Insert placeholder receipt to grab auto increment insertId
        $stmt = $pdo->prepare("INSERT INTO payments (player_id, amount, payment_date, receipt_no) VALUES (?, ?, ?, 'TEMP')");
        $stmt->execute([$player_id, $amount, $payment_date]);
        $newPaymentId = $pdo->lastInsertId();

        // Format receipt: REC-YYYYMMDD-XXXX where XXXX is padded payment ID
        $dateFormatted = str_replace('-', '', $payment_date); // "20260625"
        $receiptNo = "REC-" . $dateFormatted . "-" . str_pad($newPaymentId, 4, '0', STR_PAD_LEFT);

        // Update with final generated receipt coding
        $stmt = $pdo->prepare("UPDATE payments SET receipt_no = ? WHERE id = ?");
        $stmt->execute([$receiptNo, $newPaymentId]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Payment transaction recorded.",
            "receipt_no" => $receiptNo
        ]);
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
