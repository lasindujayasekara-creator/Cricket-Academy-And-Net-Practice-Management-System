// Chart.js Analytics Rendering for PHP Cricket Academy Dashboard

function destroyCurrentChart() {
  if (window.currentChartInstance) {
    window.currentChartInstance.destroy();
    window.currentChartInstance = null;
  }
}

// Admin: Monthly Income line chart
async function initAdminCharts() {
  destroyCurrentChart();
  const ctx = document.getElementById('overviewChart');
  if (!ctx) return;
  try {
    const res = await fetch('api/payments.php?action=analytics');
    const data = await res.json();
    if (!data.success) return;
    const labels = data.data.length > 0 ? data.data.map(d => d.month) : ['No Data'];
    const values = data.data.length > 0 ? data.data.map(d => parseFloat(d.total_amount)) : [0];

    window.currentChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Monthly Income (LKR)',
          data: values,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16,185,129,0.08)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#10b981',
          pointRadius: 5
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#94a3b8', font: { family: 'Outfit' } } } },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8' } },
          y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8' } }
        }
      }
    });
  } catch (err) { console.error('Chart init error:', err); }
}

// Coach: Feedback compliance doughnut chart
function initCoachChart(stats) {
  destroyCurrentChart();
  const ctx = document.getElementById('overviewChart');
  if (!ctx) return;
  const completed = Math.max(0, stats.totalSessions - stats.pendingFeedbackCount);
  window.currentChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Feedback Done', 'Feedback Pending'],
      datasets: [{ data: [completed, stats.pendingFeedbackCount], backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: { legend: { position: 'right', labels: { color: '#94a3b8', font: { family: 'Outfit' }, padding: 15 } } }
    }
  });
}

// Player: Attendance rate doughnut chart
function initPlayerChart(stats) {
  destroyCurrentChart();
  const ctx = document.getElementById('overviewChart');
  if (!ctx) return;
  const present = stats.attendanceRate;
  const absent = 100 - present;
  window.currentChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: [`Present ${present}%`, `Absent ${absent}%`],
      datasets: [{ data: [present, absent], backgroundColor: ['#10b981', '#ef4444'], borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '70%',
      plugins: { legend: { position: 'right', labels: { color: '#94a3b8', font: { family: 'Outfit' }, padding: 15 } } }
    }
  });
}
