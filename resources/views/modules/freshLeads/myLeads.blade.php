{{-- resources/views/modules/leads/my-leads.blade.php --}}
@section('title','My Leads')

@push('styles')
<style>

.ml-wrap {
  padding: 32px 24px 60px;
  max-width: 1360px;
  margin: 0 auto;
}

/* ── Header ── */
.ml-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}
.ml-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--ml-font-head);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--success-color, #16a34a);
  background: var(--t-success, rgba(22,163,74,.08));
  border: 1px solid rgba(22,163,74,.22);
  padding: 4px 12px;
  border-radius: var(--radius-1);
  margin-bottom: 10px;
}
.ml-eyebrow .dot-pulse {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--success-color, #16a34a);
  animation: pulse-ring 1.8s ease infinite;
}
@keyframes pulse-ring {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.45; transform:scale(1.3); }
}
.ml-title {
  font-family: var(--ml-font-head);
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -.035em;
  color: var(--text-color);
  margin: 0 0 5px;
  line-height: 1.1;
}
.ml-sub {
  color: var(--muted-color);
  font-family: var(--ml-font-body);
  font-size: 14px;
  margin: 0;
}
.ml-actions {
  display: flex;
  gap: 9px;
  align-items: center;
  flex-shrink: 0;
  padding-top: 4px;
}

/* ── Stats Row ── */
.ml-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 18px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-1);
  box-shadow: var(--shadow-1);
  flex: 1;
  min-width: 130px;
  max-width: 200px;
  transition: var(--ml-transition);
}
.stat-card:hover { box-shadow: var(--shadow-2); border-color: var(--line-medium); }
.stat-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.stat-icon.total     { background: var(--t-primary, rgba(158,54,58,.1));  color: var(--primary-color); }
.stat-icon.active    { background: var(--t-success, rgba(22,163,74,.1));  color: var(--success-color); }
.stat-icon.pending   { background: var(--t-warn,    rgba(245,158,11,.1)); color: var(--warning-color); }
.stat-icon.new-today { background: rgba(99,102,241,.1); color: #6366f1; }
.stat-num  { font-family: var(--ml-font-head); font-size: 1.3rem; font-weight: 800; color: var(--text-color); line-height: 1; }
.stat-lbl  { font-size: 11px; font-weight: 600; color: var(--muted-color); margin-top: 2px; letter-spacing: .03em; }

/* ── Toolbar ── */
.ml-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.ml-search {
  position: relative;
  flex: 1;
  min-width: 220px;
  max-width: 380px;
}
.ml-search i {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted-color);
  font-size: 13px;
  pointer-events: none;
}
.ml-search input {
  width: 100%;
  padding: 9px 12px 9px 38px;
  border: 1px solid var(--line-medium);
  border-radius: var(--radius-1);
  font-family: var(--ml-font-body);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-color);
  outline: none;
  transition: var(--ml-transition);
}
.ml-search input:focus { border-color: var(--accent-color); box-shadow: var(--ring); }
.ml-search input::placeholder { color: var(--muted-color); }
.ml-select {
  max-width: 180px;
  font-size: 13px;
  border-radius: var(--radius-1) !important;
  border-color: var(--line-medium) !important;
  font-family: var(--ml-font-body);
}
.ml-select:focus { border-color: var(--accent-color) !important; box-shadow: var(--ring) !important; outline: none; }
.ml-count-badge {
  margin-left: auto;
  font-family: var(--ml-font-body);
  font-size: 13px;
  color: var(--muted-color);
  white-space: nowrap;
}
.ml-count-badge b { color: var(--text-color); font-weight: 700; }

/* ── Buttons ── */
.btn-brand {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px;
  background: var(--primary-color); color: #fff;
  border: none; border-radius: var(--radius-1);
  font-family: var(--ml-font-body); font-size: 14px; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: var(--ml-transition);
  box-shadow: 0 2px 10px rgba(158,54,58,.3);
}
.btn-brand:hover { background: var(--secondary-color); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(107,37,40,.38); }
.btn-brand:active { transform: translateY(0); }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 14px;
  background: var(--surface); color: var(--muted-color);
  border: 1px solid var(--line-medium); border-radius: var(--radius-1);
  font-family: var(--ml-font-body); font-size: 14px; font-weight: 500;
  cursor: pointer; text-decoration: none;
  transition: var(--ml-transition);
  box-shadow: var(--shadow-1);
}
.btn-ghost:hover { background: var(--surface-2); border-color: var(--line-strong); color: var(--text-color); }
.btn-ghost.spinning i { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Cards ── */
.ml-card {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: var(--ml-radius);
  box-shadow: var(--shadow-1);
  overflow: visible;
  height: 100%;
  display: flex;
  flex-direction: column;
  transition: var(--ml-transition);
  position: relative;
  z-index: 0;
  animation: cardIn .28s ease both;
}
@keyframes cardIn {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}
.ml-card:hover { box-shadow: var(--shadow-2); border-color: var(--line-medium); transform: translateY(-2px); }
.ml-card:hover, .ml-card:focus-within { z-index: 50; }
.ml-card.dropdown-open { z-index: 80; transform: none; }

/* Card top */
.card-top {
  padding: 18px 18px 14px;
  flex: 1;
  border-radius: var(--ml-radius) var(--ml-radius) 0 0;
}
.card-row-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 11px;
  margin-bottom: 12px;
}

/* Avatar */
.ml-avatar-wrap { position: relative; flex-shrink: 0; }
.ml-avatar {
  width: 46px; height: 46px;
  border-radius: 12px;
  background: var(--primary-light);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--ml-font-head);
  font-size: .95rem; font-weight: 800;
  color: var(--primary-color);
  border: 1px solid rgba(158,54,58,.15);
  letter-spacing: -.02em;
}
.ml-status-dot {
  position: absolute; bottom: -2px; right: -2px;
  width: 11px; height: 11px;
  border-radius: 50%;
  background: var(--success-color);
  border: 2px solid var(--surface);
}

/* Lead info */
.ml-lead-info { flex: 1; min-width: 0; }
.ml-lead-name {
  font-family: var(--ml-font-head);
  font-weight: 700;
  font-size: 14.5px;
  color: var(--text-color);
  margin: 0 0 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ml-lead-contact {
  font-family: var(--ml-font-body);
  font-size: 12.5px;
  color: var(--muted-color);
  display: flex; align-items: center; gap: 5px;
}
.ml-lead-contact i { font-size: 11px; opacity: .65; flex-shrink: 0; }

/* Phone chip */
.ml-phone-chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; color: var(--muted-color);
  margin-top: 5px;
}
.ml-phone-chip i { font-size: 10px; opacity: .6; }

/* Menu button */
.ml-menu-btn {
  width: 30px; height: 30px;
  border-radius: 8px;
  border: 1px solid var(--line-soft);
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  color: var(--muted-color);
  transition: var(--ml-transition);
  flex-shrink: 0;
}
.ml-menu-btn:hover { background: var(--surface-2); border-color: var(--line-medium); color: var(--text-color); }

/* Dropdown */
.ml-dropdown.dropdown-menu {
  min-width: 205px !important;
  border-radius: 14px !important;
  border: 1px solid var(--line-soft) !important;
  box-shadow: var(--shadow-2) !important;
  padding: 6px !important;
  z-index: 1055 !important;
}
.ml-dropdown .dropdown-item {
  border-radius: 9px;
  padding: 9px 12px;
  font-family: var(--ml-font-body);
  font-size: 13px; font-weight: 500;
  display: flex; align-items: center; gap: 9px;
  color: var(--text-color);
  transition: background .12s;
}
.ml-dropdown .dropdown-item i { width: 14px; text-align: center; opacity: .65; }
.ml-dropdown .dropdown-item:hover { background: var(--surface-2); }
.ml-dropdown .item-danger  { color: var(--danger-color) !important; }
.ml-dropdown .item-danger:hover  { background: var(--danger-light) !important; }
.ml-dropdown .item-success { color: var(--success-color) !important; }
.ml-dropdown .item-success:hover { background: var(--t-success) !important; }
.ml-dropdown .dd-divider   { height: 1px; background: var(--line-soft); margin: 5px 0; }

/* Tags */
.card-meta { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 11px; }
.meta-tag  {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px;
  border-radius: var(--radius-1);
  font-family: var(--ml-font-body);
  font-size: 11.5px; font-weight: 600;
  border: 1px solid;
}
.meta-tag.class-tag { background: var(--t-primary); color: var(--accent-color); border-color: rgba(201,75,80,.22); }
.meta-tag.year-tag  { background: var(--t-success); color: var(--success-color); border-color: rgba(22,163,74,.22); }
.meta-tag.dept-tag  { background: rgba(99,102,241,.08); color: #4f46e5; border-color: rgba(99,102,241,.22); }

/* Card divider */
.card-divider { height: 1px; background: var(--line-soft); }

/* Card bottom */
.card-bottom {
  padding: 11px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  background: var(--surface-2);
  border-radius: 0 0 var(--ml-radius) var(--ml-radius);
}
.assign-info {
  font-family: var(--ml-font-body);
  font-size: 12.5px;
  color: var(--muted-color);
  display: flex; align-items: center; gap: 5px;
}
.assign-info i { font-size: 11px; opacity: .5; }
.assign-info b { color: var(--text-color); font-weight: 600; }
.since-label {
  font-family: var(--ml-font-body);
  font-size: 11.5px;
  color: var(--muted-color);
  display: flex; align-items: center; gap: 4px;
  opacity: .7;
}

/* ── State Views ── */
.ml-state {
  background: var(--surface);
  border: 1.5px dashed var(--line-medium);
  border-radius: var(--ml-radius);
  padding: 52px 24px;
  text-align: center;
  color: var(--muted-color);
}
.ml-state .state-icon {
  width: 56px; height: 56px;
  background: var(--surface-3);
  border-radius: var(--radius-1);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px;
  font-size: 1.3rem;
  color: var(--muted-color);
}
.ml-state .state-title {
  font-family: var(--ml-font-head);
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 5px;
  font-size: 16px;
}
.ml-state p { margin: 0; font-size: 14px; font-family: var(--ml-font-body); }

/* ── Skeleton Loader ── */
.skeleton-grid { display: contents; }
.skeleton-card {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: var(--ml-radius);
  padding: 18px;
  animation: shimmer 1.4s ease infinite;
}
@keyframes shimmer {
  0%,100% { opacity: 1; }
  50%      { opacity: .5; }
}
.skel-line {
  border-radius: 999px;
  background: var(--line-medium);
}

/* ── Animation stagger ── */
.ml-card-col:nth-child(1) .ml-card { animation-delay: .02s; }
.ml-card-col:nth-child(2) .ml-card { animation-delay: .05s; }
.ml-card-col:nth-child(3) .ml-card { animation-delay: .08s; }
.ml-card-col:nth-child(4) .ml-card { animation-delay: .11s; }
.ml-card-col:nth-child(5) .ml-card { animation-delay: .14s; }
.ml-card-col:nth-child(6) .ml-card { animation-delay: .17s; }

@media(max-width: 640px) {
  .ml-header { flex-direction: column; }
  .ml-title  { font-size: 1.5rem; }
  .stat-card { min-width: calc(50% - 5px); max-width: none; }
  .ml-count-badge { display: none; }
}
.uq-attempt-input {
  width: 64px; padding: 4px 8px; font-size: 13px; font-weight: 600;
  border: 1px solid var(--line-medium); border-radius: 8px; text-align: center;
  outline: none; transition: border-color .15s;
}
.uq-attempt-input:focus { border-color: var(--primary-color); }
</style>
@endpush

@section('content')
<div class="container-fluid ml-wrap">

  {{-- Header --}}
  <div class="ml-header">
    <div>
      <div class="ml-eyebrow">
        <span class="dot-pulse"></span>
        My Students
      </div>
      <h1 class="ml-title">My Leads</h1>
      <p class="ml-sub">Students assigned to your account</p>
    </div>
    <div class="ml-actions">
      <button class="btn-ghost" id="btnRefresh">
        <i class="fa fa-arrows-rotate"></i> Refresh
      </button>
      <a href="/fresh-leads/manage" class="btn-brand">
        <i class="fa fa-bolt"></i> Fresh Queue
      </a>
    </div>
  </div>

  {{-- Stats --}}
  <div class="ml-stats">
    <div class="stat-card">
      <div class="stat-icon total"><i class="fa fa-users"></i></div>
      <div>
        <div class="stat-num" id="statTotal">—</div>
        <div class="stat-lbl">Total</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon active"><i class="fa fa-circle-check"></i></div>
      <div>
        <div class="stat-num" id="statWithAcad">—</div>
        <div class="stat-lbl">With Academics</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon pending"><i class="fa fa-clock"></i></div>
      <div>
        <div class="stat-num" id="statPending">—</div>
        <div class="stat-lbl">Pending Info</div>
      </div>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="ml-toolbar">
    <div class="ml-search">
      <i class="fa fa-magnifying-glass"></i>
      <input type="text" id="searchInput" placeholder="Search by name, email or phone…" autocomplete="off">
    </div>
    <select class="form-select ml-select" id="filterAcad">
      <option value="">All Students</option>
      <option value="with_acad">Has Academic Info</option>
      <option value="no_acad">Missing Academic Info</option>
    </select>
    <div class="ml-count-badge" id="countBadge"></div>
  </div>

  {{-- Grid --}}
  <div id="mlGrid" class="row g-3"></div>
  <div id="mlEmpty" class="ml-state d-none mt-2">
    <div class="state-icon"><i class="fa fa-user-slash"></i></div>
    <p class="state-title">No students found</p>
    <p class="mt-1">Try adjusting your search or filters.</p>
  </div>

</div>
{{-- ═══ Assign Exams Modal ═══ --}}
<div class="modal fade" id="assignQuizModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;border:1px solid var(--line-soft);">
      <div class="modal-header" style="border-bottom:1px solid var(--line-soft);padding:16px 20px;">
        <h5 class="modal-title" style="font-weight:800;font-size:1rem;">
          <i class="fa fa-clipboard-list me-2" style="color:var(--primary-color)"></i>
          Assign Exams — <span id="uq_student_name" style="color:var(--primary-color)">Student</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px;">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <div class="position-relative" style="flex:1;min-width:200px;max-width:300px;">
            <i class="fa fa-search position-absolute" style="left:11px;top:50%;transform:translateY(-50%);opacity:.5;font-size:12px;"></i>
            <input id="uq_search" class="form-control ps-5" style="border-radius:10px;font-size:13px;" placeholder="Search quizzes…">
          </div>
          <select id="uq_filter" class="form-select" style="width:170px;border-radius:10px;font-size:13px;">
            <option value="all">All Quizzes</option>
            <option value="assigned">Assigned Only</option>
            <option value="unassigned">Unassigned Only</option>
          </select>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:13px;">
            <thead style="background:var(--surface-2);font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-color);">
              <tr>
                <th style="font-weight:700;padding:10px 12px;">Quiz</th>
                <th style="width:90px;font-weight:700;">Time (min)</th>
                <th style="width:90px;font-weight:700;">Questions</th>
                <th style="width:100px;font-weight:700;">Status</th>
                <th style="width:80px;font-weight:700;">Public</th>
                <th style="width:150px;font-weight:700;">Assignment Code</th>
                <th style="width:90px;font-weight:700;">Attempts</th>
                <th style="width:100px;font-weight:700;text-align:center;">Assigned</th>
              </tr>
            </thead>
            <tbody id="uq_rows">
              <tr id="uq_loader">
                <td colspan="8" class="text-center text-muted p-4">
                  <i class="fa fa-circle-notch fa-spin me-1"></i> Loading quizzes…
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--line-soft);padding:12px 20px;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-size:13px;font-weight:600;">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function waitForLibs() {
  if (typeof bootstrap === 'undefined' || typeof Swal === 'undefined') {
    return setTimeout(waitForLibs, 50);
  }
  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();

async function boot() {

  /* ── CONFIG ── */
  const LIST_MINE_URL  = '/api/my-assignments?role=academic_counsellor';
  const AUTH_CHECK_URL = '/api/auth/check';
  const ACAD_PAGE_URL  = (uuid) => `/student-academic-details?student_id=${encodeURIComponent(uuid)}`;
  const PROFILE_URL    = (uuid) => `/student-profile-details?uuid=${encodeURIComponent(uuid)}`;
  const REMOVE_URL     = (myId, uuid) => `/api/counsellors/${myId}/students/${uuid}/unassign`;
  const USER_QUIZZES_URL  = (id) => `/api/users/${id}/quizzes`;
const QUIZ_ASSIGN_URL   = (id) => `/api/users/${id}/quizzes/assign`;
const QUIZ_UNASSIGN_URL = (id) => `/api/users/${id}/quizzes/unassign`;

  /* ── AUTH ── */
  const TOKEN = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
  if (!TOKEN) {
    await Swal.fire({ icon: 'warning', title: 'Session Expired', text: 'Please log in again.' });
    location.href = '/';
    return;
  }

  function hdrs(extra) {
    return Object.assign(
      { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json' },
      extra || {}
    );
  }

  /* ── Get My ID ── */
  let MY_ID = '';
  async function getMyId() {
    const stored = sessionStorage.getItem('user_id') || localStorage.getItem('user_id') || '';
    if (stored) return stored;
    try {
      const res = await fetch(AUTH_CHECK_URL, { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      const id  = j?.user?.id ?? j?.data?.id ?? null;
      if (id) { sessionStorage.setItem('user_id', String(id)); return String(id); }
    } catch (e) { console.warn('getMyId failed:', e); }
    return '';
  }

  /* ── DOM ── */
  const mlGrid    = document.getElementById('mlGrid');
  const mlEmpty   = document.getElementById('mlEmpty');
  const searchEl  = document.getElementById('searchInput');
  const filterEl  = document.getElementById('filterAcad');
  const countEl   = document.getElementById('countBadge');

  /* ── State ── */
  let allLeads = [];
  let uqData   = [];
let uqUserId = null;

  /* ── Utils ── */
  function esc(s) {
    const m = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' };
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => m[c]);
  }
  function pick(o, keys, fb = '') {
    for (const k of keys) {
      if (o && o[k] !== undefined && o[k] !== null && String(o[k]).trim() !== '') return o[k];
    }
    return fb;
  }
  function initials(n) {
    return (n || '?').trim().split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();
  }
  function hasAcad(u) {
    return !!(u.klass || u.passout || u.dept || u.roll_no || u.cgpa);
  }

  /* ── Normalise ── */
  function extractMyStudents(j) {
    const arr          = j?.data?.my_students ?? j?.my_students ?? [];
    const counsellorName = j?.data?.counsellor?.name ?? 'Me';
    const counsellorId   = String(j?.data?.counsellor?.id ?? MY_ID ?? '');
    return Array.isArray(arr) ? arr.map(s => ({
      id:           String(s.student_uuid ?? s.uuid ?? s.student_id ?? s.id ?? ''),
      raw_id:       String(s.student_id ?? s.id ?? ''),
      name:         String(s.student_name ?? s.name ?? ''),
      email:        String(s.student_email ?? s.email ?? ''),
      phone:        String(s.student_phone ?? s.phone ?? ''),
      klass:        String(s.class ?? s.class_name ?? ''),
      passout:      String(s.passout_year ?? s.passout ?? ''),
      dept:         String(s.department ?? s.dept ?? ''),
      roll_no:      String(s.roll_no ?? ''),
      cgpa:         String(s.cgpa ?? ''),
      counsellorId: counsellorId,
      counsellorName: counsellorName,
      assignedSince: s.assigned_at ?? s.created_at ?? '',
    })) : [];
  }

  /* ── Stats ── */
  function updateStats() {
    document.getElementById('statTotal').textContent    = allLeads.length;
    document.getElementById('statWithAcad').textContent = allLeads.filter(hasAcad).length;
    document.getElementById('statPending').textContent  = allLeads.filter(u => !hasAcad(u)).length;
  }

  /* ── Card HTML ── */
  function cardHtml(u, idx) {
    const ini  = initials(u.name);
    const acad = hasAcad(u);

    const tags = [
      u.klass   ? `<span class="meta-tag class-tag"><i class="fa fa-book-open" style="font-size:.55rem"></i>${esc(u.klass)}</span>`            : '',
      u.passout ? `<span class="meta-tag year-tag"><i class="fa fa-calendar" style="font-size:.55rem"></i>${esc(u.passout)}</span>`             : '',
      u.dept    ? `<span class="meta-tag dept-tag"><i class="fa fa-building-columns" style="font-size:.55rem"></i>${esc(u.dept)}</span>`        : '',
    ].filter(Boolean).join('');

    const sinceLabel = u.assignedSince
      ? `<span class="since-label"><i class="fa fa-clock"></i>${esc(relTime(u.assignedSince))}</span>`
      : '';

    return `
      <div class="col-12 col-md-6 col-xl-4 ml-card-col">
        <div class="ml-card" data-id="${esc(u.id)}" style="animation-delay:${Math.min(idx * .04, .4)}s">
          <div class="card-top">
            <div class="card-row-top">
              <div class="ml-avatar-wrap">
                <div class="ml-avatar">${esc(ini)}</div>
                <span class="ml-status-dot"></span>
              </div>
              <div class="ml-lead-info">
                <p class="ml-lead-name">${esc(u.name || '—')}</p>
                <span class="ml-lead-contact"><i class="fa-regular fa-envelope"></i>${esc(u.email || 'No email')}</span>
                ${u.phone ? `<div class="ml-phone-chip"><i class="fa fa-phone"></i>${esc(u.phone)}</div>` : ''}
              </div>
              <div class="dropdown">
                <button class="ml-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fa fa-ellipsis"></i>
                </button>
                <ul class="dropdown-menu ml-dropdown dropdown-menu-end">
                  <li><button class="dropdown-item js-view" type="button"><i class="fa-regular fa-eye"></i>View Profile</button></li>
                  <li><button class="dropdown-item item-success js-acad" type="button"><i class="fa fa-graduation-cap"></i>Academic Details</button></li>
                  <li><div class="dd-divider"></div></li>
<li><button class="dropdown-item js-assign-quiz" type="button" data-raw-id="${esc(u.raw_id)}" data-name="${esc(u.name)}"><i class="fa fa-clipboard-list"></i>Assign Exams</button></li>
                </ul>
              </div>
            </div>
            ${tags ? `<div class="card-meta">${tags}</div>` : `
              <div class="card-meta">
                <span class="meta-tag" style="background:var(--surface-3);color:var(--muted-color);border-color:var(--line-medium);font-size:11px;">
                  <i class="fa fa-circle-info" style="font-size:.55rem"></i>No academic info yet
                </span>
              </div>`}
          </div>
          <div class="card-divider"></div>
          <div class="card-bottom">
            <div class="assign-info">
              <i class="fa fa-user-tie"></i>
              Assigned to <b>Me</b>
            </div>
            ${sinceLabel}
          </div>
        </div>
      </div>`;
  }

  /* ── Relative time ── */
  function relTime(dateStr) {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 2)   return 'just now';
    if (mins < 60)  return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24)   return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30)  return `${days}d ago`;
    return new Date(dateStr).toLocaleDateString('en-IN', { day:'numeric', month:'short' });
  }

  /* ── Filter ── */
  function getFiltered() {
    const q = searchEl.value.trim().toLowerCase();
    const f = filterEl.value;
    return allLeads.filter(u => {
      if (q && !u.name.toLowerCase().includes(q)
             && !u.email.toLowerCase().includes(q)
             && !u.phone.toLowerCase().includes(q)) return false;
      if (f === 'with_acad' && !hasAcad(u)) return false;
      if (f === 'no_acad'   &&  hasAcad(u)) return false;
      return true;
    });
  }

  function render() {
    const list = getFiltered();
    mlGrid.innerHTML = '';
    mlEmpty.classList.toggle('d-none', list.length > 0);
    if (!list.length) return;
    mlGrid.innerHTML = list.map((u, i) => cardHtml(u, i)).join('');
    countEl.innerHTML = `Showing <b>${list.length}</b> of <b>${allLeads.length}</b>`;
  }

  searchEl.addEventListener('input', render);
  filterEl.addEventListener('change', render);

  /* ── Skeleton ── */
  function showSkeleton() {
    mlGrid.innerHTML = Array.from({ length: 6 }).map(() => `
      <div class="col-12 col-md-6 col-xl-4">
        <div class="skeleton-card">
          <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:16px">
            <div class="skel-line" style="width:46px;height:46px;border-radius:12px;flex-shrink:0"></div>
            <div style="flex:1">
              <div class="skel-line" style="height:14px;width:65%;margin-bottom:8px"></div>
              <div class="skel-line" style="height:12px;width:85%"></div>
            </div>
          </div>
          <div style="display:flex;gap:6px">
            <div class="skel-line" style="height:22px;width:72px;border-radius:999px"></div>
            <div class="skel-line" style="height:22px;width:58px;border-radius:999px"></div>
          </div>
        </div>
      </div>`).join('');
  }

  function showError(msg) {
    mlGrid.innerHTML = `
      <div class="col-12">
        <div class="ml-state" style="border-color:rgba(220,38,38,.3)">
          <div class="state-icon" style="background:var(--danger-light);color:var(--danger-color)">
            <i class="fa fa-triangle-exclamation"></i>
          </div>
          <p class="state-title" style="color:var(--danger-color)">Failed to Load</p>
          <p class="mt-1">${esc(msg || 'Something went wrong')}</p>
        </div>
      </div>`;
  }

  /* ── Load ── */
  async function loadLeads() {
    const btn = document.getElementById('btnRefresh');
    if (btn) btn.classList.add('spinning');
    showSkeleton();
    mlEmpty.classList.add('d-none');
    try {
      const res = await fetch(LIST_MINE_URL, { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load');
      allLeads = extractMyStudents(j);
      updateStats();
      render();
    } catch (e) {
      showError(e.message);
    } finally {
      if (btn) btn.classList.remove('spinning');
    }
  }
  /* ══ QUIZ MODAL ══ */
const assignQuizModal = new bootstrap.Modal(document.getElementById('assignQuizModal'));
const uqSearch = document.getElementById('uq_search');
const uqFilter = document.getElementById('uq_filter');
const uqRows   = document.getElementById('uq_rows');
const uqLoader = document.getElementById('uq_loader');

async function openUserQuizzes(userId, userName) {
  uqUserId = parseInt(userId, 10);
  document.getElementById('uq_student_name').textContent = userName || ('User #' + userId);
  uqSearch.value = '';
  uqFilter.value = 'all';
  uqRows.innerHTML = '';
  uqLoader.style.display = '';
  assignQuizModal.show();

  try {
    const res = await fetch(USER_QUIZZES_URL(userId), { headers: hdrs() });
    const j   = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j.message || 'Failed to load quizzes');
    uqData = Array.isArray(j.data) ? j.data : [];
    renderQuizRows();
  } catch(e) {
    uqRows.innerHTML = `<tr><td colspan="8" class="text-danger text-center p-3">${esc(e.message)}</td></tr>`;
  } finally {
    uqLoader.style.display = 'none';
  }
}

function renderQuizRows() {
  uqRows.querySelectorAll('tr:not(#uq_loader)').forEach(tr => tr.remove());

  let list = uqData.slice();
  const q  = uqSearch.value.trim().toLowerCase();
  const f  = uqFilter.value;

  if (q)                list = list.filter(x => (x.quiz_name||'').toLowerCase().includes(q));
  if (f==='assigned')   list = list.filter(x => !!x.assigned);
  if (f==='unassigned') list = list.filter(x => !x.assigned);

  if (!list.length) {
    uqRows.innerHTML = `<tr><td colspan="8" class="text-center text-muted p-3">No quizzes found.</td></tr>`;
    return;
  }

  const frag = document.createDocumentFragment();
  list.forEach(qz => {
    const assigned = !!qz.assigned;
    const status   = (qz.status || '').toLowerCase();
    const isPublic = (qz.is_public || '').toLowerCase();
    const code     = qz.assignment_code || '';
    const attempts = qz.max_attempts ?? qz.attempt_no ?? 1;

    const statusBadge = status === 'active'
      ? `<span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase" style="font-size:10px;">${esc(status)}</span>`
      : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-uppercase" style="font-size:10px;">${esc(status||'—')}</span>`;

    const publicBadge = (isPublic==='yes'||isPublic==='public')
      ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">Yes</span>`
      : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px;">No</span>`;

    const codeHtml = code
      ? `<button type="button"
            class="btn btn-light btn-sm js-copy-code d-inline-flex align-items-center gap-1"
            style="border-radius:8px;font-size:11px;font-weight:700;"
            data-code="${esc(code)}" title="Copy code">
            ${esc(code)} <i class="fa-regular fa-copy"></i>
         </button>`
      : '<span class="text-muted">—</span>';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="font-weight:600;">${esc(qz.quiz_name || '')}</td>
      <td>${qz.total_time != null ? esc(String(qz.total_time)) : '—'}</td>
      <td>${qz.total_questions != null ? esc(String(qz.total_questions)) : '—'}</td>
      <td>${statusBadge}</td>
      <td>${publicBadge}</td>
      <td>${codeHtml}</td>
      <td>
        <input type="number" class="uq-attempt-input js-attempt"
          data-qid="${qz.quiz_id}"
          value="${esc(String(attempts))}"
          min="1" max="99"
          ${!assigned ? 'disabled title="Assign quiz first"' : ''}>
      </td>
      <td class="text-center">
        <div class="form-check form-switch d-inline-block m-0">
          <input class="form-check-input uq-toggle" type="checkbox"
            data-qid="${qz.quiz_id}"
            ${assigned ? 'checked' : ''}>
        </div>
      </td>`;
    frag.appendChild(tr);
  });
  uqRows.appendChild(frag);

  // Toggle
  uqRows.querySelectorAll('.uq-toggle').forEach(ch => {
    ch.addEventListener('change', async () => {
      await toggleQuiz(parseInt(ch.dataset.qid, 10), !!ch.checked, ch);
    });
  });

  // Copy code
  uqRows.querySelectorAll('.js-copy-code').forEach(btn => {
    btn.addEventListener('click', () => {
      navigator.clipboard.writeText(btn.dataset.code || '').then(() => {
        btn.innerHTML = '<i class="fa fa-check"></i> Copied';
        setTimeout(() => {
          btn.innerHTML = `${esc(btn.dataset.code)} <i class="fa-regular fa-copy"></i>`;
        }, 1500);
      });
    });
  });

  // Attempts
  uqRows.querySelectorAll('.js-attempt').forEach(inp => {
    inp.addEventListener('change', async () => {
      const quizId   = parseInt(inp.dataset.qid, 10);
      const attempts = Math.max(1, parseInt(inp.value, 10) || 1);
      inp.value = attempts;
      await updateAttempts(quizId, attempts, inp);
    });
  });
}

async function toggleQuiz(quizId, assigned, checkboxEl) {
  if (!uqUserId || !quizId) return;
  try {
    const url = assigned ? QUIZ_ASSIGN_URL(uqUserId) : QUIZ_UNASSIGN_URL(uqUserId);
    const res = await fetch(url, {
      method: 'POST',
      headers: hdrs({ 'Content-Type': 'application/json' }),
      body: JSON.stringify({ quiz_id: quizId }),
    });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j.message || 'Operation failed');

    const item = uqData.find(x => Number(x.quiz_id) === Number(quizId));
    if (item) {
      item.assigned        = assigned;
      item.assignment_code = assigned ? (j.data?.assignment_code || item.assignment_code || '') : null;
      item.status          = assigned ? 'active' : 'revoked';
    }
    renderQuizRows();
  } catch(e) {
    if (checkboxEl) checkboxEl.checked = !assigned;
    Swal.fire({ icon:'error', title:'Failed', text: e.message || 'Could not update.', toast:true, position:'top-end', timer:2500, showConfirmButton:false });
  }
}

async function updateAttempts(quizId, attempts, inputEl) {
  if (!uqUserId || !quizId) return;
  try {
    // NEW
const res = await fetch(`/api/quizz/${quizId}`, {
  method: 'PUT',
  headers: hdrs({ 'Content-Type': 'application/json' }),
  body: JSON.stringify({ attempt_no: attempts }),
});
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j.message || 'Failed to update attempts');

    const item = uqData.find(x => Number(x.quiz_id) === Number(quizId));
    if (item) item.attempt_no = attempts;

    Swal.fire({ icon:'success', title:'Updated', text:`Attempts set to ${attempts}`, toast:true, position:'top-end', timer:1500, showConfirmButton:false });
  } catch(e) {
    Swal.fire({ icon:'error', title:'Failed', text: e.message, toast:true, position:'top-end', timer:2500, showConfirmButton:false });
  }
}

uqSearch.addEventListener('input',  () => renderQuizRows());
uqFilter.addEventListener('change', () => renderQuizRows());

  /* ── Init ── */
  MY_ID = await getMyId();
  await loadLeads();

  /* ── Refresh ── */
  document.getElementById('btnRefresh').addEventListener('click', loadLeads);

  /* ── Dropdown z-index ── */
  document.addEventListener('show.bs.dropdown', ev => {
    ev.target.closest('.ml-card')?.classList.add('dropdown-open');
  });
  document.addEventListener('hide.bs.dropdown', ev => {
    ev.target.closest('.ml-card')?.classList.remove('dropdown-open');
  });

  /* ── Delegate Clicks ── */
  document.addEventListener('click', async function (e) {
    const card = e.target.closest('.ml-card');
    if (!card) return;
    const id = card.getAttribute('data-id');
    if (!id)  return;
    const u  = allLeads.find(x => x.id === id);

    if (e.target.closest('.js-view')) {
      window.location.href = PROFILE_URL(id);
      return;
    }

    if (e.target.closest('.js-acad')) {
      window.location.href = ACAD_PAGE_URL(id);
      return;
    }
    if (e.target.closest('.js-assign-quiz')) {
  const btn = e.target.closest('.js-assign-quiz');
  // raw_id is the integer user ID the API expects
  const rawId = btn.dataset.rawId || u?.raw_id || id;
  const name  = btn.dataset.name  || u?.name   || '';
  openUserQuizzes(rawId, name);
  return;
}

    if (e.target.closest('.js-remove')) {
      if (!MY_ID) {
        Swal.fire({ icon: 'warning', title: 'Missing ID', text: 'Could not detect your user ID.' });
        return;
      }
      const resp = await Swal.fire({
        icon: 'warning',
        title: 'Remove from your list?',
        text: `${u?.name || 'This student'} will be unassigned from your account.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove',
        confirmButtonColor: 'var(--danger-color, #dc2626)',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
      });
      if (!resp.isConfirmed) return;
      try {
        const res = await fetch(REMOVE_URL(MY_ID, id), {
          method: 'DELETE',
          headers: hdrs({ 'Content-Type': 'application/json' }),
        });
        const j = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(j.message || 'Remove failed');
        allLeads = allLeads.filter(x => x.id !== id);
        updateStats();
        render();
        Swal.fire({ icon: 'success', title: 'Removed', text: 'Student removed from your list.', timer: 1400, showConfirmButton: false });
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Could not remove.' });
      }
    }
  });

} // end boot()
</script>
@endpush