<?php
// Dashboard Metrics Compilation API
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit;
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role'];
$userId = $currentUser['id'];

try {
    if ($role === 'admin') {
        // 1. Total Players
        $stmt = $pdo->query("SELECT COUNT(*) FROM players");
        $totalPlayers = $stmt->fetchColumn();

        // 2. Total Coaches
        $stmt = $pdo->query("SELECT COUNT(*) FROM coaches");
        $totalCoaches = $stmt->fetchColumn();

        // 3. Total Bookings
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $totalBookings = $stmt->fetchColumn();

        // 4. Monthly Income (Payments recorded in current calendar month)
        $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
        $monthlyIncome = $stmt->fetchColumn() ?: 0.00;

        // 5. Global Attendance rate summary
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as total_present
            FROM attendance");
        $att = $stmt->fetch();
        $attendanceRate = $att['total_records'] > 0 
            ? round(($att['total_present'] / $att['total_records']) * 100) 
            : 100;

        // 6. Recent bookings feed activities
        $stmt = $pdo->query("SELECT b.id, b.booking_date, b.time_slot, b.status, p.name as player_name 
            FROM bookings b 
            JOIN players p ON b.player_id = p.id 
            ORDER BY b.id DESC LIMIT 6");
        $recentActivities = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "role" => "admin",
            "stats" => [
                "totalPlayers" => $totalPlayers,
                "totalCoaches" => $totalCoaches,
                "totalBookings" => $totalBookings,
                "monthlyIncome" => (float)$monthlyIncome,
                "attendanceRate" => $attendanceRate,
                "recentActivities" => $recentActivities
            ]
        ]);
        exit;

    } elseif ($role === 'coach') {
        // Coach schedule metrics
        // 1. Total assigned sessions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE coach_id = ?");
        $stmt->execute([$userId]);
        $totalSessions = $stmt->fetchColumn();

        // 2. Upcoming assigned sessions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE coach_id = ? AND status = 'Approved' AND booking_date >= CURRENT_DATE()");
        $stmt->execute([$userId]);
        $upcomingSessionsCount = $stmt->fetchColumn();

        // 3. Sessions pending coach feedback/evaluation (Approved, past dates, no feedback notes)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE coach_id = ? AND status = 'Approved' AND coach_feedback IS NULL AND booking_date <= CURRENT_DATE()");
        $stmt->execute([$userId]);
        $pendingFeedbackCount = $stmt->fetchColumn();

        // 4. Coach schedule feed (Top 5 list)
        $stmt = $pdo->prepare("SELECT b.*, p.name as player_name, p.category as player_category 
            FROM bookings b 
            JOIN players p ON b.player_id = p.id 
            WHERE b.coach_id = ? 
            ORDER BY b.booking_date ASC, b.time_slot ASC LIMIT 5");
        $stmt->execute([$userId]);
        $recentSchedules = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "role" => "coach",
            "stats" => [
                "totalSessions" => $totalSessions,
                "upcomingSessionsCount" => $upcomingSessionsCount,
                "pendingFeedbackCount" => $pendingFeedbackCount,
                "recentSchedules" => $recentSchedules
            ]
        ]);
        exit;

    } elseif ($role === 'player') {
        // Player dashboard metrics
        // 1. Upcoming bookings count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE player_id = ? AND status != 'Cancelled' AND booking_date >= CURRENT_DATE()");
        $stmt->execute([$userId]);
        $upcomingBookingsCount = $stmt->fetchColumn();

        // 2. Personal attendance logs details
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days 
            FROM attendance WHERE player_id = ?");
        $stmt->execute([$userId]);
        $att = $stmt->fetch();
        $attendanceRate = $att['total_days'] > 0 
            ? round(($att['present_days'] / $att['total_days']) * 100) 
            : 100;

        // 3. Fee payment status check (Verify if player has recorded payment in the current month)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE player_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
        $stmt->execute([$userId]);
        $hasPaid = $stmt->fetchColumn() > 0;
        $paymentStatus = $hasPaid ? "Paid" : "Pending";

        // 4. Recent bookings list (Top 5)
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE player_id = ? ORDER BY booking_date DESC, time_slot ASC LIMIT 5");
        $stmt->execute([$userId]);
        $recentBookings = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "role" => "player",
            "stats" => [
                "upcomingBookingsCount" => $upcomingBookingsCount,
                "attendanceRate" => $attendanceRate,
                "paymentStatus" => $paymentStatus,
                "recentBookings" => $recentBookings
            ]
        ]);
        exit;
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database operation failed: " . $e->getMessage()]);
    exit;
}
