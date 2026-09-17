<?php
// Protect dashboard - redirect to login if not authenticated
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$currentUser = $_SESSION['user'];
$userRole = $currentUser['role'];
$userId = $currentUser['id'];
$academyName = get_academy_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - <?php echo $academyName; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="dashboard-wrapper">

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h2>🏏 <?php echo explode(' ', $academyName)[0]; ?><span><?php echo implode(' ', array_slice(explode(' ', $academyName), 1)); ?></span></h2>
    </div>

    <ul class="sidebar-menu">
      <li class="sidebar-menu-item">
        <a class="sidebar-link active" data-tab="overview"><i class="fa-solid fa-chart-pie"></i> Overview</a>
      </li>
      <?php if ($userRole !== 'player'): ?>
      <li class="sidebar-menu-item" id="nav-players">
        <a class="sidebar-link" data-tab="players"><i class="fa-solid fa-user-graduate"></i> Players</a>
      </li>
      <?php endif; ?>
      <li class="sidebar-menu-item" id="nav-coaches">
        <a class="sidebar-link" data-tab="coaches"><i class="fa-solid fa-user-tie"></i> Coaches</a>
      </li>
      <li class="sidebar-menu-item" id="nav-bookings">
        <a class="sidebar-link" data-tab="bookings"><i class="fa-solid fa-calendar-check"></i> Net Bookings</a>
      </li>
      <?php if ($userRole !== 'player'): ?>
      <li class="sidebar-menu-item" id="nav-attendance">
        <a class="sidebar-link" data-tab="attendance"><i class="fa-solid fa-clipboard-user"></i> Attendance</a>
      </li>
      <?php endif; ?>
      <li class="sidebar-menu-item" id="nav-payments">
        <a class="sidebar-link" data-tab="payments"><i class="fa-solid fa-credit-card"></i> Payments</a>
      </li>
      <?php if ($userRole === 'admin'): ?>
      <li class="sidebar-menu-item" id="nav-reports">
        <a class="sidebar-link" data-tab="reports"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a>
      </li>
      <li class="sidebar-menu-item" id="nav-settings">
        <a class="sidebar-link" data-tab="settings"><i class="fa-solid fa-sliders"></i> Settings</a>
      </li>
      <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar" id="sidebarAvatar"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
        <div class="user-info">
          <div class="user-name" id="sidebarName"><?php echo htmlspecialchars($currentUser['username']); ?></div>
          <div class="user-role"><?php echo ucfirst($userRole); ?></div>
        </div>
      </div>
      <a href="api/auth.php?action=logout" id="logoutBtn" class="btn btn-logout" onclick="return doLogout(event)">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </a>
    </div>
  </aside>

  <!-- Main Panel -->
  <main class="main-panel">
    <header class="top-nav">
      <button class="mobile-menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
      <div class="nav-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="globalSearch" placeholder="Search...">
      </div>
      <div class="nav-actions">
        <span class="badge <?php echo $userRole === 'admin' ? 'badge-approved' : ($userRole === 'coach' ? 'badge-pending' : 'badge-cancelled'); ?>">
          <?php echo strtoupper($userRole); ?> PORTAL
        </span>
        <span id="headerDate" style="color:var(--text-secondary); font-size:0.9rem;"></span>
      </div>
    </header>

    <div class="content-body">

      <!-- 1. OVERVIEW TAB -->
      <section id="tab-overview" class="view-section">
        <div class="page-header">
          <div class="page-title">
            <h2>Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!</h2>
            <p>Here's your <?php echo ucfirst($userRole); ?> dashboard overview.</p>
          </div>
        </div>
        <div class="stats-grid" id="dashboardStatsGrid"></div>
        <div class="dashboard-columns">
          <div class="chart-card">
            <h3><i class="fa-solid fa-chart-line"></i> Analytics Chart</h3>
            <div class="chart-container"><canvas id="overviewChart"></canvas></div>
          </div>
          <div class="recent-activity-card">
            <h3><i class="fa-solid fa-bell"></i> Recent Activity</h3>
            <div class="activity-list" id="recentActivityList"></div>
          </div>
        </div>
      </section>

      <!-- 2. PLAYERS TAB (Admin & Coach only) -->
      <?php if ($userRole !== 'player'): ?>
      <section id="tab-players" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Player Management</h2><p>Manage registered cricket academy players.</p></div>
          <?php if ($userRole === 'admin'): ?>
          <button id="addPlayerBtn" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Player</button>
          <?php endif; ?>
        </div>
        <div class="data-card">
          <div class="flex justify-between align-center mb-20" style="flex-wrap:wrap; gap:15px;">
            <div class="nav-search" style="display:flex; width:100%; max-width:350px;">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="playerSearch" placeholder="Search by name, category...">
            </div>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Category</th><th>Performance Notes</th><th>Actions</th></tr></thead>
              <tbody id="playersListBody"></tbody>
            </table>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- 3. COACHES TAB -->
      <section id="tab-coaches" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Coaches Directory</h2><p>Academy coaching staff and specializations.</p></div>
          <?php if ($userRole === 'admin'): ?>
          <button id="addCoachBtn" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Coach</button>
          <?php endif; ?>
        </div>
        <div class="data-card">
          <div class="table-responsive">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Specialization</th><th>Actions</th></tr></thead>
              <tbody id="coachesListBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 4. BOOKINGS TAB -->
      <section id="tab-bookings" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Net Practice Bookings</h2><p>Schedule sessions and track slot availability.</p></div>
          <?php if ($userRole !== 'coach'): ?>
          <button id="newBookingBtn" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Book Session</button>
          <?php endif; ?>
        </div>
        <div class="data-card">
          <div class="flex gap-10 mb-20">
            <select id="bookingStatusFilter" class="form-control" style="width:auto; padding:8px 16px;">
              <option value="">All Bookings</option>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead><tr><th>Date</th><th>Time Slot</th><th>Net Lane</th><th>Player</th><th>Coach</th><th>Status</th><th>Feedback</th><th>Actions</th></tr></thead>
              <tbody id="bookingsListBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 5. ATTENDANCE TAB (Admin & Coach only) -->
      <?php if ($userRole !== 'player'): ?>
      <section id="tab-attendance" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Attendance Management</h2><p>Mark and track daily practice attendance.</p></div>
        </div>
        <div class="data-card">
          <div class="flex justify-between align-center mb-20" style="flex-wrap:wrap; gap:15px;">
            <div class="flex align-center gap-10">
              <label for="attendanceDatePicker" style="font-weight:500;">Select Date:</label>
              <input type="date" id="attendanceDatePicker" class="form-control" style="width:auto; padding:6px 12px;">
            </div>
            <button id="saveAttendanceBtn" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Attendance</button>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead><tr><th>Player ID</th><th>Name</th><th>Category</th><th class="text-center">Status</th></tr></thead>
              <tbody id="attendanceListBody"></tbody>
            </table>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- 6. PAYMENTS TAB -->
      <section id="tab-payments" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Payments & Fee Registry</h2><p>Track fee collections and outstanding balances.</p></div>
          <?php if ($userRole === 'admin'): ?>
          <button id="recordPaymentBtn" class="btn btn-primary"><i class="fa-solid fa-dollar-sign"></i> Record Payment</button>
          <?php endif; ?>
        </div>
        <div class="dashboard-columns" style="grid-template-columns:2fr 1fr;">
          <div class="data-card">
            <h3 class="mb-20"><i class="fa-solid fa-clock-rotate-left"></i> Transaction History</h3>
            <div class="table-responsive">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Receipt No</th><th>Player</th><th>Amount</th><th>Actions</th></tr></thead>
                <tbody id="paymentsListBody"></tbody>
              </table>
            </div>
          </div>
          <?php if ($userRole === 'admin'): ?>
          <div class="data-card">
            <h3 class="mb-20" style="color:var(--status-pending);"><i class="fa-solid fa-circle-exclamation"></i> Unpaid This Month</h3>
            <div class="table-responsive">
              <table class="data-table">
                <thead><tr><th>Player</th><th>Phone</th></tr></thead>
                <tbody id="pendingPaymentsListBody"></tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- 7. REPORTS TAB (Admin only) -->
      <?php if ($userRole === 'admin'): ?>
      <section id="tab-reports" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Report Generation</h2><p>Print and export official academy reports.</p></div>
        </div>
        <div class="reports-grid">
          <div class="report-card">
            <h3><i class="fa-solid fa-users"></i> Players Report</h3>
            <p>Complete roster with categories, contacts, and performance evaluations.</p>
            <a href="report_print.php?type=players" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print / Save PDF</a>
          </div>
          <div class="report-card">
            <h3><i class="fa-solid fa-chalkboard-user"></i> Coaches Report</h3>
            <p>Staff directory with specializations and coaching schedules.</p>
            <a href="report_print.php?type=coaches" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print / Save PDF</a>
          </div>
          <div class="report-card">
            <h3><i class="fa-solid fa-book-open"></i> Bookings Report</h3>
            <p>Net lane booking history and session status summaries.</p>
            <a href="report_print.php?type=bookings" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print / Save PDF</a>
          </div>
          <div class="report-card">
            <h3><i class="fa-solid fa-calendar-days"></i> Attendance Report</h3>
            <p>Daily presence logs and overall attendance rate analysis.</p>
            <a href="report_print.php?type=attendance" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print / Save PDF</a>
          </div>
          <div class="report-card">
            <h3><i class="fa-solid fa-file-invoice"></i> Payments Report</h3>
            <p>Income statements, receipt logs, and fee collection summaries.</p>
            <a href="report_print.php?type=payments" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print / Save PDF</a>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- 8. SETTINGS TAB (Admin only) -->
      <?php if ($userRole === 'admin'): ?>
      <section id="tab-settings" class="view-section hidden">
        <div class="page-header">
          <div class="page-title"><h2>Academy Settings</h2><p>Customize system settings and academy profile.</p></div>
        </div>
        <div class="data-card" style="max-width: 600px;">
          <form id="settingsForm">
            <div class="form-group">
              <label for="settingAcademyName"><i class="fa-solid fa-graduation-cap"></i> Academy Name *</label>
              <input type="text" id="settingAcademyName" class="form-control" value="<?php echo htmlspecialchars($academyName); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" id="saveSettingsBtn" style="margin-top: 10px;">
              <i class="fa-solid fa-save"></i> Save Settings
            </button>
          </form>
        </div>
      </section>
      <?php endif; ?>

    </div><!-- end content-body -->
  </main>
</div><!-- end dashboard-wrapper -->

<!-- ====== MODALS ====== -->

<!-- Player Modal (Add/Edit) -->
<div class="modal-overlay" id="playerModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3 id="playerModalTitle">Add New Player</h3>
      <button class="modal-close" onclick="closeModal('playerModal')">&times;</button>
    </header>
    <form id="playerForm">
      <input type="hidden" id="playerIdField">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group" id="playerUsernameGroup">
            <label>Username *</label>
            <input type="text" id="playerUsername" class="form-control">
          </div>
          <div class="form-group" id="playerPasswordGroup">
            <label>Password *</label>
            <input type="password" id="playerPassword" class="form-control">
          </div>
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" id="playerName" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" id="playerEmail" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Phone *</label>
            <input type="text" id="playerPhone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Category *</label>
            <select id="playerCategory" class="form-control" required>
              <option value="">Select...</option>
              <option value="Batsman">Batsman</option>
              <option value="Bowler">Bowler</option>
              <option value="All-Rounder">All-Rounder</option>
              <option value="Wicketkeeper">Wicketkeeper</option>
            </select>
          </div>
          <div class="form-group-full">
            <label>Address *</label>
            <textarea id="playerAddress" class="form-control" rows="2" required></textarea>
          </div>
          <div class="form-group-full">
            <label>Performance Notes</label>
            <textarea id="playerPerformance" class="form-control" rows="3" placeholder="Coach evaluations, batting avg etc."></textarea>
          </div>
        </div>
      </div>
      <footer class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('playerModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Profile</button>
      </footer>
    </form>
  </div>
</div>

<!-- Coach Modal (Add/Edit) -->
<div class="modal-overlay" id="coachModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3 id="coachModalTitle">Add New Coach</h3>
      <button class="modal-close" onclick="closeModal('coachModal')">&times;</button>
    </header>
    <form id="coachForm">
      <input type="hidden" id="coachIdField">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group" id="coachUsernameGroup">
            <label>Username *</label>
            <input type="text" id="coachUsername" class="form-control">
          </div>
          <div class="form-group" id="coachPasswordGroup">
            <label>Password *</label>
            <input type="password" id="coachPassword" class="form-control">
          </div>
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" id="coachName" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" id="coachEmail" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Phone *</label>
            <input type="text" id="coachPhone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Specialization *</label>
            <input type="text" id="coachSpecialization" class="form-control" placeholder="e.g. Fast Bowling, Batting" required>
          </div>
        </div>
      </div>
      <footer class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('coachModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Staff</button>
      </footer>
    </form>
  </div>
</div>

<!-- Booking Modal -->
<div class="modal-overlay" id="bookingModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3>Book Net Practice Session</h3>
      <button class="modal-close" onclick="closeModal('bookingModal')">&times;</button>
    </header>
    <form id="bookingForm">
      <div class="modal-body">
        <div class="form-group" id="bookingPlayerSelectGroup">
          <label>Select Player *</label>
          <select id="bookingPlayerSelect" class="form-control"></select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Practice Date *</label>
            <input type="date" id="bookingDate" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Net Lane *</label>
            <select id="bookingNetSelect" class="form-control" required>
              <option value="1">Net Lane 1</option>
              <option value="2">Net Lane 2</option>
              <option value="3">Net Lane 3</option>
            </select>
          </div>
        </div>
        <label style="display:block; font-weight:500; margin-top:15px; margin-bottom:8px;">Select Time Slot *</label>
        <div class="slots-container" id="timeSlotsContainer">
          <div style="color:var(--text-muted);">Select date and net lane first...</div>
        </div>
        <input type="hidden" id="selectedTimeSlot">
        <div class="form-group" style="margin-top:15px;">
          <label>Assign Coach</label>
          <select id="bookingCoachSelect" class="form-control">
            <option value="">No Coach (Self Practice)</option>
          </select>
        </div>
      </div>
      <footer class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('bookingModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Booking</button>
      </footer>
    </form>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal-overlay" id="feedbackModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3>Session Feedback</h3>
      <button class="modal-close" onclick="closeModal('feedbackModal')">&times;</button>
    </header>
    <form id="feedbackForm">
      <input type="hidden" id="feedbackBookingId">
      <div class="modal-body">
        <div class="form-group">
          <label>Player</label>
          <input type="text" id="feedbackPlayerName" class="form-control" disabled>
        </div>
        <div class="form-group">
          <label>Session</label>
          <input type="text" id="feedbackSessionDetails" class="form-control" disabled>
        </div>
        <div class="form-group">
          <label>Coaching Feedback / Evaluation *</label>
          <textarea id="sessionFeedbackNotes" class="form-control" rows="5" placeholder="Describe player performance, drills done, areas to improve..." required></textarea>
        </div>
      </div>
      <footer class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('feedbackModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Feedback</button>
      </footer>
    </form>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3>Record Fee Payment</h3>
      <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
    </header>
    <form id="paymentForm">
      <div class="modal-body">
        <div class="form-group">
          <label>Select Player *</label>
          <select id="paymentPlayerSelect" class="form-control" required>
            <option value="">Choose Player...</option>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Amount (LKR) *</label>
            <input type="number" step="0.01" id="paymentAmount" class="form-control" value="5000.00" required>
          </div>
          <div class="form-group">
            <label>Payment Date *</label>
            <input type="date" id="paymentDate" class="form-control" required>
          </div>
        </div>
      </div>
      <footer class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Payment</button>
      </footer>
    </form>
  </div>
</div>

<!-- View Profile / Generic Modal -->
<div class="modal-overlay" id="profileModal">
  <div class="modal-card">
    <header class="modal-header">
      <h3 id="profileModalTitle">Details</h3>
      <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
    </header>
    <div class="modal-body" id="profileModalBody"></div>
    <footer class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('profileModal')">Close</button>
    </footer>
  </div>
</div>

<!-- Pass PHP session data to JavaScript -->
<script>
  const CURRENT_USER = {
    id: <?php echo json_encode($userId); ?>,
    username: <?php echo json_encode($currentUser['username']); ?>,
    role: <?php echo json_encode($userRole); ?>
  };
  const ACADEMY_NAME = <?php echo json_encode($academyName); ?>;
</script>
<script src="js/api.js"></script>
<script src="js/dashboard.js"></script>
<script src="js/charts.js"></script>
</body>
</html>
