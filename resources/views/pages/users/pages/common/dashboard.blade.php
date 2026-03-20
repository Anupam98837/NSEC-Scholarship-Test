{{-- resources/views/pages/users/dashboard.blade.php --}}
@extends('pages.users.layout.structure')

@section('title', 'Dashboard')

@section('content')
  <div id="dash-shell">
    {{-- Dashboards (all rendered; only 1 will be shown) --}}
    <div id="dashAdmin" style="display:none">
      @include('modules.common.adminDashboard')
    </div>

    <div id="dashExaminer" style="display:none">
      @include('modules.common.examinerDashboard')
    </div>

    <div id="dashStudent" style="display:none">
      @include('modules.common.studentDashboard')
    </div>

    <div id="dashAuthor" style="display:none">
      @include('modules.common.authorDashboard')
    </div>

    <div id="dashCollegeAdmin" style="display:none">
      @include('modules.common.collegeAdminDashboard')
    </div>

    <div id="dashAcademicCounsellor" style="display:none">
      @include('modules.common.academicCounsellorDashboard')
    </div>
  </div>
@endsection

@push('scripts')
{{-- Chart.js needed for all dashboards --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(async function initDashboard() {
  const shell = document.getElementById('dash-shell');
  if (!shell) {
    console.warn('[DASH] dash-shell not found. Exiting.');
    return;
  }

  // Wait for DOM if needed
  if (document.readyState === 'loading') {
    await new Promise(resolve => document.addEventListener('DOMContentLoaded', resolve, { once: true }));
  }

  const dashAdmin              = document.getElementById('dashAdmin');
  const dashExaminer           = document.getElementById('dashExaminer');
  const dashStudent            = document.getElementById('dashStudent');
  const dashAuthor             = document.getElementById('dashAuthor');
  const dashCollegeAdmin       = document.getElementById('dashCollegeAdmin');
  const dashAcademicCounsellor = document.getElementById('dashAcademicCounsellor');

  // Check if panels exist
  if (!dashAdmin || !dashExaminer || !dashStudent ||
      !dashAuthor || !dashCollegeAdmin || !dashAcademicCounsellor) {
    console.error('[DASH] Missing dashboard panels');
    return;
  }

  // Helper: show only one panel
  const showPanel = (panel) => {
    [
      dashAdmin,
      dashExaminer,
      dashStudent,
      dashAuthor,
      dashCollegeAdmin,
      dashAcademicCounsellor,
    ].forEach(p => p.style.display = 'none');
    panel.style.display = 'block';
  };

  // Helper: publish role globally
  const publishRole = (role) => {
    document.body.setAttribute('data-role', role);
    window.__DASH_ACTIVE_ROLE__ = role;
    try {
      window.dispatchEvent(new CustomEvent('dash:role', { detail: { role } }));
    } catch (e) {}
  };

  // Normalize role
  const normalizeRole = (role) => {
    const r = String(role || '').trim().toLowerCase().replace(/[\s-]+/g, '_');

    // super_admin behaves like admin
    if (r === 'super_admin' || r === 'superadmin') return 'admin';

    // college_administrator variants
    if (r === 'college_administrator' || r === 'collegeadministrator' ||
        r === 'college_admin'         || r === 'cadm') {
      return 'college_administrator';
    }

    // academic_counsellor variants (both spellings)
    if (r === 'academic_counsellor'  || r === 'academic_counselor' ||
        r === 'academiccounsellor'   || r === 'academiccounselor'  ||
        r === 'acc') {
      return 'academic_counsellor';
    }

    // author variants
    if (r === 'author' || r === 'writer' || r === 'aut') return 'author';

    return r;
  };

  // Get role from API
  const getMyRole = async (token) => {
    if (!token) return '';
    try {
      const res = await fetch('/api/auth/me-role', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json'
        }
      });

      if (!res.ok) return '';

      const data = await res.json();
      if (data?.status === 'success' && data?.role) {
        return normalizeRole(data.role);
      }
      return '';
    } catch (e) {
      console.error('[DASH] Error fetching role:', e);
      return '';
    }
  };

  // Get token
  const token = sessionStorage.getItem('token') || localStorage.getItem('token');
  if (!token) {
    window.location.replace('/');
    return;
  }

  // Get role
  const role = await getMyRole(token);
  if (!role) {
    sessionStorage.removeItem('token');
    localStorage.removeItem('token');
    window.location.replace('/');
    return;
  }

  // Publish role
  publishRole(role);

  // Small safe wait (ensures DOM + Blade pushed scripts are ready)
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

  // Show appropriate dashboard and initialize
  if (role === 'admin') {
    showPanel(dashAdmin);
    console.log('[DASH] Showing admin dashboard');
    if (typeof window.initializeAdminDashboard === 'function') {
      window.initializeAdminDashboard();
    } else {
      console.error('[DASH] initializeAdminDashboard not found');
    }

  } else if (role === 'examiner') {
    showPanel(dashExaminer);
    console.log('[DASH] Showing examiner dashboard');
    if (typeof window.initializeExaminerDashboard === 'function') {
      window.initializeExaminerDashboard();
    } else {
      console.error('[DASH] initializeExaminerDashboard not found');
    }

  } else if (role === 'student') {
    showPanel(dashStudent);
    console.log('[DASH] Showing student dashboard');
    if (typeof window.initializeStudentDashboard === 'function') {
      window.initializeStudentDashboard();
    } else {
      console.error('[DASH] initializeStudentDashboard not found');
    }

  } else if (role === 'author') {
    showPanel(dashAuthor);
    console.log('[DASH] Showing author dashboard');
    if (typeof window.initializeAuthorDashboard === 'function') {
      window.initializeAuthorDashboard();
    } else {
      console.error('[DASH] initializeAuthorDashboard not found');
    }

  } else if (role === 'college_administrator') {
    showPanel(dashCollegeAdmin);
    console.log('[DASH] Showing college administrator dashboard');
    if (typeof window.initializeCollegeAdminDashboard === 'function') {
      window.initializeCollegeAdminDashboard();
    } else {
      console.error('[DASH] initializeCollegeAdminDashboard not found');
    }

  } else if (role === 'academic_counsellor') {
    showPanel(dashAcademicCounsellor);
    console.log('[DASH] Showing academic counsellor dashboard');
    if (typeof window.initializeAcademicCounsellorDashboard === 'function') {
      window.initializeAcademicCounsellorDashboard();
    } else {
      console.error('[DASH] initializeAcademicCounsellorDashboard not found');
    }

  } else {
    console.warn('[DASH] Unknown role, redirecting:', role);
    window.location.replace('/');
    return;
  }

  console.log('[DASH] Dashboard initialized for role:', role);
})();
</script>
@endpush