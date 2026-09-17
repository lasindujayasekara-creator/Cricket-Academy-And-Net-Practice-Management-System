// Dashboard - Main Controller Script
let selectedTimeSlotVal = '';
let currentChartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
  // Set today's date
  document.getElementById('headerDate').textContent = new Date().toDateString();

  // Update sidebar username from PHP session
  if (typeof CURRENT_USER !== 'undefined' && CURRENT_USER.role === 'player') {
    fetch('api/players.php?id=' + CURRENT_USER.id)
      .then(r => r.json()).then(data => {
        if (data.success && data.data) {
          document.getElementById('sidebarName').textContent = data.data.name;
          document.getElementById('sidebarAvatar').textContent = data.data.name.charAt(0).toUpperCase();
        }
      }).catch(() => {});
  }

  // Initialize tab loading
  loadTabData('overview');
  setupEventListeners();
});

function setupEventListeners() {
  // Sidebar tab navigation
  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      switchTab(link.getAttribute('data-tab'));
    });
  });

  // Mobile menu toggle
  const menuToggle = document.getElementById('menuToggle');
  if (menuToggle) menuToggle.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('active'));

  // Logout
  document.getElementById('logoutBtn').addEventListener('click', async e => {
    e.preventDefault();
    await fetch('api/auth.php?action=logout', { method: 'POST' });
    window.location.href = 'index.php';
  });

  // Player CRUD events
  const addPlayerBtn = document.getElementById('addPlayerBtn');
  if (addPlayerBtn) addPlayerBtn.addEventListener('click', () => openPlayerModal());
  const playerForm = document.getElementById('playerForm');
  if (playerForm) playerForm.addEventListener('submit', savePlayer);
  const playerSearch = document.getElementById('playerSearch');
  if (playerSearch) playerSearch.addEventListener('input', () => loadPlayersList(playerSearch.value));

  // Coach CRUD events
  const addCoachBtn = document.getElementById('addCoachBtn');
  if (addCoachBtn) addCoachBtn.addEventListener('click', () => openCoachModal());
  const coachForm = document.getElementById('coachForm');
  if (coachForm) coachForm.addEventListener('submit', saveCoach);

  // Booking events
  const newBookingBtn = document.getElementById('newBookingBtn');
  if (newBookingBtn) newBookingBtn.addEventListener('click', () => openBookingModal());
  const bookingForm = document.getElementById('bookingForm');
  if (bookingForm) bookingForm.addEventListener('submit', saveBooking);
  const bookingDate = document.getElementById('bookingDate');
  if (bookingDate) bookingDate.addEventListener('change', refreshSlots);
  const bookingNetSelect = document.getElementById('bookingNetSelect');
  if (bookingNetSelect) bookingNetSelect.addEventListener('change', refreshSlots);
  const bookingStatusFilter = document.getElementById('bookingStatusFilter');
  if (bookingStatusFilter) bookingStatusFilter.addEventListener('change', loadBookingsList);

  // Attendance events
  const attendanceDatePicker = document.getElementById('attendanceDatePicker');
  if (attendanceDatePicker) {
    attendanceDatePicker.value = new Date().toISOString().slice(0, 10);
    attendanceDatePicker.addEventListener('change', loadAttendanceSheet);
  }
  const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');
  if (saveAttendanceBtn) saveAttendanceBtn.addEventListener('click', saveAttendanceSheet);

  // Payment events
  const recordPaymentBtn = document.getElementById('recordPaymentBtn');
  if (recordPaymentBtn) recordPaymentBtn.addEventListener('click', openPaymentModal);
  const paymentForm = document.getElementById('paymentForm');
  if (paymentForm) paymentForm.addEventListener('submit', savePaymentRecord);

  // Feedback form
  const feedbackForm = document.getElementById('feedbackForm');
  if (feedbackForm) feedbackForm.addEventListener('submit', saveSessionFeedback);

  // Settings form
  const settingsForm = document.getElementById('settingsForm');
  if (settingsForm) settingsForm.addEventListener('submit', saveSettings);
}

function switchTab(tabId) {
  document.querySelectorAll('.sidebar-link').forEach(l => {
    l.classList.toggle('active', l.getAttribute('data-tab') === tabId);
  });
  document.querySelectorAll('.view-section').forEach(s => {
    s.classList.toggle('hidden', s.id !== `tab-${tabId}`);
  });
  document.getElementById('sidebar').classList.remove('active');
  loadTabData(tabId);
}

function loadTabData(tabId) {
  switch (tabId) {
    case 'overview':   loadOverviewStats(); break;
    case 'players':    loadPlayersList(); break;
    case 'coaches':    loadCoachesList(); break;
    case 'bookings':   loadBookingsList(); break;
    case 'attendance': loadAttendanceSheet(); break;
    case 'payments':   loadPaymentsLedger(); break;
    case 'settings':   /* Already rendered server-side */ break;
  }
}

// ====== OVERVIEW ======
async function loadOverviewStats() {
  try {
    const res = await API.get('api/dashboard.php');
    if (!res || !res.success) return;
    const grid = document.getElementById('dashboardStatsGrid');
    const feed = document.getElementById('recentActivityList');
    grid.innerHTML = '';
    feed.innerHTML = '';

    if (res.role === 'admin') {
      const s = res.stats;
      grid.innerHTML = `
        <div class="stat-card"><div class="stat-details"><h3>Total Players</h3><div class="stat-number">${s.totalPlayers}</div></div><div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Total Coaches</h3><div class="stat-number">${s.totalCoaches}</div></div><div class="stat-icon" style="color:#3b82f6"><i class="fa-solid fa-user-tie"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Total Bookings</h3><div class="stat-number">${s.totalBookings}</div></div><div class="stat-icon" style="color:#f59e0b"><i class="fa-solid fa-calendar-check"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Monthly Income</h3><div class="stat-number">LKR ${parseFloat(s.monthlyIncome).toLocaleString()}</div></div><div class="stat-icon" style="color:#a855f7"><i class="fa-solid fa-dollar-sign"></i></div></div>
      `;
      s.recentActivities.length === 0
        ? feed.innerHTML = '<p style="color:var(--text-muted); font-style:italic; text-align:center; padding:10px;">No recent activities.</p>'
        : s.recentActivities.forEach(a => {
            feed.innerHTML += `<div class="activity-item"><div class="activity-bullet" style="background:var(--status-${a.status.toLowerCase()})"></div><div class="activity-content"><div class="activity-title"><strong>${a.player_name}</strong> booked a net session</div><div class="activity-time">${a.booking_date} | ${a.time_slot} | <span class="badge badge-${a.status.toLowerCase()}">${a.status}</span></div></div></div>`;
          });
      initAdminCharts();

    } else if (res.role === 'coach') {
      const s = res.stats;
      grid.innerHTML = `
        <div class="stat-card"><div class="stat-details"><h3>Total Assigned Sessions</h3><div class="stat-number">${s.totalSessions}</div></div><div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Upcoming Sessions</h3><div class="stat-number">${s.upcomingSessionsCount}</div></div><div class="stat-icon" style="color:#3b82f6"><i class="fa-solid fa-hourglass-half"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Feedback Pending</h3><div class="stat-number" style="color:var(--status-pending)">${s.pendingFeedbackCount}</div></div><div class="stat-icon" style="color:#f59e0b"><i class="fa-solid fa-comment-medical"></i></div></div>
      `;
      s.recentSchedules.length === 0
        ? feed.innerHTML = '<p style="color:var(--text-muted); font-style:italic; text-align:center; padding:10px;">No upcoming schedule.</p>'
        : s.recentSchedules.forEach(i => {
            feed.innerHTML += `<div class="activity-item"><div class="activity-bullet"></div><div class="activity-content"><div class="activity-title">Session: <strong>${i.player_name}</strong> (${i.player_category})</div><div class="activity-time">${i.booking_date} | ${i.time_slot} | Net ${i.net_no}</div></div></div>`;
          });
      initCoachChart(s);

    } else if (res.role === 'player') {
      const s = res.stats;
      const pColor = s.paymentStatus === 'Paid' ? 'var(--status-approved)' : 'var(--status-pending)';
      grid.innerHTML = `
        <div class="stat-card"><div class="stat-details"><h3>Upcoming Bookings</h3><div class="stat-number">${s.upcomingBookingsCount}</div></div><div class="stat-icon"><i class="fa-solid fa-clock"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Attendance Rate</h3><div class="stat-number">${s.attendanceRate}%</div></div><div class="stat-icon" style="color:#3b82f6"><i class="fa-solid fa-clipboard-user"></i></div></div>
        <div class="stat-card"><div class="stat-details"><h3>Monthly Fee</h3><div class="stat-number" style="color:${pColor}">${s.paymentStatus}</div></div><div class="stat-icon" style="color:#a855f7"><i class="fa-solid fa-file-invoice-dollar"></i></div></div>
      `;
      s.recentBookings.length === 0
        ? feed.innerHTML = '<p style="color:var(--text-muted); font-style:italic; text-align:center; padding:10px;">No bookings yet.</p>'
        : s.recentBookings.forEach(b => {
            feed.innerHTML += `<div class="activity-item"><div class="activity-bullet" style="background:var(--status-${b.status.toLowerCase()})"></div><div class="activity-content"><div class="activity-title">Practice - Net ${b.net_no}</div><div class="activity-time">${b.booking_date} | ${b.time_slot} | <span class="badge badge-${b.status.toLowerCase()}">${b.status}</span></div></div></div>`;
          });
      initPlayerChart(s);
    }
  } catch (err) { console.error('Dashboard load error:', err); }
}

// ====== PLAYERS ======
async function loadPlayersList(search = '') {
  try {
    const res = await API.get(`api/players.php?search=${encodeURIComponent(search)}`);
    if (!res || !res.success) return;
    const tbody = document.getElementById('playersListBody');
    tbody.innerHTML = '';
    if (res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="color:var(--text-secondary); padding:20px;">No players found.</td></tr>`;
      return;
    }
    res.data.forEach(p => {
      const isAdmin = CURRENT_USER.role === 'admin';
      const isCoach = CURRENT_USER.role === 'coach';
      tbody.innerHTML += `
        <tr>
          <td><strong>${p.id}</strong></td>
          <td><strong>${escHtml(p.name)}</strong><br><small style="color:var(--text-muted)">@${escHtml(p.username)}</small></td>
          <td>${escHtml(p.email)}</td>
          <td>${escHtml(p.phone)}</td>
          <td><span class="badge badge-outline">${escHtml(p.category)}</span></td>
          <td><div style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escHtml(p.performance_notes || '')}">${p.performance_notes ? escHtml(p.performance_notes) : '<em style="color:var(--text-muted)">No evaluation</em>'}</div></td>
          <td>
            <div class="action-buttons">
              <button class="btn-icon" title="View Profile" onclick="viewPlayerProfile(${p.id})"><i class="fa-solid fa-eye"></i></button>
              ${isAdmin ? `<button class="btn-icon" title="Edit" onclick="openPlayerModal(${p.id})"><i class="fa-solid fa-pen-to-square"></i></button>
              <button class="btn-icon btn-icon-danger" title="Delete" onclick="deletePlayer(${p.id})"><i class="fa-solid fa-trash-can"></i></button>` : ''}
              ${isCoach ? `<button class="btn-icon" title="Update Performance" onclick="openPlayerModal(${p.id})"><i class="fa-solid fa-star"></i></button>` : ''}
            </div>
          </td>
        </tr>
      `;
    });
  } catch (err) { alert('Error loading players: ' + err.message); }
}

async function openPlayerModal(id = null) {
  const form = document.getElementById('playerForm');
  form.reset();
  document.getElementById('playerIdField').value = '';
  const usernameGroup = document.getElementById('playerUsernameGroup');
  const passwordGroup = document.getElementById('playerPasswordGroup');

  if (id) {
    document.getElementById('playerModalTitle').textContent = 'Edit Player Profile';
    usernameGroup.style.display = 'none';
    passwordGroup.style.display = 'none';
    document.getElementById('playerUsername').required = false;
    document.getElementById('playerPassword').required = false;
    try {
      const res = await API.get(`api/players.php?id=${id}`);
      if (res && res.success) {
        const p = res.data;
        document.getElementById('playerIdField').value = p.id;
        document.getElementById('playerName').value = p.name;
        document.getElementById('playerEmail').value = p.email;
        document.getElementById('playerPhone').value = p.phone;
        document.getElementById('playerCategory').value = p.category;
        document.getElementById('playerAddress').value = p.address;
        document.getElementById('playerPerformance').value = p.performance_notes || '';
      }
    } catch (err) { alert(err.message); return; }
  } else {
    document.getElementById('playerModalTitle').textContent = 'Add New Player';
    usernameGroup.style.display = 'block';
    passwordGroup.style.display = 'block';
    document.getElementById('playerUsername').required = true;
    document.getElementById('playerPassword').required = true;
  }
  openModal('playerModal');
}

async function savePlayer(e) {
  e.preventDefault();
  const id = document.getElementById('playerIdField').value;
  const payload = {
    name: document.getElementById('playerName').value.trim(),
    email: document.getElementById('playerEmail').value.trim(),
    phone: document.getElementById('playerPhone').value.trim(),
    category: document.getElementById('playerCategory').value,
    address: document.getElementById('playerAddress').value.trim(),
    performance_notes: document.getElementById('playerPerformance').value.trim()
  };
  if (!id) {
    payload.username = document.getElementById('playerUsername').value.trim();
    payload.password = document.getElementById('playerPassword').value.trim();
  }
  try {
    const res = id
      ? await API.put(`api/players.php?id=${id}`, payload)
      : await API.post('api/players.php', payload);
    if (res && res.success) {
      alert(res.message);
      closeModal('playerModal');
      loadPlayersList();
    }
  } catch (err) { alert(err.message); }
}

async function deletePlayer(id) {
  if (confirm('Warning: This will delete the player account and all related records. Are you sure?')) {
    try {
      const res = await API.delete(`api/players.php?id=${id}`);
      if (res && res.success) { alert(res.message); loadPlayersList(); }
    } catch (err) { alert(err.message); }
  }
}

async function viewPlayerProfile(id) {
  try {
    const res = await API.get(`api/players.php?id=${id}`);
    const att = await API.get(`api/attendance.php?playerId=${id}`);
    if (!res || !res.success) return;
    const p = res.data;
    const rate = att && att.success ? att.stats.attendance_rate : 'N/A';
    const present = att && att.success ? att.stats.present_days : '-';
    const total = att && att.success ? att.stats.total_days : '-';
    document.getElementById('profileModalTitle').textContent = 'Player Profile';
    document.getElementById('profileModalBody').innerHTML = `
      <div style="display:flex;align-items:center;gap:18px;margin-bottom:18px;">
        <div class="user-avatar" style="width:60px;height:60px;font-size:1.8rem;">${p.name.charAt(0)}</div>
        <div><h3 style="color:#fff;font-size:1.3rem;">${escHtml(p.name)}</h3><span class="badge badge-approved">${escHtml(p.category)}</span></div>
      </div>
      <hr style="border:0;border-top:1px solid var(--border-color);margin-bottom:15px;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:0.88rem;margin-bottom:15px;">
        <div><strong>Username:</strong> @${escHtml(p.username)}</div>
        <div><strong>Email:</strong> ${escHtml(p.email)}</div>
        <div><strong>Phone:</strong> ${escHtml(p.phone)}</div>
        <div><strong>Attendance:</strong> ${rate}% (${present}/${total} sessions)</div>
        <div style="grid-column:span 2"><strong>Address:</strong> ${escHtml(p.address)}</div>
      </div>
      <div style="background:var(--bg-primary);padding:14px;border-radius:var(--radius);border-left:3px solid var(--accent-green);">
        <strong style="font-size:0.88rem;">Performance Evaluation:</strong>
        <p style="margin-top:6px;color:var(--text-secondary);font-style:${p.performance_notes ? 'normal' : 'italic'};">${p.performance_notes ? escHtml(p.performance_notes) : 'No evaluation recorded yet.'}</p>
      </div>
    `;
    openModal('profileModal');
  } catch (err) { alert(err.message); }
}

// ====== COACHES ======
async function loadCoachesList() {
  try {
    const res = await API.get('api/coaches.php');
    if (!res || !res.success) return;
    const tbody = document.getElementById('coachesListBody');
    tbody.innerHTML = '';
    if (res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="color:var(--text-secondary);padding:20px;">No coaches registered.</td></tr>`;
      return;
    }
    const isAdmin = CURRENT_USER.role === 'admin';
    res.data.forEach(c => {
      tbody.innerHTML += `
        <tr>
          <td><strong>${c.id}</strong></td>
          <td><strong>${escHtml(c.name)}</strong><br><small style="color:var(--text-muted)">@${escHtml(c.username)}</small></td>
          <td>${escHtml(c.email)}</td>
          <td>${escHtml(c.phone)}</td>
          <td>${escHtml(c.specialization)}</td>
          <td>
            <div class="action-buttons">
              <button class="btn-icon" title="View Schedule" onclick="viewCoachSchedule(${c.id}, '${escHtml(c.name)}')"><i class="fa-solid fa-calendar-days"></i></button>
              ${isAdmin ? `<button class="btn-icon" title="Edit" onclick="openCoachModal(${c.id})"><i class="fa-solid fa-pen-to-square"></i></button>
              <button class="btn-icon btn-icon-danger" title="Delete" onclick="deleteCoach(${c.id})"><i class="fa-solid fa-trash-can"></i></button>` : ''}
            </div>
          </td>
        </tr>
      `;
    });
  } catch (err) { alert('Error loading coaches: ' + err.message); }
}

async function openCoachModal(id = null) {
  const form = document.getElementById('coachForm');
  form.reset();
  document.getElementById('coachIdField').value = '';
  const usernameGroup = document.getElementById('coachUsernameGroup');
  const passwordGroup = document.getElementById('coachPasswordGroup');

  if (id) {
    document.getElementById('coachModalTitle').textContent = 'Edit Coach Profile';
    usernameGroup.style.display = 'none';
    passwordGroup.style.display = 'block';
    passwordGroup.querySelector('label').textContent = 'Password (Leave blank to keep current)';
    document.getElementById('coachUsername').required = false;
    document.getElementById('coachPassword').required = false;
    try {
      const res = await API.get(`api/coaches.php?id=${id}`);
      if (res && res.success) {
        const c = res.data;
        document.getElementById('coachIdField').value = c.id;
        document.getElementById('coachName').value = c.name;
        document.getElementById('coachEmail').value = c.email;
        document.getElementById('coachPhone').value = c.phone;
        document.getElementById('coachSpecialization').value = c.specialization;
      }
    } catch (err) { alert(err.message); return; }
  } else {
    document.getElementById('coachModalTitle').textContent = 'Add New Coach';
    usernameGroup.style.display = 'block';
    passwordGroup.style.display = 'block';
    passwordGroup.querySelector('label').textContent = 'Password *';
    document.getElementById('coachUsername').required = true;
    document.getElementById('coachPassword').required = true;
  }
  openModal('coachModal');
}

async function saveCoach(e) {
  e.preventDefault();
  const id = document.getElementById('coachIdField').value;
  const payload = {
    name: document.getElementById('coachName').value.trim(),
    email: document.getElementById('coachEmail').value.trim(),
    phone: document.getElementById('coachPhone').value.trim(),
    specialization: document.getElementById('coachSpecialization').value.trim()
  };
  if (id) {
    const pw = document.getElementById('coachPassword').value.trim();
    if (pw) payload.password = pw;
  } else {
    payload.username = document.getElementById('coachUsername').value.trim();
    payload.password = document.getElementById('coachPassword').value.trim();
  }
  try {
    const res = id
      ? await API.put(`api/coaches.php?id=${id}`, payload)
      : await API.post('api/coaches.php', payload);
    if (res && res.success) {
      alert(res.message);
      closeModal('coachModal');
      loadCoachesList();
    }
  } catch (err) { alert(err.message); }
}

async function deleteCoach(id) {
  if (confirm('Delete this coach? Their bookings will be unassigned.')) {
    try {
      const res = await API.delete(`api/coaches.php?id=${id}`);
      if (res && res.success) { alert(res.message); loadCoachesList(); }
    } catch (err) { alert(err.message); }
  }
}

async function viewCoachSchedule(id, name) {
  try {
    const res = await API.get(`api/coaches.php?id=${id}&action=schedule`);
    if (!res || !res.success) return;
    document.getElementById('profileModalTitle').textContent = `Schedule - ${name}`;
    let html = '<div style="display:flex;flex-direction:column;gap:12px;">';
    if (res.data.length === 0) {
      html += '<p style="color:var(--text-muted);font-style:italic;text-align:center;">No sessions assigned.</p>';
    } else {
      res.data.forEach(b => {
        html += `<div style="background:var(--bg-primary);padding:12px;border-radius:var(--radius);border-left:3px solid var(--accent-green);">
          <div style="display:flex;justify-content:space-between;font-weight:600;color:#fff;"><span>${b.booking_date}</span><span class="badge badge-${b.status.toLowerCase()}">${b.status}</span></div>
          <div style="margin-top:6px;font-size:0.85rem;color:var(--text-secondary);">${b.time_slot} | Net ${b.net_no} | Player: <strong>${escHtml(b.player_name)}</strong> (${escHtml(b.player_category)})</div>
        </div>`;
      });
    }
    html += '</div>';
    document.getElementById('profileModalBody').innerHTML = html;
    openModal('profileModal');
  } catch (err) { alert(err.message); }
}

// ====== BOOKINGS ======
async function loadBookingsList() {
  try {
    const status = document.getElementById('bookingStatusFilter').value;
    const res = await API.get(`api/bookings.php${status ? '?status=' + status : ''}`);
    if (!res || !res.success) return;
    const tbody = document.getElementById('bookingsListBody');
    tbody.innerHTML = '';
    if (res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="color:var(--text-secondary);padding:20px;">No bookings found.</td></tr>`;
      return;
    }
    res.data.forEach(b => {
      let actions = '';
      if (CURRENT_USER.role === 'admin') {
        if (b.status === 'Pending') actions += `<button class="btn btn-outline" style="padding:3px 10px;font-size:0.75rem;border-color:var(--accent-green);color:var(--accent-green);" onclick="updateBookingStatus(${b.id},'Approved')">Approve</button>`;
        if (b.status !== 'Cancelled') actions += ` <button class="btn btn-outline" style="padding:3px 10px;font-size:0.75rem;border-color:var(--status-cancelled);color:#fca5a5;" onclick="updateBookingStatus(${b.id},'Cancelled')">Cancel</button>`;
        actions += ` <button class="btn-icon" title="Assign Coach" onclick="openAssignCoachModal(${b.id},${b.coach_id || 'null'})"><i class="fa-solid fa-user-plus"></i></button>`;
      } else if (CURRENT_USER.role === 'player' && b.status !== 'Cancelled') {
        actions = `<button class="btn btn-outline" style="padding:3px 10px;font-size:0.75rem;border-color:var(--status-cancelled);color:#fca5a5;" onclick="updateBookingStatus(${b.id},'Cancelled')">Cancel</button>`;
      } else if (CURRENT_USER.role === 'coach' && b.status === 'Approved') {
        actions = `<button class="btn btn-outline" style="padding:3px 10px;font-size:0.75rem;border-color:var(--accent-green);color:var(--accent-green);" onclick="openFeedbackModal(${b.id},'${escHtml(b.player_name)}','${b.booking_date} @ ${b.time_slot}')">Add Review</button>`;
      }
      tbody.innerHTML += `
        <tr>
          <td><strong>${b.booking_date}</strong></td>
          <td>${b.time_slot}</td>
          <td><span class="badge badge-outline">Net ${b.net_no}</span></td>
          <td>${escHtml(b.player_name)}<br><small style="color:var(--text-muted)">${escHtml(b.player_phone)}</small></td>
          <td>${b.coach_name ? escHtml(b.coach_name) : '<em style="color:var(--text-muted)">None</em>'}</td>
          <td><span class="badge badge-${b.status.toLowerCase()}">${b.status}</span></td>
          <td><div style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.82rem;" title="${escHtml(b.coach_feedback || '')}">${b.coach_feedback ? escHtml(b.coach_feedback) : '<em style="color:var(--text-muted)">N/A</em>'}</div></td>
          <td><div class="action-buttons">${actions || '<span style="color:var(--text-muted)">—</span>'}</div></td>
        </tr>
      `;
    });
  } catch (err) { alert('Error loading bookings: ' + err.message); }
}

async function openBookingModal() {
  document.getElementById('bookingForm').reset();
  document.getElementById('bookingDate').value = new Date().toISOString().slice(0, 10);
  selectedTimeSlotVal = '';
  document.getElementById('selectedTimeSlot').value = '';
  document.getElementById('timeSlotsContainer').innerHTML = '<div style="color:var(--text-muted);">Select date and net lane first...</div>';

  const playerGroup = document.getElementById('bookingPlayerSelectGroup');
  const playerSelect = document.getElementById('bookingPlayerSelect');
  const coachSelect = document.getElementById('bookingCoachSelect');

  if (CURRENT_USER.role === 'admin') {
    playerGroup.style.display = 'block';
    playerSelect.required = true;
    try {
      const res = await API.get('api/players.php');
      playerSelect.innerHTML = '<option value="">Select player...</option>';
      res.data.forEach(p => { playerSelect.innerHTML += `<option value="${p.id}">${escHtml(p.name)} (${escHtml(p.category)})</option>`; });
    } catch (e) {}
  } else {
    playerGroup.style.display = 'none';
    playerSelect.required = false;
  }

  try {
    const res = await API.get('api/coaches.php');
    coachSelect.innerHTML = '<option value="">No Coach (Self Practice)</option>';
    res.data.forEach(c => { coachSelect.innerHTML += `<option value="${c.id}">${escHtml(c.name)} — ${escHtml(c.specialization)}</option>`; });
  } catch (e) {}

  openModal('bookingModal');
}

async function refreshSlots() {
  const date = document.getElementById('bookingDate').value;
  const netNo = document.getElementById('bookingNetSelect').value;
  const container = document.getElementById('timeSlotsContainer');
  container.innerHTML = '<div style="color:var(--text-muted);">Loading slots...</div>';
  selectedTimeSlotVal = '';
  document.getElementById('selectedTimeSlot').value = '';
  if (!date || !netNo) return;

  const allSlots = ['07:00 - 09:00','09:00 - 11:00','11:00 - 13:00','14:00 - 16:00','16:00 - 18:00','18:00 - 20:00'];
  try {
    const res = await API.get(`api/bookings.php?booking_date=${date}&net_no=${netNo}`);
    const occupied = res.data
      .filter(b => b.status === 'Approved' || b.status === 'Pending')
      .map(b => b.time_slot);
    container.innerHTML = '';
    allSlots.forEach(slot => {
      const isOccupied = occupied.includes(slot);
      const el = document.createElement('div');
      el.className = `slot-item${isOccupied ? ' occupied' : ''}`;
      el.innerHTML = `<div class="slot-time">${slot}</div><div class="slot-net" style="color:${isOccupied ? 'var(--status-cancelled)' : 'var(--accent-green)'}">${isOccupied ? 'OCCUPIED' : 'Available'}</div>`;
      if (!isOccupied) {
        el.addEventListener('click', () => {
          document.querySelectorAll('.slot-item').forEach(i => i.classList.remove('selected'));
          el.classList.add('selected');
          selectedTimeSlotVal = slot;
          document.getElementById('selectedTimeSlot').value = slot;
        });
      }
      container.appendChild(el);
    });
  } catch (err) { container.innerHTML = '<div style="color:var(--status-cancelled);">Failed to load slots.</div>'; }
}

async function saveBooking(e) {
  e.preventDefault();
  if (!selectedTimeSlotVal) { alert('Please select a time slot!'); return; }
  const payload = {
    booking_date: document.getElementById('bookingDate').value,
    net_no: parseInt(document.getElementById('bookingNetSelect').value),
    time_slot: selectedTimeSlotVal,
    coach_id: document.getElementById('bookingCoachSelect').value || null
  };
  if (CURRENT_USER.role === 'admin') {
    payload.player_id = parseInt(document.getElementById('bookingPlayerSelect').value);
    if (!payload.player_id) { alert('Please select a player!'); return; }
  }
  try {
    const res = await API.post('api/bookings.php', payload);
    if (res && res.success) { alert(res.message); closeModal('bookingModal'); loadBookingsList(); }
  } catch (err) { alert(err.message); }
}

async function updateBookingStatus(id, status) {
  if (confirm(`Change booking status to ${status}?`)) {
    try {
      const res = await API.put(`api/bookings.php?id=${id}&action=status`, { status });
      if (res && res.success) { alert(res.message); loadBookingsList(); }
    } catch (err) { alert(err.message); }
  }
}

async function openAssignCoachModal(bookingId, currentCoachId) {
  try {
    const res = await API.get('api/coaches.php');
    document.getElementById('profileModalTitle').textContent = 'Assign Coach to Session';
    let html = `<form id="assignCoachForm" style="display:flex;flex-direction:column;gap:15px;">
      <input type="hidden" id="assignBookingId" value="${bookingId}">
      <div class="form-group">
        <label>Select Coach:</label>
        <select id="assignCoachSelect" class="form-control" style="margin-top:6px;">
          <option value="">No Coach</option>`;
    res.data.forEach(c => { html += `<option value="${c.id}" ${currentCoachId == c.id ? 'selected' : ''}>${escHtml(c.name)} (${escHtml(c.specialization)})</option>`; });
    html += `</select></div>
      <button type="submit" class="btn btn-primary">Save Assignment</button>
    </form>`;
    document.getElementById('profileModalBody').innerHTML = html;
    document.getElementById('assignCoachForm').addEventListener('submit', async e2 => {
      e2.preventDefault();
      const bId = document.getElementById('assignBookingId').value;
      const cId = document.getElementById('assignCoachSelect').value || null;
      try {
        const res2 = await API.put(`api/bookings.php?id=${bId}&action=assign`, { coach_id: cId });
        if (res2 && res2.success) { alert(res2.message); closeModal('profileModal'); loadBookingsList(); }
      } catch (err) { alert(err.message); }
    });
    openModal('profileModal');
  } catch (err) { alert(err.message); }
}

function openFeedbackModal(bookingId, playerName, sessionDetails) {
  document.getElementById('feedbackForm').reset();
  document.getElementById('feedbackBookingId').value = bookingId;
  document.getElementById('feedbackPlayerName').value = playerName;
  document.getElementById('feedbackSessionDetails').value = sessionDetails;
  openModal('feedbackModal');
}

async function saveSessionFeedback(e) {
  e.preventDefault();
  const id = document.getElementById('feedbackBookingId').value;
  const feedback = document.getElementById('sessionFeedbackNotes').value.trim();
  try {
    const res = await API.put(`api/bookings.php?id=${id}&action=feedback`, { coach_feedback: feedback });
    if (res && res.success) { alert(res.message); closeModal('feedbackModal'); loadBookingsList(); }
  } catch (err) { alert(err.message); }
}

// ====== ATTENDANCE ======
async function loadAttendanceSheet() {
  const date = document.getElementById('attendanceDatePicker').value;
  try {
    const res = await API.get(`api/attendance.php?date=${date}`);
    if (!res || !res.success) return;
    const tbody = document.getElementById('attendanceListBody');
    tbody.innerHTML = '';
    if (res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center" style="color:var(--text-secondary);padding:20px;">No players registered.</td></tr>`;
      return;
    }
    res.data.forEach(r => {
      tbody.innerHTML += `
        <tr>
          <td><strong>${r.player_id}</strong></td>
          <td>${escHtml(r.player_name)}</td>
          <td><span class="badge badge-outline">${escHtml(r.category)}</span></td>
          <td class="text-center">
            <div style="display:inline-flex;gap:20px;">
              <label style="cursor:pointer;display:flex;align-items:center;gap:6px;">
                <input type="radio" name="att_${r.player_id}" value="Present" data-player-id="${r.player_id}" ${r.status === 'Present' ? 'checked' : ''}>
                <span class="badge badge-present">Present</span>
              </label>
              <label style="cursor:pointer;display:flex;align-items:center;gap:6px;">
                <input type="radio" name="att_${r.player_id}" value="Absent" data-player-id="${r.player_id}" ${r.status === 'Absent' ? 'checked' : ''}>
                <span class="badge badge-absent">Absent</span>
              </label>
            </div>
          </td>
        </tr>
      `;
    });
  } catch (err) { alert(err.message); }
}

async function saveAttendanceSheet() {
  const date = document.getElementById('attendanceDatePicker').value;
  const records = [];
  document.querySelectorAll('#attendanceListBody input[type="radio"]:checked').forEach(input => {
    records.push({ player_id: parseInt(input.dataset.playerId), status: input.value });
  });
  if (records.length === 0) { alert('Please mark at least one player!'); return; }
  try {
    const res = await API.post('api/attendance.php', { date, records });
    if (res && res.success) { alert(res.message); loadAttendanceSheet(); }
  } catch (err) { alert(err.message); }
}

// ====== PAYMENTS ======
async function loadPaymentsLedger() {
  try {
    const res = await API.get('api/payments.php');
    const tbody = document.getElementById('paymentsListBody');
    tbody.innerHTML = '';
    if (!res || !res.success || res.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="color:var(--text-secondary);padding:20px;">No transactions recorded.</td></tr>`;
    } else {
      res.data.forEach(p => {
        tbody.innerHTML += `
          <tr>
            <td><strong>${p.payment_date}</strong></td>
            <td><code style="background:var(--bg-primary);padding:3px 7px;border-radius:5px;font-size:0.82rem;">${p.receipt_no}</code></td>
            <td>${escHtml(p.player_name)}<br><small style="color:var(--text-muted)">${escHtml(p.player_email)}</small></td>
            <td style="color:var(--accent-green);font-weight:700;">LKR ${parseFloat(p.amount).toLocaleString('en', {minimumFractionDigits:2})}</td>
            <td><button class="btn btn-outline" style="padding:4px 10px;font-size:0.78rem;" onclick="printReceipt('${p.receipt_no}','${escHtml(p.player_name)}','${p.payment_date}','${p.amount}')"><i class="fa-solid fa-print"></i> Print</button></td>
          </tr>
        `;
      });
    }

    // Pending list (admin only)
    const pendingTbody = document.getElementById('pendingPaymentsListBody');
    if (pendingTbody && CURRENT_USER.role === 'admin') {
      const pendingRes = await API.get('api/payments.php?action=pending');
      pendingTbody.innerHTML = '';
      if (!pendingRes || !pendingRes.success || pendingRes.data.length === 0) {
        pendingTbody.innerHTML = `<tr><td colspan="2" class="text-center" style="color:var(--status-approved);font-weight:500;padding:20px;">All members paid! ✓</td></tr>`;
      } else {
        pendingRes.data.forEach(m => {
          pendingTbody.innerHTML += `<tr><td><strong>${escHtml(m.player_name)}</strong><br><span class="badge badge-outline" style="margin-top:3px;">${escHtml(m.category)}</span></td><td>${escHtml(m.phone)}</td></tr>`;
        });
      }
    }
  } catch (err) { alert(err.message); }
}

async function openPaymentModal() {
  document.getElementById('paymentForm').reset();
  document.getElementById('paymentDate').value = new Date().toISOString().slice(0, 10);
  const select = document.getElementById('paymentPlayerSelect');
  select.innerHTML = '<option value="">Loading...</option>';
  try {
    const res = await API.get('api/players.php');
    select.innerHTML = '<option value="">Choose player...</option>';
    res.data.forEach(p => { select.innerHTML += `<option value="${p.id}">${escHtml(p.name)} (${escHtml(p.category)})</option>`; });
    openModal('paymentModal');
  } catch (err) { alert(err.message); }
}

async function savePaymentRecord(e) {
  e.preventDefault();
  const payload = {
    player_id: parseInt(document.getElementById('paymentPlayerSelect').value),
    amount: parseFloat(document.getElementById('paymentAmount').value),
    payment_date: document.getElementById('paymentDate').value
  };
  if (!payload.player_id) { alert('Please select a player!'); return; }
  try {
    const res = await API.post('api/payments.php', payload);
    if (res && res.success) {
      alert(`Payment recorded!\nReceipt: ${res.receipt_no}`);
      closeModal('paymentModal');
      loadPaymentsLedger();
    }
  } catch (err) { alert(err.message); }
}

function printReceipt(receiptNo, name, date, amount) {
  const w = window.open('', '_blank', 'width=550,height=480');
  w.document.write(`<!DOCTYPE html><html><head><title>Receipt - ${receiptNo}</title>
  <style>
    body{font-family:Arial,sans-serif;padding:30px;color:#333}
    .box{border:2px solid #e2e8f0;border-radius:8px;padding:25px;max-width:480px;margin:auto}
    .hdr{text-align:center;border-bottom:2px solid #10b981;padding-bottom:12px;margin-bottom:18px}
    .hdr h2{margin:0;color:#0f172a;font-size:1.3rem}
    .hdr p{margin:4px 0 0;color:#64748b;font-size:0.85rem}
    .row{display:flex;justify-content:space-between;margin-bottom:10px;font-size:0.9rem}
    .lbl{font-weight:bold;color:#475569}
    .amt{background:#f1f5f9;padding:15px;border-radius:6px;text-align:center;margin-top:15px}
    .amt-val{font-size:1.6rem;font-weight:800;color:#10b981}
    .ftr{text-align:center;font-size:0.78rem;color:#94a3b8;margin-top:22px;border-top:1px dashed #cbd5e1;padding-top:14px}
    @media print { button { display:none; } }
  </style></head><body>
  <div class="box">
    <div class="hdr"><h2>🏏 ${ACADEMY_NAME.toUpperCase()}</h2><p>Official Payment Receipt</p></div>
    <div class="row"><span class="lbl">Receipt No:</span><span><code>${receiptNo}</code></span></div>
    <div class="row"><span class="lbl">Date:</span><span>${date}</span></div>
    <div class="row"><span class="lbl">Player:</span><span>${name}</span></div>
    <div class="amt"><div style="font-size:0.85rem;color:#475569;font-weight:600;">AMOUNT RECEIVED</div><div class="amt-val">LKR ${parseFloat(amount).toLocaleString('en',{minimumFractionDigits:2})}</div></div>
    <div class="ftr"><p>Thank you for your payment!</p><p>${ACADEMY_NAME} & Net Practice Management System</p></div>
  </div>
  <script>window.onload=function(){window.print();}<\/script></body></html>`);
  w.document.close();
}

// ====== MODAL UTILITIES ======
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function doLogout(e) {
  e.preventDefault();
  fetch('api/auth.php?action=logout', { method: 'POST' })
    .then(() => window.location.href = 'index.php');
  return false;
}

// Settings submit handler (Admin only)
async function saveSettings(e) {
  e.preventDefault();
  const nameInput = document.getElementById('settingAcademyName');
  if (!nameInput) return;
  const newName = nameInput.value.trim();
  const btn = document.getElementById('saveSettingsBtn');
  if (btn) {
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
  }
  try {
    const res = await API.post('api/settings.php', { academy_name: newName });
    if (res && res.success) {
      alert(res.message);
      window.location.reload();
    } else {
      alert(res.message || 'Failed to save settings.');
    }
  } catch (err) {
    alert('Error: ' + err.message);
  } finally {
    if (btn) {
      btn.innerHTML = '<i class="fa-solid fa-save"></i> Save Settings';
      btn.disabled = false;
    }
  }
}

// HTML Escape helper
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
