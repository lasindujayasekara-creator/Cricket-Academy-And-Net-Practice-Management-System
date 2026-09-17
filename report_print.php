<?php
// Print-ready Report View (Opens in new tab, user clicks Ctrl+P or browser Print)
session_start();
require_once 'config/db.php';
$academyName = get_academy_name();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('<p style="color:red; text-align:center; padding:30px;">Access Denied. Only Admins can view reports.</p>');
}

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

$title = '';
$headers = [];
$rows = [];

switch ($type) {
    case 'players':
        $title = 'Registered Players Report';
        $headers = ['ID', 'Username', 'Full Name', 'Email', 'Phone', 'Category', 'Address', 'Performance Notes'];
        $stmt = $pdo->query("SELECT p.*, u.username FROM players p JOIN users u ON p.id = u.id ORDER BY p.name ASC");
        while ($row = $stmt->fetch()) {
            $rows[] = [
                $row['id'], $row['username'], $row['name'], $row['email'],
                $row['phone'], $row['category'], $row['address'],
                $row['performance_notes'] ?: 'No notes yet'
            ];
        }
        break;

    case 'coaches':
        $title = 'Academy Coaching Staff Report';
        $headers = ['ID', 'Username', 'Full Name', 'Email', 'Phone', 'Specialization'];
        $stmt = $pdo->query("SELECT c.*, u.username FROM coaches c JOIN users u ON c.id = u.id ORDER BY c.name ASC");
        while ($row = $stmt->fetch()) {
            $rows[] = [$row['id'], $row['username'], $row['name'], $row['email'], $row['phone'], $row['specialization']];
        }
        break;

    case 'bookings':
        $title = 'Net Practice Bookings Report';
        $headers = ['Date', 'Player', 'Time Slot', 'Net Lane', 'Coach', 'Status', 'Session Feedback'];
        $stmt = $pdo->query("SELECT b.*, p.name as player_name, COALESCE(c.name, 'No Coach') as coach_name 
            FROM bookings b JOIN players p ON b.player_id = p.id LEFT JOIN coaches c ON b.coach_id = c.id 
            ORDER BY b.booking_date DESC, b.time_slot ASC");
        while ($row = $stmt->fetch()) {
            $rows[] = [
                $row['booking_date'], $row['player_name'], $row['time_slot'],
                'Net '.$row['net_no'], $row['coach_name'], $row['status'],
                $row['coach_feedback'] ?: 'N/A'
            ];
        }
        break;

    case 'attendance':
        $title = "Attendance Report - $date";
        $headers = ['Player ID', 'Player Name', 'Category', 'Attendance Status'];
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.category, COALESCE(a.status, 'Not Marked') as status 
            FROM players p LEFT JOIN attendance a ON p.id = a.player_id AND a.attendance_date = ? 
            ORDER BY p.name ASC");
        $stmt->execute([$date]);
        while ($row = $stmt->fetch()) {
            $rows[] = [$row['id'], $row['name'], $row['category'], $row['status']];
        }
        break;

    case 'payments':
        $title = 'Income & Fee Payments Report';
        $headers = ['Date', 'Receipt No', 'Player Name', 'Email', 'Amount (LKR)'];
        $stmt = $pdo->query("SELECT pay.*, p.name as player_name, p.email as player_email 
            FROM payments pay JOIN players p ON pay.player_id = p.id ORDER BY pay.payment_date DESC");
        while ($row = $stmt->fetch()) {
            $rows[] = [$row['payment_date'], $row['receipt_no'], $row['player_name'], $row['player_email'], number_format($row['amount'], 2)];
        }
        break;

    default:
        die('<p style="color:red; text-align:center; padding:30px;">Invalid report type.</p>');
}

$generatedAt = date('d F Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($title); ?> - Cricket Academy</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #1e293b; }

    .report-header { background: #10b981; color: white; padding: 25px 40px; }
    .report-header h1 { font-size: 1.4rem; font-weight: 700; }
    .report-header p { font-size: 0.85rem; opacity: 0.9; margin-top: 4px; }

    .report-meta { padding: 15px 40px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 0.85rem; }
    .report-meta strong { color: #10b981; }

    .report-title { padding: 20px 40px 10px; font-size: 1.1rem; font-weight: 700; color: #0f172a; border-bottom: 2px solid #10b981; margin: 0 40px; }

    .table-wrapper { padding: 20px 40px 40px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    thead th { background: #1e293b; color: white; padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
    tbody tr:nth-child(even) { background-color: #f8fafc; }
    tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }

    .report-footer { text-align: center; padding: 20px 40px; font-size: 0.75rem; color: #64748b; border-top: 1px solid #e2e8f0; }

    .no-print-bar { background: #0f172a; color: #fff; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; }
    .no-print-bar button { background: #10b981; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; }
    .no-print-bar button:hover { background: #059669; }
    
    .total-records { padding: 10px 40px; font-size: 0.9rem; color: #64748b; }
    .total-records strong { color: #0f172a; }

    @media print {
      .no-print-bar { display: none; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
  <div class="no-print-bar">
    <span>📄 Report Preview — Click Print to save as PDF</span>
    <button onclick="window.print()"><i>🖨️</i> Print / Save as PDF</button>
  </div>

  <div class="report-header">
    <h1>🏏 <?php echo strtoupper($academyName); ?> & NET PRACTICE MANAGEMENT SYSTEM</h1>
    <p>Higher National Diploma (HND) Final Year Project — Official Report Document</p>
  </div>

  <div class="report-meta">
    <span>Report Type: <strong><?php echo htmlspecialchars(ucfirst($type)); ?></strong></span>
    <span>Total Records: <strong><?php echo count($rows); ?></strong></span>
    <span>Generated On: <strong><?php echo $generatedAt; ?></strong></span>
  </div>

  <div class="report-title"><?php echo htmlspecialchars($title); ?></div>

  <div class="table-wrapper">
    <?php if (empty($rows)): ?>
      <p style="padding: 30px 0; text-align: center; color: #94a3b8; font-style: italic;">No data records found for this report.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr><?php foreach ($headers as $h): ?><th><?php echo htmlspecialchars($h); ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($row as $cell): ?>
          <td><?php echo htmlspecialchars($cell); ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <div class="report-footer">
    <p><?php echo htmlspecialchars($academyName); ?> and Net Practice Management System &mdash; HND Final Project Report</p>
    <p>Generated: <?php echo $generatedAt; ?> | Total Records: <?php echo count($rows); ?></p>
  </div>
</body>
</html>
