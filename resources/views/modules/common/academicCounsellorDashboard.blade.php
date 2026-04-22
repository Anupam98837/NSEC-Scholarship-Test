<style>
  .dash-wrap{
    max-width:1180px;
    margin:16px auto 40px;
  }

  .dash-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
    flex-wrap:wrap;
  }
  .dash-head-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .dash-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-1);
  }
  .dash-pill-icon{
    width:26px;
    height:26px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--t-primary);
    color:var(--primary-color);
  }
  .dash-title-main{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.12rem;
  }
  .dash-title-sub{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .dash-head-right{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  #dashboardPeriod{
    font-weight:500;
    color:var(--secondary-color);
  }

  .dash-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }
  .dash-filters{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .filter-chip{
    border:1px dashed var(--line-strong);
    border-radius:999px;
    padding:6px 10px;
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:8px;
  }
  .filter-chip-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  #periodFilter{
    min-width:130px;
    height:32px;
    padding:4px 8px;
    font-size:var(--fs-13);
    border-radius:999px;
  }

  .stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    margin-bottom:18px;
  }
  .stat-card{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    padding:14px 14px 12px;
    box-shadow:var(--shadow-2);
    display:flex;
    flex-direction:column;
    gap:4px;
    min-height:122px;
  }
  .stat-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
  }
  .stat-icon{
    width:40px;
    height:40px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--surface-2);
    color:var(--secondary-color);
    flex-shrink:0;
  }
  .stat-kicker{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
  }
  .stat-value{
    font-size:1.7rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.1;
  }
  .stat-label{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .stat-meta{
    font-size:var(--fs-12);
    color:var(--secondary-color);
    margin-top:2px;
  }

  .dash-panel{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    box-shadow:var(--shadow-2);
    padding:14px 14px 12px;
    height:100%;
  }
  .dash-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .dash-panel-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
    font-size:var(--fs-15);
  }
  .dash-panel-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  .chart-shell{
    position:relative;
    height:250px;
  }

  .metric-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
  }
  .metric-card{
    border-radius:12px;
    padding:10px 10px 8px;
    background:var(--surface-2);
  }
  .metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:2px;
  }
  .metric-value{
    font-size:1.1rem;
    font-weight:600;
    color:var(--ink);
  }

  .top-list{
    max-height:270px;
    overflow:auto;
  }
  .top-item{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:8px;
    padding:9px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .top-item:last-child{
    border-bottom:none;
  }
  .top-item-main{
    flex:1;
    min-width:0;
  }
  .top-item-title{
    font-size:var(--fs-13);
    font-weight:600;
    color:var(--ink);
    margin-bottom:2px;
  }
  .top-item-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
    line-height:1.45;
  }
  .top-item-badge{
    font-size:var(--fs-12);
    font-weight:600;
    color:var(--secondary-color);
    white-space:nowrap;
  }

  .student-table-wrap{
    overflow:auto;
    border-radius:14px;
    border:1px solid var(--line-soft);
  }
  .student-table{
    width:100%;
    border-collapse:collapse;
    min-width:820px;
  }
  .student-table th,
  .student-table td{
    padding:10px 12px;
    border-bottom:1px solid var(--line-soft);
    font-size:var(--fs-13);
    vertical-align:middle;
  }
  .student-table th{
    background:var(--surface-2);
    color:var(--muted-color);
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.04em;
    font-size:var(--fs-12);
  }
  .student-table tr:last-child td{
    border-bottom:none;
  }

  .status-badge{
    display:inline-flex;
    align-items:center;
    padding:4px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    color:var(--secondary-color);
  }

  .dash-empty{
    text-align:center;
    color:var(--muted-color);
    padding:24px 12px;
    font-size:var(--fs-13);
  }

  @media (max-width: 768px){
    .metric-grid{
      grid-template-columns:1fr 1fr;
    }
  }

  @media (max-width: 576px){
    .metric-grid{
      grid-template-columns:1fr;
    }
  }
</style>

<div class="dash-wrap">
  <div class="dash-head">
    <div class="dash-head-left">
      <div class="dash-pill">
        <div class="dash-pill-icon">
          <i class="fa-solid fa-user-group"></i>
        </div>
        <div>
          <div class="dash-title-main">Academic Counsellor Dashboard</div>
          <div class="dash-title-sub">Assigned students, engagement, progress and follow-up view</div>
        </div>
      </div>
    </div>
    <div class="dash-head-right">
      <span class="text-muted me-1">Range:</span>
      <span id="dashboardPeriod">Last 30 days</span>
    </div>
  </div>

  <div class="dash-toolbar">
    <div class="dash-filters">
      <div class="filter-chip">
        <span class="filter-chip-label">Period</span>
        <select id="periodFilter" class="form-select form-select-sm">
          <option value="7d">Last 7 days</option>
          <option value="30d" selected>Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last 12 months</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light btn-sm" id="btnRefresh">
        <span class="me-1"><i class="fa-solid fa-rotate-right"></i></span>
        Refresh
      </button>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="stat-kicker">Students</div>
      </div>
      <div class="stat-value" id="totalStudentsAssigned">0</div>
      <div class="stat-label">Total Assigned Students</div>
      <div class="stat-meta" id="todayNewStudents">0 assigned today</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
        <div class="stat-kicker">New</div>
      </div>
      <div class="stat-value" id="newStudentsInPeriod">0</div>
      <div class="stat-label">Assigned In Selected Period</div>
      <div class="stat-meta" id="activeStatusMeta">0 active students</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon"><i class="fa-solid fa-pen"></i></div>
        <div class="stat-kicker">Attempts</div>
      </div>
      <div class="stat-value" id="totalAttemptsMyStudents">0</div>
      <div class="stat-label">Attempts By My Students</div>
      <div class="stat-meta" id="todayAttemptsMyStudents">0 started today</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
        <div class="stat-kicker">Average</div>
      </div>
      <div class="stat-value" id="avgPercentageMyStudents">0%</div>
      <div class="stat-label">Average Score</div>
      <div class="stat-meta" id="todayCompletedMyStudents">0 completed today</div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Assignments Over Time</div>
            <div class="dash-panel-sub">Daily student assignment trend in the selected range</div>
          </div>
        </div>
        <div class="chart-shell">
          <canvas id="assignmentsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Status Snapshot</div>
          <div class="dash-panel-sub">Current student status distribution</div>
        </div>
        <div class="metric-grid">
          <div class="metric-card">
            <div class="metric-label">Active</div>
            <div class="metric-value" id="metricActiveStudents">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Inactive</div>
            <div class="metric-value" id="metricInactiveStudents">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Blocked</div>
            <div class="metric-value" id="metricBlockedStudents">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Completion Rate</div>
            <div class="metric-value" id="metricCompletionRate">0%</div>
          </div>
        </div>
      </div>

      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Students Needing Attention</div>
          <div class="dash-panel-sub">Assigned students with no attempts yet</div>
        </div>
        <div id="inactiveStudentsList" class="top-list">
          <div class="dash-empty">Loading students…</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Attempts Over Time</div>
            <div class="dash-panel-sub">Activity from your assigned students</div>
          </div>
        </div>
        <div class="chart-shell">
          <canvas id="attemptsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Average Score Over Time</div>
            <div class="dash-panel-sub">Performance trend of your assigned students</div>
          </div>
        </div>
        <div class="chart-shell">
          <canvas id="scoresChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Top Students</div>
          <div class="dash-panel-sub">Best performers in the selected period</div>
        </div>
        <div id="topStudentsList" class="top-list">
          <div class="dash-empty">Loading students…</div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Notifications</div>
          <div class="dash-panel-sub">Latest active updates</div>
        </div>
        <div id="notificationsList" class="top-list">
          <div class="dash-empty">Loading notifications…</div>
        </div>
      </div>
    </div>
  </div>

  <div class="dash-panel">
    <div class="dash-panel-head">
      <div>
        <div class="dash-panel-title">My Students</div>
        <div class="dash-panel-sub">Full assigned student overview with attempts and scores</div>
      </div>
    </div>

    <div class="student-table-wrap">
      <table class="student-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Status</th>
            <th>Attempts</th>
            <th>Completed</th>
            <th>Average %</th>
            <th>Best %</th>
            <th>Assigned</th>
          </tr>
        </thead>
        <tbody id="myStudentsTableBody">
          <tr>
            <td colspan="7" class="dash-empty">Loading students…</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastSuccessText">Dashboard updated</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>

  <div id="toastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastErrorText">Failed to load dashboard data</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.initializeAcademicCounsellorDashboard = window.initializeCounsellorDashboard = function () {
  const root =
    document.getElementById('dashCounsellor') ||
    document.getElementById('dashAcademicCounsellor') ||
    document.querySelector('[data-dashboard="academic-counsellor"]');

  if (!root) {
    console.warn('[DASH] Counsellor dashboard root not found');
    return;
  }

  (function () {
    const ENDPOINT = "{{ url('api/dashboard/counsellor') }}";

    let period = '30d';
    let dashboardData = null;
    let assignmentsChart = null;
    let attemptsChart = null;
    let scoresChart = null;

    const periodFilter = document.getElementById('periodFilter');
    const dashboardPeriod = document.getElementById('dashboardPeriod');
    const btnRefresh = document.getElementById('btnRefresh');

    const totalStudentsAssignedEl = document.getElementById('totalStudentsAssigned');
    const newStudentsInPeriodEl = document.getElementById('newStudentsInPeriod');
    const totalAttemptsMyStudentsEl = document.getElementById('totalAttemptsMyStudents');
    const avgPercentageMyStudentsEl = document.getElementById('avgPercentageMyStudents');

    const todayNewStudentsEl = document.getElementById('todayNewStudents');
    const activeStatusMetaEl = document.getElementById('activeStatusMeta');
    const todayAttemptsMyStudentsEl = document.getElementById('todayAttemptsMyStudents');
    const todayCompletedMyStudentsEl = document.getElementById('todayCompletedMyStudents');

    const metricActiveStudentsEl = document.getElementById('metricActiveStudents');
    const metricInactiveStudentsEl = document.getElementById('metricInactiveStudents');
    const metricBlockedStudentsEl = document.getElementById('metricBlockedStudents');
    const metricCompletionRateEl = document.getElementById('metricCompletionRate');

    const inactiveStudentsListEl = document.getElementById('inactiveStudentsList');
    const topStudentsListEl = document.getElementById('topStudentsList');
    const notificationsListEl = document.getElementById('notificationsList');
    const myStudentsTableBodyEl = document.getElementById('myStudentsTableBody');

    const toastSuccessNode = document.getElementById('toastSuccess');
    const toastErrorNode = document.getElementById('toastError');

    const toastSuccess = toastSuccessNode ? new bootstrap.Toast(toastSuccessNode) : null;
    const toastError = toastErrorNode ? new bootstrap.Toast(toastErrorNode) : null;
    const toastSuccessText = document.getElementById('toastSuccessText');
    const toastErrorText = document.getElementById('toastErrorText');

    function getToken() {
      return sessionStorage.getItem('token') || localStorage.getItem('token') || '';
    }

    function authHeaders() {
      return {
        'Authorization': 'Bearer ' + getToken(),
        'Accept': 'application/json'
      };
    }

    function showSuccess(msg) {
      if (toastSuccessText) toastSuccessText.textContent = msg;
      if (toastSuccess) toastSuccess.show();
    }

    function showError(msg) {
      if (toastErrorText) toastErrorText.textContent = msg;
      if (toastError) toastError.show();
    }

    function escapeHtml(str) {
      return String(str ?? '').replace(/[&<>"']/g, function (s) {
        return ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        })[s];
      });
    }

    function periodLabel(value) {
      switch (value) {
        case '7d': return 'Last 7 days';
        case '30d': return 'Last 30 days';
        case '90d': return 'Last 90 days';
        case '1y': return 'Last 12 months';
        default: return 'Selected range';
      }
    }

    function fmtDateShort(value) {
      if (!value) return '';
      const d = new Date(value);
      if (isNaN(d.getTime())) return value;
      return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
    }

    function fmtDateTime(value) {
      if (!value) return '';
      const d = new Date(value);
      if (isNaN(d.getTime())) return value;
      return d.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function num(value) {
      return Number(value || 0);
    }

    function pct(value) {
      const n = Number(value || 0);
      return Number.isFinite(n) ? n.toFixed(1) + '%' : '0.0%';
    }

    function setLoadingState() {
      if (inactiveStudentsListEl) inactiveStudentsListEl.innerHTML = '<div class="dash-empty">Loading students…</div>';
      if (topStudentsListEl) topStudentsListEl.innerHTML = '<div class="dash-empty">Loading students…</div>';
      if (notificationsListEl) notificationsListEl.innerHTML = '<div class="dash-empty">Loading notifications…</div>';
      if (myStudentsTableBodyEl) myStudentsTableBodyEl.innerHTML = '<tr><td colspan="7" class="dash-empty">Loading students…</td></tr>';
    }

    function setErrorState() {
      if (inactiveStudentsListEl) inactiveStudentsListEl.innerHTML = '<div class="dash-empty text-danger">Failed to load students</div>';
      if (topStudentsListEl) topStudentsListEl.innerHTML = '<div class="dash-empty text-danger">Failed to load students</div>';
      if (notificationsListEl) notificationsListEl.innerHTML = '<div class="dash-empty text-danger">Failed to load notifications</div>';
      if (myStudentsTableBodyEl) myStudentsTableBodyEl.innerHTML = '<tr><td colspan="7" class="dash-empty text-danger">Failed to load students</td></tr>';
    }

    async function fetchDashboard() {
      try {
        setLoadingState();

        const res = await fetch(ENDPOINT + '?' + new URLSearchParams({ period }).toString(), {
          headers: authHeaders()
        });

        const json = await res.json().catch(() => ({}));
        console.log('[DASH] counsellor response', json);

        if (!res.ok || String(json.status || '').toLowerCase() !== 'success') {
          throw new Error(json.message || 'Failed to load dashboard data');
        }

        dashboardData = json.data || {};
        renderDashboard();
        showSuccess('Dashboard updated');
      } catch (err) {
        console.error('[DASH] counsellor error', err);
        setErrorState();
        showError(err.message || 'Failed to load dashboard data');
      }
    }

    function renderDashboard() {
      renderRange();
      renderStats();
      renderStatusSnapshot();
      renderAssignmentsChart();
      renderAttemptsChart();
      renderScoresChart();
      renderInactiveStudents();
      renderTopStudents();
      renderNotifications();
      renderMyStudents();
    }

    function renderRange() {
      const dr = dashboardData?.date_range || {};
      const start = fmtDateShort(dr.start);
      const end = fmtDateShort(dr.end);
      const label = periodLabel(dr.period || period);
      if (dashboardPeriod) {
        dashboardPeriod.textContent = (start && end) ? `${label} · ${start} – ${end}` : label;
      }
    }

    function renderStats() {
      const summary = dashboardData?.summary_counts || {};
      const quick = dashboardData?.quick_stats || {};
      const statuses = summary.students_by_status || {};

      if (totalStudentsAssignedEl) totalStudentsAssignedEl.textContent = num(summary.total_students_assigned);
      if (newStudentsInPeriodEl) newStudentsInPeriodEl.textContent = num(summary.new_students_in_period);
      if (totalAttemptsMyStudentsEl) totalAttemptsMyStudentsEl.textContent = num(summary.total_attempts_my_students);
      if (avgPercentageMyStudentsEl) avgPercentageMyStudentsEl.textContent = pct(summary.average_percentage_my_students);

      if (todayNewStudentsEl) todayNewStudentsEl.textContent = `${num(quick.today_new_students)} assigned today`;
      if (activeStatusMetaEl) activeStatusMetaEl.textContent = `${num(statuses.active)} active students`;
      if (todayAttemptsMyStudentsEl) todayAttemptsMyStudentsEl.textContent = `${num(quick.today_attempts_my_students)} started today`;
      if (todayCompletedMyStudentsEl) todayCompletedMyStudentsEl.textContent = `${num(quick.today_completed_my_students)} completed today`;
    }

    function renderStatusSnapshot() {
      const summary = dashboardData?.summary_counts || {};
      const statuses = summary.students_by_status || {};
      const totalAttempts = num(summary.total_attempts_my_students);
      const completedAttempts = num(summary.completed_attempts_my_students);
      const completionRate = totalAttempts > 0 ? ((completedAttempts * 100) / totalAttempts) : 0;

      if (metricActiveStudentsEl) metricActiveStudentsEl.textContent = num(statuses.active);
      if (metricInactiveStudentsEl) metricInactiveStudentsEl.textContent = num(statuses.inactive);
      if (metricBlockedStudentsEl) metricBlockedStudentsEl.textContent = num(statuses.blocked);
      if (metricCompletionRateEl) metricCompletionRateEl.textContent = completionRate.toFixed(1) + '%';
    }

    function makeLineChart(canvasId, rows, label, borderColor, bgColor, valueKey, yMax = null, suffix = '') {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return null;

      const labels = (rows || []).map(row => fmtDateShort(row.date));
      const values = (rows || []).map(row => Number(row[valueKey]) || 0);

      return new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label,
            data: values,
            borderColor,
            backgroundColor: bgColor,
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 2.5,
            pointHoverRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ...(yMax !== null ? { max: yMax } : {}),
              ticks: suffix ? {
                callback: function (v) { return v + suffix; }
              } : {}
            },
            x: { grid: { display: false } }
          }
        }
      });
    }

    function renderAssignmentsChart() {
      if (assignmentsChart) assignmentsChart.destroy();
      assignmentsChart = makeLineChart('assignmentsChart', dashboardData?.assignments_over_time || [], 'Assignments', '#9E363A', 'rgba(158,54,58,0.14)', 'count');
    }

    function renderAttemptsChart() {
      if (attemptsChart) attemptsChart.destroy();
      attemptsChart = makeLineChart('attemptsChart', dashboardData?.attempts_over_time || [], 'Attempts', '#1f9790', 'rgba(31,151,144,0.16)', 'count');
    }

    function renderScoresChart() {
      if (scoresChart) scoresChart.destroy();
      scoresChart = makeLineChart('scoresChart', dashboardData?.scores_over_time || [], 'Average Score', '#0ea5e9', 'rgba(14,165,233,0.14)', 'avg_percentage', 100, '%');
    }

    function renderInactiveStudents() {
      const rows = dashboardData?.inactive_students || [];
      if (!inactiveStudentsListEl) return;

      if (!rows.length) {
        inactiveStudentsListEl.innerHTML = '<div class="dash-empty">No inactive students found</div>';
        return;
      }

      inactiveStudentsListEl.innerHTML = rows.map(function (row) {
        return `
          <div class="top-item">
            <div class="top-item-main">
              <div class="top-item-title">${escapeHtml(row.student_name || 'Student')}</div>
              <div class="top-item-meta">${escapeHtml(row.student_email || '')}</div>
            </div>
            <div class="top-item-badge">No attempts</div>
          </div>
        `;
      }).join('');
    }

    function renderTopStudents() {
      const rows = dashboardData?.top_students || [];
      if (!topStudentsListEl) return;

      if (!rows.length) {
        topStudentsListEl.innerHTML = '<div class="dash-empty">No performance data found</div>';
        return;
      }

      topStudentsListEl.innerHTML = rows.map(function (row, index) {
        const avg = Number(row.avg_percentage || 0).toFixed(1);
        const best = Number(row.best_percentage || 0).toFixed(1);

        return `
          <div class="top-item">
            <div class="top-item-main">
              <div class="top-item-title">${index + 1}. ${escapeHtml(row.name || 'Student')}</div>
              <div class="top-item-meta">
                ${num(row.attempts)} attempts • Avg ${avg}% • Best ${best}%<br>
                ${escapeHtml(row.email || '')}
              </div>
            </div>
            <div class="top-item-badge">${avg}%</div>
          </div>
        `;
      }).join('');
    }

    function renderNotifications() {
      const rows = dashboardData?.notifications?.latest || [];
      if (!notificationsListEl) return;

      if (!rows.length) {
        notificationsListEl.innerHTML = '<div class="dash-empty">No notifications available</div>';
        return;
      }

      notificationsListEl.innerHTML = rows.map(function (row) {
        return `
          <div class="top-item">
            <div class="top-item-main">
              <div class="top-item-title">${escapeHtml(row.title || 'Notification')}</div>
              <div class="top-item-meta">${escapeHtml(row.message || '')}<br>${fmtDateTime(row.created_at)}</div>
            </div>
            <div class="top-item-badge">${escapeHtml(row.priority || 'normal')}</div>
          </div>
        `;
      }).join('');
    }

    function renderMyStudents() {
      const rows = dashboardData?.my_students || [];
      if (!myStudentsTableBodyEl) return;

      if (!rows.length) {
        myStudentsTableBodyEl.innerHTML = '<tr><td colspan="7" class="dash-empty">No assigned students found</td></tr>';
        return;
      }

      myStudentsTableBodyEl.innerHTML = rows.map(function (row) {
        return `
          <tr>
            <td>
              <div style="font-weight:600;color:var(--ink)">${escapeHtml(row.student_name || 'Student')}</div>
              <div style="font-size:12px;color:var(--muted-color)">${escapeHtml(row.student_email || row.student_phone || '')}</div>
            </td>
            <td><span class="status-badge">${escapeHtml(row.student_status || row.assignment_status || '—')}</span></td>
            <td>${num(row.total_attempts)}</td>
            <td>${num(row.completed_attempts)}</td>
            <td>${Number(row.avg_percentage || 0).toFixed(2)}%</td>
            <td>${Number(row.best_percentage || 0).toFixed(2)}%</td>
            <td>${fmtDateShort(row.assigned_at)}</td>
          </tr>
        `;
      }).join('');
    }

    if (periodFilter) {
      periodFilter.addEventListener('change', function () {
        period = this.value || '30d';
        fetchDashboard();
      });
    }

    if (btnRefresh) {
      btnRefresh.addEventListener('click', function () {
        fetchDashboard();
      });
    }

    fetchDashboard();
  })();
};
</script>