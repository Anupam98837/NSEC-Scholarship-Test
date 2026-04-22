<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  .aaq-wrap{max-width:1180px;margin:16px auto 40px}
  .aaq-panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:16px}
  .aaq-toolbar{display:flex;gap:12px;align-items:end;justify-content:space-between;flex-wrap:wrap}
  .aaq-search{min-width:min(320px,100%)}
  .aaq-search .form-control,
  .aaq-toolbar .form-control{height:42px;border-radius:12px;border:1px solid var(--line-strong)}
  .aaq-meta{font-size:13px;color:var(--muted-color)}
  .aaq-table-wrap{border:1px solid var(--line-strong);border-radius:16px;overflow:hidden;background:var(--surface)}
  .aaq-table{margin:0}
  .aaq-table thead th{font-size:12px;color:var(--muted-color);background:var(--surface);border-bottom:1px solid var(--line-strong);white-space:nowrap}
  .aaq-table tbody tr:hover{background:var(--page-hover)}
  .aaq-name{font-weight:600;color:var(--ink)}
  .aaq-sub{font-size:12px;color:var(--muted-color)}
  .aaq-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .aaq-badge.public{background:rgba(22,163,74,.12);color:#15803d}
  .aaq-badge.private{background:rgba(100,116,139,.12);color:#475569}
  .aaq-empty{padding:42px 16px;text-align:center;color:var(--muted-color)}
  .aaq-check{width:18px;height:18px}
  @media (max-width: 768px){
    .aaq-toolbar{align-items:stretch}
    .aaq-search{min-width:100%}
  }
</style>

<div class="aaq-wrap" id="aaqApp">
  <div class="aaq-panel mb-3">
    <div class="aaq-toolbar">
      <div>
        <div class="fw-semibold">Student Register Auto Assign</div>
        <div class="aaq-meta">Choose which active quizzes should be assigned automatically when a student registers.</div>
      </div>

      <div class="aaq-search">
        <label class="form-label small mb-1">Search Quiz</label>
        <div class="position-relative">
          <input type="search" id="aaqSearch" class="form-control ps-5" placeholder="Search by quiz name...">
          <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.55;"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="aaq-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div class="aaq-meta" id="aaqSummary">Loading quizzes...</div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light btn-sm" id="aaqSelectVisible">
          <i class="fa-solid fa-check-double me-1"></i>Select Visible
        </button>
        <button class="btn btn-light btn-sm" id="aaqClearVisible">
          <i class="fa-solid fa-eraser me-1"></i>Clear Visible
        </button>
        <button class="btn btn-primary btn-sm" id="aaqSaveBtn">
          <i class="fa-solid fa-floppy-disk me-1"></i>Save Auto Assign
        </button>
      </div>
    </div>

    <div class="aaq-table-wrap">
      <div class="table-responsive">
        <table class="table aaq-table align-middle">
          <thead>
            <tr>
              <th style="width:54px;">
                <input type="checkbox" id="aaqToggleAll" class="aaq-check" aria-label="Toggle all visible quizzes">
              </th>
              <th>Quiz</th>
              <th style="width:110px;">Attempts</th>
              <th style="width:110px;">Questions</th>
              <th style="width:110px;">Time</th>
              <th style="width:110px;">Visibility</th>
              <th style="width:180px;">Created</th>
            </tr>
          </thead>
          <tbody id="aaqRows">
            <tr><td colspan="7" class="aaq-empty">Loading quizzes...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const API_URL = '/api/quizz/auto-assign/student-register';

  const searchInput = document.getElementById('aaqSearch');
  const summaryEl = document.getElementById('aaqSummary');
  const rowsEl = document.getElementById('aaqRows');
  const saveBtn = document.getElementById('aaqSaveBtn');
  const toggleAllEl = document.getElementById('aaqToggleAll');
  const selectVisibleBtn = document.getElementById('aaqSelectVisible');
  const clearVisibleBtn = document.getElementById('aaqClearVisible');

  let quizzes = [];
  let filtered = [];
  let selected = new Set();

  function authHeaders(extra = {}) {
    const token = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
    return Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : '',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    }, extra);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatDate(value) {
    if (!value) return '—';
    const dt = new Date(value);
    return Number.isNaN(dt.getTime()) ? '—' : dt.toLocaleString();
  }

  function filteredQuizzes() {
    const q = (searchInput.value || '').trim().toLowerCase();
    filtered = quizzes.filter(quiz => !q || (quiz.quiz_name || '').toLowerCase().includes(q));
    return filtered;
  }

  function render() {
    const items = filteredQuizzes();
    const selectedCount = selected.size;

    summaryEl.textContent = `${items.length} active quiz(es) shown • ${selectedCount} selected for student register`;

    if (!items.length) {
      rowsEl.innerHTML = '<tr><td colspan="7" class="aaq-empty">No quizzes found for this search.</td></tr>';
      toggleAllEl.checked = false;
      toggleAllEl.indeterminate = false;
      return;
    }

    rowsEl.innerHTML = items.map(quiz => {
      const checked = selected.has(quiz.id) ? 'checked' : '';
      const visibilityClass = quiz.is_public === 'yes' ? 'public' : 'private';
      const visibilityText = quiz.is_public === 'yes' ? 'Public' : 'Private';

      return `
        <tr data-id="${quiz.id}">
          <td>
            <input type="checkbox" class="aaq-check js-quiz-check" data-id="${quiz.id}" ${checked}>
          </td>
          <td>
            <div class="aaq-name">${escapeHtml(quiz.quiz_name)}</div>
            <div class="aaq-sub">ID: ${quiz.id}</div>
          </td>
          <td>${quiz.total_attempts ?? '—'}</td>
          <td>${quiz.total_questions ?? '—'}</td>
          <td>${quiz.total_time ? `${quiz.total_time} min` : '—'}</td>
          <td><span class="aaq-badge ${visibilityClass}">${visibilityText}</span></td>
          <td>${escapeHtml(formatDate(quiz.created_at))}</td>
        </tr>
      `;
    }).join('');

    const visibleIds = items.map(item => item.id);
    const selectedVisibleCount = visibleIds.filter(id => selected.has(id)).length;
    toggleAllEl.checked = visibleIds.length > 0 && selectedVisibleCount === visibleIds.length;
    toggleAllEl.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < visibleIds.length;
  }

  async function load() {
    rowsEl.innerHTML = '<tr><td colspan="7" class="aaq-empty">Loading quizzes...</td></tr>';
    summaryEl.textContent = 'Loading quizzes...';

    try {
      const res = await fetch(API_URL, { headers: authHeaders() });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Failed to load auto assign quizzes.');

      quizzes = Array.isArray(json.quizzes) ? json.quizzes : [];
      selected = new Set(Array.isArray(json.selected_ids) ? json.selected_ids.map(Number) : []);
      render();
    } catch (error) {
      rowsEl.innerHTML = `<tr><td colspan="7" class="aaq-empty">${escapeHtml(error.message || 'Failed to load quizzes.')}</td></tr>`;
      summaryEl.textContent = 'Unable to load auto assign quizzes.';
    }
  }

  async function save() {
    const selectedIds = Array.from(selected).sort((a, b) => a - b);

    saveBtn.disabled = true;
    const oldHtml = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...';

    try {
      const res = await fetch(API_URL, {
        method: 'PUT',
        headers: authHeaders(),
        body: JSON.stringify({ quiz_ids: selectedIds }),
      });

      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Failed to save auto assign quizzes.');

      selected = new Set(Array.isArray(json.selected_ids) ? json.selected_ids.map(Number) : selectedIds);
      render();
      summaryEl.textContent = `${filtered.length} active quiz(es) shown • ${selected.size} selected for student register • Saved`;
    } catch (error) {
      alert(error.message || 'Failed to save auto assign quizzes.');
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = oldHtml;
    }
  }

  rowsEl.addEventListener('change', (event) => {
    const checkbox = event.target.closest('.js-quiz-check');
    if (!checkbox) return;

    const id = Number(checkbox.dataset.id);
    if (!id) return;

    if (checkbox.checked) selected.add(id);
    else selected.delete(id);

    render();
  });

  toggleAllEl.addEventListener('change', () => {
    const items = filteredQuizzes();
    items.forEach(item => {
      if (toggleAllEl.checked) selected.add(item.id);
      else selected.delete(item.id);
    });
    render();
  });

  selectVisibleBtn.addEventListener('click', () => {
    filteredQuizzes().forEach(item => selected.add(item.id));
    render();
  });

  clearVisibleBtn.addEventListener('click', () => {
    filteredQuizzes().forEach(item => selected.delete(item.id));
    render();
  });

  searchInput.addEventListener('input', render);
  saveBtn.addEventListener('click', save);

  load();
})();
</script>
