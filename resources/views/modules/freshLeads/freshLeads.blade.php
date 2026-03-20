{{-- resources/views/modules/leads/fresh-leads.blade.php --}}
@section('title', 'Fresh Leads')

@push('styles')
<style>
  /* ── Variables ── */
  :root {
    --brand:        #c94b50;
    --brand-dark:   #9e3639;
    --brand-light:  #fdf1f1;
    --success:      #16a34a;
    --success-light:#f0fdf4;
    --warn:         #d97706;
    --warn-light:   #fffbeb;
    --info:         #4f46e5;
    --info-light:   #eef2ff;
    --text:         #111827;
    --muted:        #6b7280;
    --border:       #e5e7eb;
    --surface:      #ffffff;
    --surface-2:    #f9fafb;
    --radius:       12px;
    --shadow:       0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    --shadow-md:    0 4px 16px rgba(0,0,0,.1);
    --transition:   all .15s ease;
  }

  /* ── Layout ── */
  .fl-wrap { max-width: 900px; margin: 0 auto; padding: 32px 20px 64px; }

  /* ── Header ── */
  .fl-header { margin-bottom: 28px; }
  .fl-eyebrow {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--brand);
    background: var(--brand-light); border: 1px solid rgba(201,75,80,.2);
    padding: 4px 12px; border-radius: 999px; margin-bottom: 10px;
  }
  .fl-title { font-size: 1.75rem; font-weight: 800; color: var(--text); margin: 0 0 4px; letter-spacing: -.03em; }
  .fl-sub   { font-size: 14px; color: var(--muted); margin: 0; }

  /* ── Stats ── */
  .fl-stats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
  .stat-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 14px; background: var(--surface);
    border: 1px solid var(--border); border-radius: 999px;
    font-size: 13px; color: var(--muted); box-shadow: var(--shadow);
  }
  .stat-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .stat-pill strong { color: var(--text); font-weight: 700; }

  /* ── Focus Card ── */
  .focus-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: 24px;
  }
  .focus-head {
    padding: 20px 20px 16px;
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 14px;
    border-bottom: 1px solid var(--border);
  }
  .focus-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
  .focus-avatar {
    width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
    background: var(--brand-light); border: 1px solid rgba(201,75,80,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 800; color: var(--brand);
  }
  .focus-name  { font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0 0 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .focus-meta  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; color: var(--muted); }
  .focus-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }

  /* ── Badges ── */
  .badge-unassigned {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--warn-light); color: var(--warn); border: 1px solid rgba(217,119,6,.2);
  }
  .badge-assigned {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--success-light); color: var(--success); border: 1px solid rgba(22,163,74,.2);
  }

  /* ── Buttons ── */
  .btn-assign {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
    border: 1px solid rgba(22,163,74,.25); background: var(--success-light); color: var(--success);
    transition: all .15s;
  }
  .btn-assign:hover:not(:disabled) { background: #dcfce7; transform: translateY(-1px); }
  .btn-assign:disabled { opacity: .45; cursor: not-allowed; }

  .btn-quiz {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
    border: 1px solid rgba(79,70,229,.25); background: var(--info-light); color: var(--info);
    transition: all .15s;
  }
  .btn-quiz:hover { background: #e0e7ff; transform: translateY(-1px); }

  /* ══ Custom Tabs (matching student-profile style) ══ */
  .sp-tab-nav {
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
    padding: 0 16px;
    display: flex;
    gap: 2px;
  }
  .sp-tab-btn {
    border: 0;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 700;
    color: var(--muted);
    background: transparent;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .sp-tab-btn:hover { color: var(--text); }
  .sp-tab-btn.active { color: var(--text); border-bottom-color: var(--brand); }
  .sp-tab-btn i { font-size: 12px; }

  .sp-tab-body { padding: 20px; }
  .sp-tab-pane { display: none; }
  .sp-tab-pane.active { display: block; }

  /* ── Section Headers ── */
  .section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
  }
  .section-title {
    font-size: 12px; font-weight: 800; color: var(--muted);
    text-transform: uppercase; letter-spacing: .08em;
    display: flex; align-items: center; gap: 7px;
  }
  .section-title i { color: var(--brand); font-size: 11px; }

  /* ── Divider ── */
  .sp-divider { height: 1px; background: var(--border); margin: 20px 0; }

  /* ── Info Grid ── */
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .info-field { display: flex; flex-direction: column; gap: 3px; }
  .info-field label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--muted);
  }
  .info-field .field-val {
    font-size: 14px; font-weight: 600; color: var(--text);
    padding: 7px 10px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    min-height: 36px;
    word-break: break-word;
  }
  .info-field .field-val.empty { color: var(--muted); font-weight: 400; font-style: italic; }

  /* ── Lock / Blur ── */
  .locked .lock-blur    { filter: blur(6px); opacity: .5; pointer-events: none; user-select: none; }
  .lock-note {
    display: none; margin-top: 14px;
    background: var(--warn-light); border: 1px solid rgba(217,119,6,.2);
    border-radius: 10px; padding: 12px 14px;
    font-size: 13px; color: #92400e;
  }
  .locked .lock-note { display: block; }

  /* ── Empty / Loading State ── */
  .fl-state {
    background: var(--surface); border: 1.5px dashed var(--border);
    border-radius: 14px; padding: 48px 24px; text-align: center; color: var(--muted);
  }
  .fl-state .state-icon {
    width: 52px; height: 52px; background: var(--surface-2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin: 0 auto 12px;
  }
  .fl-state .state-title { font-size: 15px; font-weight: 800; color: var(--text); margin: 0 0 4px; }

  /* ── Empty/placeholder exam panel ── */
  .exam-placeholder {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px 16px;
    font-size: 13px; color: var(--muted); text-align: center;
  }

  /* ── Quiz Modal Table ── */
  .uq-attempt-input {
    width: 64px; padding: 4px 8px; font-size: 13px; font-weight: 600;
    border: 1px solid var(--border); border-radius: 8px; text-align: center;
    outline: none; transition: border-color .15s;
  }
  .uq-attempt-input:focus { border-color: var(--brand); }

  @media (max-width: 640px) {
    .focus-head { flex-direction: column; }
    .focus-actions { width: 100%; }
    .focus-actions > * { flex: 1; justify-content: center; }
    .info-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 420px) {
    .info-grid { grid-template-columns: 1fr; }
  }

  /* ── Exam Accordion ── */
  .exam-accordion-item {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 10px;
    background: var(--surface);
    box-shadow: var(--shadow);
    transition: box-shadow .2s;
  }
  .exam-accordion-item:hover { box-shadow: var(--shadow-md); }

  .exam-accordion-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; cursor: pointer;
    user-select: none;
    border-bottom: 1px solid transparent;
    transition: background .15s, border-color .15s;
  }
  .exam-accordion-header:hover { background: var(--surface-2); }
  .exam-accordion-item.open .exam-accordion-header {
    background: var(--surface-2);
    border-bottom-color: var(--border);
  }

  .exam-acc-icon {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: var(--brand-light); border: 1px solid rgba(201,75,80,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; color: var(--brand);
  }
  .exam-acc-name { font-size: .9rem; font-weight: 800; color: var(--text); margin: 0 0 3px; }
  .exam-acc-meta { font-size: 12px; color: var(--muted); display: flex; gap: 10px; flex-wrap: wrap; }
  .exam-acc-badges { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
  .exam-acc-chevron {
    width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;
    color: var(--muted); transition: transform .25s; font-size: 12px; flex-shrink: 0;
  }
  .exam-accordion-item.open .exam-acc-chevron { transform: rotate(180deg); color: var(--brand); }

  .exam-accordion-body { display: none; padding: 16px; }
  .exam-accordion-item.open .exam-accordion-body { display: block; }

  /* ── Attempt Pills ── */
  .attempt-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .attempt-tab-btn {
    padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1.5px solid var(--border); background: var(--surface); color: var(--muted);
    transition: all .15s;
  }
  .attempt-tab-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }
  .attempt-tab-btn:hover:not(.active) { border-color: var(--brand); color: var(--brand); }

  /* ── Group Table ── */
  .group-result-wrap { overflow-x: auto; }
  .group-result-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .group-result-table th {
    padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em; color: var(--muted);
    background: var(--surface-2); border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  .group-result-table td {
    padding: 10px 12px; border-bottom: 1px solid var(--border);
    color: var(--text); font-weight: 500;
  }
  .group-result-table tr:last-child td { border-bottom: none; }
  .group-result-table tr.total-row td {
    background: var(--surface-2); font-weight: 800; border-top: 2px solid var(--border);
  }
  .group-result-table tr:hover:not(.total-row) td { background: var(--surface-2); }

  /* ── Mini progress bar ── */
  .pct-bar-wrap { display: flex; align-items: center; gap: 8px; }
  .pct-bar-bg { flex: 1; height: 6px; background: var(--border); border-radius: 99px; overflow: hidden; }
  .pct-bar-fill { height: 100%; border-radius: 99px; transition: width .4s cubic-bezier(.4,0,.2,1); }
  .pct-val { font-size: 12px; font-weight: 700; min-width: 38px; text-align: right; }

  /* ── Score badge ── */
  .score-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800;
  }
  .score-chip.pass { background: var(--success-light); color: var(--success); border: 1px solid rgba(22,163,74,.2); }
  .score-chip.avg  { background: var(--warn-light);    color: var(--warn);    border: 1px solid rgba(217,119,6,.2); }
  .score-chip.fail { background: #fef2f2;              color: #dc2626;        border: 1px solid rgba(220,38,38,.2); }

  /* ── Activity Feed ── */
  .activity-feed { display: flex; flex-direction: column; gap: 0; }
  .activity-item {
    display: flex; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid var(--border);
    position: relative;
  }
  .activity-item:last-child { border-bottom: none; }

  .activity-icon-wrap {
    flex-shrink: 0; width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem;
  }
  .activity-icon-wrap.store   { background: var(--success-light); color: var(--success); }
  .activity-icon-wrap.update  { background: var(--info-light);    color: var(--info);    }
  .activity-icon-wrap.delete  { background: #fef2f2;              color: #dc2626;        }
  .activity-icon-wrap.default { background: var(--surface-3, #f3f4f6); color: var(--muted); }
  .activity-icon-wrap.toggled { background: var(--warn-light);    color: var(--warn);    }

  .activity-body { flex: 1; min-width: 0; }
  .activity-title {
    font-size: 13px; font-weight: 700; color: var(--text);
    margin: 0 0 3px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  }
  .activity-module {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    padding: 2px 7px; border-radius: 999px;
    background: var(--surface-2); color: var(--muted); border: 1px solid var(--border);
  }
  .activity-note  { font-size: 12px; color: var(--muted); margin: 2px 0 0; }
  .activity-meta  {
    font-size: 11px; color: var(--muted); margin-top: 4px;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  }
  .activity-meta i { opacity: .55; }

  /* pagination */
  .act-pagination {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
    padding-top: 16px; margin-top: 4px;
    border-top: 1px solid var(--border);
    font-size: 13px; color: var(--muted);
  }
  .act-pagination button {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1px solid var(--border); background: var(--surface); color: var(--muted);
    transition: var(--transition);
  }
  .act-pagination button:hover:not(:disabled) { background: var(--surface-2); color: var(--text); }
  .act-pagination button:disabled { opacity: .4; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="container-fluid fl-wrap">

  {{-- Header --}}
  <div class="fl-header">
    <div class="fl-eyebrow"><i class="fa fa-bolt"></i> Live Queue</div>
    <h1 class="fl-title">Fresh Leads</h1>
    <p class="fl-sub">Unassigned students — first come, first served</p>
  </div>

  {{-- Stats --}}
  <div class="fl-stats">
    <div class="stat-pill"><span class="dot" style="background:var(--brand)"></span><strong id="statTotal">0</strong> Total</div>
    <div class="stat-pill"><span class="dot" style="background:var(--warn)"></span><strong id="statUnassigned">0</strong> Unassigned</div>
    <div class="stat-pill"><span class="dot" style="background:var(--success)"></span><strong id="statAssigned">0</strong> Assigned</div>
  </div>

  {{-- Focus Card (hidden until student loaded) --}}
  <div id="focusWrap" class="d-none">
    <div id="focusCard" class="focus-card locked">

      {{-- Head --}}
      <div class="focus-head">
        <div class="focus-left">
          <div class="focus-avatar" id="focusAvatar">—</div>
          <div style="min-width:0">
            <p class="focus-name" id="focusName">—</p>
            <div class="focus-meta">
              <span id="focusStatusBadge" class="badge-unassigned"><i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned</span>
              <span><i class="fa-regular fa-envelope" style="opacity:.6"></i> <span id="focusEmail">—</span></span>
              <span class="demo d-none"><i class="fa fa-phone" style="opacity:.6"></i> <span id="focusPhone">—</span></span>
            </div>
          </div>
        </div>
        <div class="focus-actions">
          <button class="btn-assign" id="btnAssignToMe" type="button">
            <i class="fa fa-user-check"></i> Assign to Me
          </button>
          <button class="btn-quiz d-none" id="btnAssignQuiz" type="button">
            <i class="fa fa-clipboard-list"></i> Assign Exams
          </button>
        </div>
      </div>

      {{-- ══ Custom Tab Nav ══ --}}
      <div class="sp-tab-nav">
        <button class="sp-tab-btn active" data-tab="tabProfile"><i class="fa fa-address-card"></i> Profile</button>
        <button class="sp-tab-btn" data-tab="tabExams"><i class="fa fa-file-alt"></i> Exams</button>
        <button class="sp-tab-btn" data-tab="tabActivity"><i class="fa fa-chart-line"></i> Activity</button>
        <button class="sp-tab-btn" data-tab="tabComms"><i class="fa fa-comments"></i> Communications</button>
      </div>

      {{-- Tab Body --}}
      <div class="sp-tab-body">

        {{-- Profile Tab --}}
        <div class="sp-tab-pane active" id="tabProfile">
          <div class="lock-blur">
            <div class="section-head">
              <div class="section-title"><i class="fa fa-user"></i> Personal &amp; Contact Details</div>
            </div>
            <div class="info-grid">
              <div class="info-field"><label>Full Name</label><div class="field-val" id="spName">—</div></div>
              <div class="info-field"><label>Primary Email</label><div class="field-val" id="spEmail">—</div></div>
              <div class="info-field"><label>Mobile Number</label><div class="field-val" id="spMobile">—</div></div>
              <div class="info-field"><label>WhatsApp</label><div class="field-val" id="spWhatsapp">—</div></div>
              <div class="info-field"><label>Alternative Email</label><div class="field-val" id="spAltEmail">—</div></div>
            </div>
            <div class="sp-divider"></div>
            <div class="section-head">
              <div class="section-title"><i class="fa fa-shield-halved"></i> Guardian Information</div>
            </div>
            <div class="info-grid">
              <div class="info-field"><label>Guardian Name</label><div class="field-val" id="spGuardian">—</div></div>
              <div class="info-field"><label>Guardian Phone</label><div class="field-val" id="spGuardianNum">—</div></div>
            </div>
            <div class="sp-divider"></div>
            <div class="section-head">
              <div class="section-title"><i class="fa fa-school"></i> School / Exam Details</div>
            </div>
            <div class="info-grid">
              <div class="info-field"><label>Enrolled Class</label><div class="field-val" id="spClass">—</div></div>
              <div class="info-field"><label>Education Board</label><div class="field-val" id="spBoard">—</div></div>
              <div class="info-field"><label>Exam Type</label><div class="field-val" id="spExamType">—</div></div>
              <div class="info-field"><label>Year of Passout</label><div class="field-val" id="spPassout">—</div></div>
            </div>
          </div>
          <div class="lock-note">
            <i class="fa fa-lock me-2"></i> <strong>Locked.</strong> Assign this student to yourself to view full profile details.
          </div>
        </div>

        {{-- Exams Tab --}}
        <div class="sp-tab-pane" id="tabExams">
          <div class="lock-blur">
            <div id="examsContent">
              <div class="exam-placeholder" id="examsPlaceholder">
                <i class="fa fa-file-circle-question mb-2" style="font-size:1.4rem;opacity:.4"></i>
                <div>No exam results yet. Assign exams using the button above.</div>
              </div>
              <div id="examAccordion" class="d-none"></div>
            </div>
          </div>
          <div class="lock-note">
            <i class="fa fa-lock me-2"></i> <strong>Locked.</strong> Assign to unlock exam history.
          </div>
        </div>

        {{-- Activity Tab --}}
        <div class="sp-tab-pane" id="tabActivity">
          <div class="lock-blur">
            <div class="section-head">
              <div class="section-title"><i class="fa fa-chart-line"></i> Activity Log</div>
              <div style="display:flex;gap:8px;align-items:center;">
                <select id="actModuleFilter" style="font-size:12px;padding:5px 10px;border-radius:8px;border:1px solid var(--border);color:var(--text);background:var(--surface);">
                  <option value="">All Modules</option>
                </select>
                <select id="actTypeFilter" style="font-size:12px;padding:5px 10px;border-radius:8px;border:1px solid var(--border);color:var(--text);background:var(--surface);">
                  <option value="">All Types</option>
                  <option value="store">Created</option>
                  <option value="update">Updated</option>
                  <option value="delete">Deleted</option>
                  <option value="default">Default</option>
                </select>
              </div>
            </div>
            <div id="activityFeedWrap">
              <div class="exam-placeholder" id="actPlaceholder">
                <i class="fa fa-chart-line mb-2" style="font-size:1.4rem;opacity:.4"></i>
                <div>Activity will appear here once loaded.</div>
              </div>
              <div class="activity-feed d-none" id="activityFeed"></div>
              <div class="act-pagination d-none" id="actPagination">
                <span id="actPaginationInfo"></span>
                <div style="display:flex;gap:6px;">
                  <button id="actPrevBtn"><i class="fa fa-chevron-left"></i> Prev</button>
                  <button id="actNextBtn">Next <i class="fa fa-chevron-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <div class="lock-note">
            <i class="fa fa-lock me-2"></i> <strong>Locked.</strong> Assign to unlock activity log.
          </div>
        </div>

        {{-- Communications Tab --}}
        <div class="sp-tab-pane" id="tabComms">
          <div class="lock-blur">
            <div class="exam-placeholder">
              <i class="fa fa-comments mb-2" style="font-size:1.4rem;opacity:.4"></i>
              <div>Call logs, WhatsApp and email history will appear here.</div>
            </div>
          </div>
          <div class="lock-note">
            <i class="fa fa-lock me-2"></i> <strong>Locked.</strong> Assign to unlock communication history.
          </div>
        </div>

      </div>{{-- /sp-tab-body --}}
    </div>
  </div>

  {{-- Loading / Empty state --}}
  <div id="flState" class="fl-state">
    <div class="state-icon"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i></div>
    <p class="state-title">Ready</p>
    <p class="mt-1" style="font-size:13px">Accept the prompt to load the next student in queue.</p>
  </div>

</div>

{{-- ═══════════════════════════════════════════
     Assign Exams Modal
═══════════════════════════════════════════ --}}
<div class="modal fade" id="assignQuizModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;border:1px solid var(--border);">
      <div class="modal-header" style="border-bottom:1px solid var(--border);padding:16px 20px;">
        <h5 class="modal-title" style="font-weight:800;font-size:1rem;">
          <i class="fa fa-clipboard-list me-2" style="color:var(--brand)"></i>
          Assign Exams — <span id="uq_student_name" style="color:var(--brand)">Student</span>
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
            <thead style="background:var(--surface-2);font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">
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
      <div class="modal-footer" style="border-top:1px solid var(--border);padding:12px 20px;">
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
  if (typeof bootstrap === 'undefined' || typeof Swal === 'undefined') return setTimeout(waitForLibs, 50);
  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();

async function boot() {

  /* ══ CONFIG ══════════════════════════════════════════════ */
  const FRESH_LEADS_URL   = '/api/students/fresh-leads';
  const ASSIGN_URL        = (myId, uuid) => `/api/counsellors/${myId}/students/${uuid}/assign`;
  const AUTH_CHECK_URL    = '/api/auth/check';
  const USER_QUIZZES_URL  = (id) => `/api/users/${id}/quizzes`;
  const QUIZ_ASSIGN_URL   = (id) => `/api/users/${id}/quizzes/assign`;
  const QUIZ_UNASSIGN_URL = (id) => `/api/users/${id}/quizzes/unassign`;
  const ACTIVITY_LOGS_URL = (studentId, page, module, activity) => {
    const p = new URLSearchParams({ student_id: studentId, limit: ACT_LIMIT, page, sort: 'desc' });
    if (module)   p.set('module', module);
    if (activity) p.set('activity', activity);
    return `/api/activity-logs?${p}`;
  };

  /* ══ AUTH ════════════════════════════════════════════════ */
  const TOKEN = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
  if (!TOKEN) {
    await Swal.fire({ icon: 'warning', title: 'Session Expired', text: 'Please log in again.' });
    location.href = '/'; return;
  }
  function hdrs(extra) {
    return Object.assign({ 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json' }, extra || {});
  }
  function normalizeRole(v) {
    let r = String(v || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
    const map = { academiccounsellor: 'academic_counsellor', academiccounselor: 'academic_counsellor' };
    return map[r.replace(/_/g, '')] || r;
  }
  const ROLE       = normalizeRole(sessionStorage.getItem('role') || localStorage.getItem('role') || '');
  const CAN_ASSIGN = (ROLE === 'academic_counsellor');

  /* ══ DOM ═════════════════════════════════════════════════ */
  const focusWrap       = document.getElementById('focusWrap');
  const focusCard       = document.getElementById('focusCard');
  const flState         = document.getElementById('flState');
  const btnAssignToMe   = document.getElementById('btnAssignToMe');
  const btnAssignQuiz   = document.getElementById('btnAssignQuiz');
  const assignQuizModal = new bootstrap.Modal(document.getElementById('assignQuizModal'));

  /* ══ ACTIVITY STATE ══════════════════════════════════════ */
  const ACT_LIMIT      = 15;
  let   actPage        = 1;
  let   actTotal       = 0;
  let   actModsLoaded  = false; // guard: populate module dropdown only once per student

  const actFeed        = document.getElementById('activityFeed');
  const actPlaceholder = document.getElementById('actPlaceholder');
  const actPagination  = document.getElementById('actPagination');
  const actPrevBtn     = document.getElementById('actPrevBtn');
  const actNextBtn     = document.getElementById('actNextBtn');
  const actPagInfo     = document.getElementById('actPaginationInfo');
  const actModFilter   = document.getElementById('actModuleFilter');
  const actTypeFilter  = document.getElementById('actTypeFilter');

  /* ══ STATE ═══════════════════════════════════════════════ */
  let MY_ID    = '';
  let focused  = null;
  let uqData   = [];
  let uqUserId = null;

  /* ══ UTILS ═══════════════════════════════════════════════ */
  function esc(s) {
    const m = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' };
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => m[c]);
  }
  function initials(n) {
    return (n||'?').trim().split(' ').slice(0,2).map(p=>p[0]).join('').toUpperCase();
  }
  function val(v) {
    return (v !== null && v !== undefined && String(v).trim() !== '') ? String(v) : '—';
  }

  async function getMyId() {
    const stored = sessionStorage.getItem('user_id') || localStorage.getItem('user_id') || '';
    if (stored) return stored;
    try {
      const res = await fetch(AUTH_CHECK_URL, { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      const id  = j?.user?.id ?? j?.data?.id ?? null;
      if (id) { sessionStorage.setItem('user_id', String(id)); return String(id); }
    } catch(e) {}
    return '';
  }

  /* ══ STATS ═══════════════════════════════════════════════ */
  function updateStats(data) {
    document.getElementById('statTotal').textContent      = data.total      ?? 0;
    document.getElementById('statUnassigned').textContent = data.unassigned ?? 0;
    document.getElementById('statAssigned').textContent   = data.assigned   ?? 0;
  }

  /* ══ LOCK TOGGLE ═════════════════════════════════════════ */
  function setLocked(isLocked) {
    focusCard.classList.toggle('locked', !!isLocked);
    btnAssignQuiz.classList.toggle('d-none', !!isLocked);

    // Reset exam tab
    if (examAccordion) {
      examAccordion.innerHTML = '';
      examAccordion.classList.add('d-none');
    }
    if (examsPlaceholder) {
      examsPlaceholder.classList.remove('d-none');
      examsPlaceholder.innerHTML = `<i class="fa fa-file-circle-question mb-2" style="font-size:1.4rem;opacity:.4"></i><div>No exam results yet. Assign exams using the button above.</div>`;
    }

    // Reset activity tab state for the new student
    actPage       = 1;
    actTotal      = 0;
    actModsLoaded = false;
    // Reset module dropdown back to "All Modules" only
    while (actModFilter.options.length > 1) actModFilter.remove(1);
    actModFilter.value  = '';
    actTypeFilter.value = '';
    // Reset feed UI
    actFeed.classList.add('d-none');
    actPagination.classList.add('d-none');
    actPlaceholder.classList.remove('d-none');
    actPlaceholder.innerHTML = `<i class="fa fa-chart-line mb-2" style="font-size:1.4rem;opacity:.4"></i><div>Activity will appear here once loaded.</div>`;

    Object.keys(examResultCache).forEach(k => delete examResultCache[k]);
  }

  /* ══ ACTIVITY — icon mapping ═════════════════════════════ */
  function actIcon(type) {
    const map = {
      store:   { icon: 'fa-plus',       cls: 'store'   },
      update:  { icon: 'fa-pen',        cls: 'update'  },
      delete:  { icon: 'fa-trash',      cls: 'delete'  },
      default: { icon: 'fa-circle-dot', cls: 'default' },
    };
    const t = (type || '').toLowerCase();
    if (t.startsWith('toggled')) return { icon: 'fa-toggle-on', cls: 'toggled' };
    return map[t] || map.default;
  }

  /* ══ ACTIVITY — render feed ══════════════════════════════ */
  function renderActivityFeed(rows) {
    if (!rows.length) {
      actFeed.classList.add('d-none');
      actPagination.classList.add('d-none');
      actPlaceholder.classList.remove('d-none');
      actPlaceholder.innerHTML = `<i class="fa fa-inbox" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:6px;"></i> No activity found.`;
      return;
    }

    actPlaceholder.classList.add('d-none');
    actFeed.classList.remove('d-none');
    actPagination.classList.remove('d-none');

    actFeed.innerHTML = rows.map(row => {
      const { icon, cls } = actIcon(row.activity);
      const when = row.created_at || row.occurred_at || row.when || '';
      const timeStr = when
        ? new Date(when).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
        : '—';
      const note   = row.log_note || row.description || row.message || '';
      const target = row.target   || row.record_id   || '';

      return `
        <div class="activity-item">
          <div class="activity-icon-wrap ${cls}">
            <i class="fa ${icon}"></i>
          </div>
          <div class="activity-body">
            <div class="activity-title">
              ${esc(row.activity || 'Action')}
              ${row.module ? `<span class="activity-module">${esc(row.module)}</span>` : ''}
            </div>
            ${note   ? `<div class="activity-note">${esc(note)}</div>` : ''}
            ${target ? `<div class="activity-note" style="color:var(--muted);font-size:11px;">Record: ${esc(String(target))}</div>` : ''}
            <div class="activity-meta">
              <span><i class="fa fa-clock"></i> ${timeStr}</span>
              ${row.ip ? `<span><i class="fa fa-network-wired"></i> ${esc(row.ip)}</span>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');

    const totalPages = Math.ceil(actTotal / ACT_LIMIT) || 1;
    actPagInfo.textContent  = `Page ${actPage} of ${totalPages}  (${actTotal} total)`;
    actPrevBtn.disabled     = actPage <= 1;
    actNextBtn.disabled     = actPage >= totalPages;
  }

  /* ══ ACTIVITY — load page ════════════════════════════════ */
  async function loadActivityTab(resetPage = false) {
    // Resolve the current student's numeric ID
    const studentId = focused?.id ?? '';
    if (!studentId) return;

    if (resetPage) actPage = 1;

    const module   = actModFilter.value   || '';
    const activity = actTypeFilter.value  || '';

    // Show spinner
    actPlaceholder.classList.remove('d-none');
    actPlaceholder.innerHTML = `<i class="fa fa-circle-notch fa-spin" style="color:var(--brand);font-size:1.4rem;display:block;margin-bottom:6px;"></i> Loading activity…`;
    actFeed.classList.add('d-none');
    actPagination.classList.add('d-none');

    try {
      const res = await fetch(ACTIVITY_LOGS_URL(studentId, actPage, module, activity), { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!res.ok || !j.ok) throw new Error(j.error || j.message || 'Failed to load activity');

      actTotal = j.total ?? 0;
      renderActivityFeed(Array.isArray(j.data) ? j.data : []);

      // Populate module dropdown once per student load
      if (!actModsLoaded) {
        actModsLoaded = true;
        populateModuleDropdown(studentId);
      }
    } catch(e) {
      actFeed.classList.add('d-none');
      actPagination.classList.add('d-none');
      actPlaceholder.classList.remove('d-none');
      actPlaceholder.innerHTML = `<i class="fa fa-triangle-exclamation" style="color:#dc2626;font-size:1.4rem;display:block;margin-bottom:6px;"></i><span style="color:#dc2626">${esc(e.message)}</span>`;
    }
  }

  /* ══ ACTIVITY — populate module dropdown ═════════════════ */
  async function populateModuleDropdown(studentId) {
    try {
      const res = await fetch(`/api/activity-logs?student_id=${studentId}&limit=500`, { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!j.ok) return;
      const modules = [...new Set((j.data || []).map(r => r.module).filter(Boolean))].sort();
      modules.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m; opt.textContent = m;
        actModFilter.appendChild(opt);
      });
    } catch(e) { /* silent */ }
  }

  /* ══ ACTIVITY — pagination & filter events ═══════════════ */
  actPrevBtn.addEventListener('click',  () => { actPage--; loadActivityTab(); });
  actNextBtn.addEventListener('click',  () => { actPage++; loadActivityTab(); });
  actModFilter.addEventListener('change',  () => loadActivityTab(true));
  actTypeFilter.addEventListener('change', () => loadActivityTab(true));

  /* ══ SINGLE unified tab-click handler ═══════════════════ */
  document.querySelectorAll('.sp-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      // Switch active tab
      document.querySelectorAll('.sp-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.sp-tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');

      const isLocked = focusCard.classList.contains('locked');

      // Load exams tab content (only when unlocked)
      if (btn.dataset.tab === 'tabExams' && focused && !isLocked) {
        loadExamsTab();
      }

      // Load activity tab content (only when unlocked)
      if (btn.dataset.tab === 'tabActivity' && focused && !isLocked) {
        loadActivityTab(true);
      }
    });
  });

  /* ══ RENDER FOCUS CARD ═══════════════════════════════════ */
  function renderFocus(u) {
    focused = u || null;
    if (!focused) {
      focusWrap.classList.add('d-none');
      flState.classList.remove('d-none');
      flState.innerHTML = `
        <div class="state-icon"><i class="fa fa-users-slash" style="color:var(--muted)"></i></div>
        <p class="state-title">No Student Loaded</p>
        <p class="mt-1" style="font-size:13px">Refresh to load the next lead in queue.</p>`;
      return;
    }

    focusWrap.classList.remove('d-none');
    flState.classList.add('d-none');

    document.getElementById('focusAvatar').textContent = initials(focused.name);
    document.getElementById('focusName').textContent   = focused.name  || '—';
    document.getElementById('focusEmail').textContent  = focused.email || '—';
    document.getElementById('focusPhone').textContent  = focused.phone || '—';

    function setField(id, value) {
      const el = document.getElementById(id);
      if (!el) return;
      const display = val(value);
      el.textContent = display;
      el.classList.toggle('empty', display === '—');
    }

    setField('spName',        focused.name);
    setField('spEmail',       focused.email);
    setField('spMobile',      focused.phone || focused.mobile_number);
    setField('spWhatsapp',    focused.whatsapp || focused.whatsapp_number);
    setField('spAltEmail',    focused.alt_email || focused.alternative_email);
    setField('spGuardian',    focused.guardian_name || focused.parent_name);
    setField('spGuardianNum', focused.guardian_phone || focused.parent_phone);
    setField('spClass',       focused.klass || focused.class_name || focused.enrolled_class);
    setField('spBoard',       focused.education_board || focused.board);
    setField('spExamType',    focused.exam_type);
    setField('spPassout',     focused.passout_year || focused.passout);

    const badge    = document.getElementById('focusStatusBadge');
    const assigned = !!focused.assignedTo;
    badge.className = assigned ? 'badge-assigned' : 'badge-unassigned';
    badge.innerHTML = assigned
      ? `<i class="fa fa-circle" style="font-size:.45rem"></i> Assigned`
      : `<i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned`;

    btnAssignToMe.disabled = assigned || !CAN_ASSIGN;
    btnAssignToMe.title    = !CAN_ASSIGN
      ? 'Only Academic Counsellors can self-assign'
      : (assigned ? 'Already assigned' : '');

    setLocked(!assigned);
  }

  /* ══ LOAD FRESH LEAD ═════════════════════════════════════ */
  async function loadNextLead() {
    flState.classList.remove('d-none');
    flState.innerHTML = `
      <div class="state-icon"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i></div>
      <p class="state-title">Loading Next Lead</p>
      <p class="mt-1" style="font-size:13px">Fetching the next unassigned student…</p>`;
    focusWrap.classList.add('d-none');

    try {
      const res = await fetch(FRESH_LEADS_URL + '?per_page=1', { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load');

      const data = Array.isArray(j.data) ? j.data : [];
      const meta = j.meta || {};

      updateStats({
        total:      meta.total ?? data.length,
        unassigned: meta.total ?? data.length,
        assigned:   0,
      });

      if (!data.length) {
        flState.innerHTML = `
          <div class="state-icon"><i class="fa fa-check-circle" style="color:var(--success)"></i></div>
          <p class="state-title">Queue Empty</p>
          <p class="mt-1" style="font-size:13px">No unassigned students in the queue right now.</p>`;
        return;
      }

      renderFocus(data[0]);

    } catch(e) {
      flState.innerHTML = `
        <div class="state-icon"><i class="fa fa-triangle-exclamation" style="color:#dc2626"></i></div>
        <p class="state-title" style="color:#dc2626">Failed to Load</p>
        <p class="mt-1" style="font-size:13px">${esc(e.message || 'Server error')}</p>`;
    }
  }

  /* ══ ENTRY POINT ═════════════════════════════════════════ */
  MY_ID = await getMyId();

  const ask = await Swal.fire({
    icon: 'question',
    title: 'Load next student?',
    text: 'Load the next unassigned student from the queue?',
    showCancelButton: true,
    confirmButtonText: '<i class="fa fa-bolt me-1"></i> Yes, Load',
    cancelButtonText:  'Not now',
    reverseButtons: true,
    allowOutsideClick: false,
    allowEscapeKey: false,
  });

  if (ask.isConfirmed) {
    await loadNextLead();
  } else {
    flState.innerHTML = `
      <div class="state-icon"><i class="fa fa-hand" style="color:var(--muted)"></i></div>
      <p class="state-title">Ready when you are</p>
      <p class="mt-1" style="font-size:13px">Click <strong>Refresh</strong> to load the next student.</p>`;
  }

  /* ══ ASSIGN TO ME ════════════════════════════════════════ */
  btnAssignToMe.addEventListener('click', async () => {
    if (!focused || !CAN_ASSIGN || !MY_ID) return;

    const resp = await Swal.fire({
      icon: 'question',
      title: 'Assign to yourself?',
      text: `${focused.name || 'This student'} will be assigned to your account.`,
      showCancelButton: true,
      confirmButtonText: 'Assign',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
    });
    if (!resp.isConfirmed) return;

    try {
      const res = await fetch(ASSIGN_URL(MY_ID, focused.uuid || focused.id), {
        method: 'POST',
        headers: hdrs({ 'Content-Type': 'application/json' }),
        body: JSON.stringify({}),
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || 'Assignment failed');

      focused.assignedTo = 'Me';
      renderFocus(focused);

      Swal.fire({ icon:'success', title:'Assigned!', text:'Student assigned successfully.', timer:1400, showConfirmButton:false });

    } catch(e) {
      Swal.fire({ icon:'error', title:'Failed', text: e.message || 'Could not assign.' });
    }
  });

  /* ══ ASSIGN EXAMS BUTTON ═════════════════════════════════ */
  btnAssignQuiz.addEventListener('click', () => {
    if (!focused) return;
    openUserQuizzes(focused.id, focused.name);
  });

  /* ══ QUIZ MODAL ══════════════════════════════════════════ */
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

    if (q)              list = list.filter(x => (x.quiz_name||'').toLowerCase().includes(q));
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
        <td>${qz.total_time      != null ? esc(String(qz.total_time))      : '—'}</td>
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

    uqRows.querySelectorAll('.uq-toggle').forEach(ch => {
      ch.addEventListener('change', async () => {
        await toggleQuiz(parseInt(ch.dataset.qid, 10), !!ch.checked, ch);
      });
    });

    uqRows.querySelectorAll('.js-copy-code').forEach(btn => {
      btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.code || '').then(() => {
          btn.innerHTML = '<i class="fa fa-check"></i> Copied';
          setTimeout(() => { btn.innerHTML = `${esc(btn.dataset.code)} <i class="fa-regular fa-copy"></i>`; }, 1500);
        });
      });
    });

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
      const res = await fetch(`/api/quizzes/${quizId}`, {
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

  /* ══ EXAMS TAB — group-wise result accordion ══════════════ */
  const GROUP_WISE_URL            = (examUuid, studentUuid) =>
    `/api/exam/group-wise-result?exam_key=${examUuid}&student_key=${studentUuid}`;
  const USER_ASSIGNED_QUIZZES_URL = (userId) => `/api/users/${userId}/quizzes`;

  const examAccordion    = document.getElementById('examAccordion');
  const examsPlaceholder = document.getElementById('examsPlaceholder');
  const examResultCache  = {};

  async function loadExamsTab() {
    if (!focused) return;
    const userId      = focused.id;
    const studentUuid = focused.uuid;

    examAccordion.innerHTML = '';
    examAccordion.classList.add('d-none');
    examsPlaceholder.classList.remove('d-none');
    examsPlaceholder.innerHTML = `<i class="fa fa-circle-notch fa-spin mb-2" style="font-size:1.4rem;color:var(--brand)"></i><div>Loading exams…</div>`;

    try {
      const res     = await fetch(USER_ASSIGNED_QUIZZES_URL(userId), { headers: hdrs() });
      const j       = await res.json().catch(() => ({}));
      const quizzes = (Array.isArray(j.data) ? j.data : []).filter(q => !!q.assigned);

      if (!quizzes.length) {
        examsPlaceholder.innerHTML = `<i class="fa fa-file-circle-question mb-2" style="font-size:1.4rem;opacity:.4"></i><div>No assigned exams yet.</div>`;
        return;
      }

      examsPlaceholder.classList.add('d-none');
      examAccordion.classList.remove('d-none');
      examAccordion.innerHTML = '';
      quizzes.forEach(qz => examAccordion.appendChild(buildExamAccordionItem(qz, studentUuid)));

    } catch(e) {
      examsPlaceholder.innerHTML = `<i class="fa fa-triangle-exclamation mb-2" style="font-size:1.4rem;color:#dc2626"></i><div style="color:#dc2626">${esc(e.message || 'Failed to load')}</div>`;
    }
  }

  function buildExamAccordionItem(qz, studentUuid) {
    const wrap = document.createElement('div');
    wrap.className = 'exam-accordion-item';
    wrap.dataset.quizUuid = qz.quiz_uuid || qz.uuid || '';

    wrap.innerHTML = `
      <div class="exam-accordion-header">
        <div class="exam-acc-icon"><i class="fa fa-file-alt"></i></div>
        <div style="min-width:0;flex:1;">
          <p class="exam-acc-name">${esc(qz.quiz_name || 'Exam')}</p>
          <div class="exam-acc-meta">
            <span><i class="fa fa-clock" style="opacity:.6"></i> ${qz.total_time != null ? qz.total_time + ' min' : '—'}</span>
            <span><i class="fa fa-list-ol" style="opacity:.6"></i> ${qz.total_questions != null ? qz.total_questions + ' Qs' : '—'}</span>
            ${qz.assignment_code ? `<span><i class="fa fa-tag" style="opacity:.6"></i> ${esc(qz.assignment_code)}</span>` : ''}
          </div>
        </div>
        <div class="exam-acc-badges">
          <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;text-transform:uppercase;">Assigned</span>
        </div>
        <div class="exam-acc-chevron"><i class="fa fa-chevron-down"></i></div>
      </div>
      <div class="exam-accordion-body">
        <div class="exam-body-inner">
          <div class="exam-placeholder">
            <i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i>
            <span style="margin-left:8px;">Loading results…</span>
          </div>
        </div>
      </div>`;

    wrap.querySelector('.exam-accordion-header').addEventListener('click', () =>
      toggleExamAccordion(wrap, qz, studentUuid)
    );
    return wrap;
  }

  async function toggleExamAccordion(wrap, qz, studentUuid) {
    const isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.exam-accordion-item.open').forEach(el => {
      if (el !== wrap) el.classList.remove('open');
    });
    if (isOpen) { wrap.classList.remove('open'); return; }
    wrap.classList.add('open');

    const inner    = wrap.querySelector('.exam-body-inner');
    const quizUuid = qz.quiz_uuid || qz.uuid || '';
    if (!quizUuid) {
      inner.innerHTML = `<div class="exam-placeholder" style="color:#dc2626;">No quiz UUID available.</div>`;
      return;
    }
    if (examResultCache[quizUuid]) { renderGroupWise(inner, examResultCache[quizUuid]); return; }

    inner.innerHTML = `<div class="exam-placeholder"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i> <span>Loading results…</span></div>`;

    try {
      const res = await fetch(GROUP_WISE_URL(quizUuid, studentUuid), { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));

      if (!res.ok || !j.success) {
        inner.innerHTML = res.status === 404
          ? `<div class="exam-placeholder"><i class="fa fa-inbox" style="opacity:.4;font-size:1.3rem"></i><div class="mt-1">Student hasn't attempted this exam yet.</div></div>`
          : `<div class="exam-placeholder" style="color:#dc2626;"><i class="fa fa-triangle-exclamation"></i> ${esc(j.message || 'Failed to load results')}</div>`;
        return;
      }

      examResultCache[quizUuid] = j;
      renderGroupWise(inner, j);
    } catch(e) {
      inner.innerHTML = `<div class="exam-placeholder" style="color:#dc2626;"><i class="fa fa-triangle-exclamation"></i> ${esc(e.message || 'Network error')}</div>`;
    }
  }

  function renderGroupWise(container, data) {
    const attempts = data.attempts || [];
    if (!attempts.length) {
      container.innerHTML = `<div class="exam-placeholder">No attempt data found.</div>`;
      return;
    }

    let html = `<div class="attempt-tabs">`;
    attempts.forEach((a, i) => {
      html += `<button class="attempt-tab-btn ${i===0?'active':''}" data-idx="${i}">
        Attempt ${a.result?.attempt_number ?? (i+1)}
        ${pctChip(a.result?.percentage)}
      </button>`;
    });
    html += `</div>`;

    attempts.forEach((a, i) => {
      html += `<div class="exam-attempt-pane" data-idx="${i}" style="${i!==0?'display:none':''}">`;
      html += buildAttemptTable(a);
      html += `</div>`;
    });

    container.innerHTML = html;

    container.querySelectorAll('.attempt-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.idx);
        container.querySelectorAll('.attempt-tab-btn').forEach(b => b.classList.remove('active'));
        container.querySelectorAll('.exam-attempt-pane').forEach(p => p.style.display='none');
        btn.classList.add('active');
        container.querySelector(`.exam-attempt-pane[data-idx="${idx}"]`).style.display = '';
      });
    });
  }

  function pctChip(pct) {
    if (pct == null) return '';
    const p   = parseFloat(pct);
    const cls = p >= 70 ? 'pass' : p >= 40 ? 'avg' : 'fail';
    return `<span class="score-chip ${cls}" style="font-size:10px;padding:2px 7px;">${p.toFixed(1)}%</span>`;
  }

  function barColor(pct) {
    const p = parseFloat(pct || 0);
    return p >= 70 ? 'var(--success)' : p >= 40 ? 'var(--warn)' : '#ef4444';
  }

  function buildAttemptTable(a) {
    const groups  = a.groups  || [];
    const overall = a.overall || {};
    const result  = a.result  || {};
    const attempt = a.attempt || {};

    const finAt = attempt.finished_at
      ? new Date(attempt.finished_at).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
      : '—';

    let html = `
      <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
        <div style="font-size:13px;color:var(--muted);">
          <i class="fa fa-calendar-check me-1" style="opacity:.6"></i> Submitted:
          <strong style="color:var(--text)">${finAt}</strong>
        </div>
        <div style="font-size:13px;color:var(--muted);">
          <i class="fa fa-hashtag me-1" style="opacity:.6"></i> Score:
          <strong style="color:var(--text)">${result.marks_obtained ?? 0} / ${result.total_marks ?? 0}</strong>
        </div>
        ${pctChip(result.percentage) ? `<div>${pctChip(result.percentage)}</div>` : ''}
      </div>
      <div class="group-result-wrap">
        <table class="group-result-table">
          <thead>
            <tr>
              <th style="min-width:150px;">Group / Section</th>
              <th>Total Qs</th><th>Attempted</th><th>Skipped</th>
              <th>Correct</th><th>Incorrect</th><th>Marks</th>
              <th style="min-width:130px;">Score %</th>
            </tr>
          </thead>
          <tbody>`;

    groups.forEach(g => {
      const pct = parseFloat(g.percentage || 0);
      html += `<tr>
        <td style="font-weight:700;">${esc(g.group_title)}</td>
        <td>${g.total_questions}</td><td>${g.attempted}</td><td>${g.left}</td>
        <td style="color:var(--success);font-weight:700;">${g.correct}</td>
        <td style="color:#ef4444;font-weight:700;">${g.incorrect}</td>
        <td style="font-weight:700;">${g.marks_obtained} <span style="color:var(--muted);font-weight:400;">/ ${g.total_marks}</span></td>
        <td><div class="pct-bar-wrap">
          <div class="pct-bar-bg"><div class="pct-bar-fill" style="width:${pct}%;background:${barColor(pct)};"></div></div>
          <span class="pct-val" style="color:${barColor(pct)}">${pct.toFixed(1)}%</span>
        </div></td>
      </tr>`;
    });

    const ovPct = parseFloat(overall.percentage || 0);
    html += `<tr class="total-row">
      <td>Total</td>
      <td>${overall.total_questions ?? 0}</td><td>${overall.attempted ?? 0}</td><td>${overall.left ?? 0}</td>
      <td style="color:var(--success);">${overall.correct ?? 0}</td>
      <td style="color:#ef4444;">${overall.incorrect ?? 0}</td>
      <td>${overall.marks_obtained ?? 0} <span style="color:var(--muted);font-weight:400;">/ ${overall.total_marks ?? 0}</span></td>
      <td><div class="pct-bar-wrap">
        <div class="pct-bar-bg"><div class="pct-bar-fill" style="width:${ovPct}%;background:${barColor(ovPct)};"></div></div>
        <span class="pct-val" style="color:${barColor(ovPct)}">${ovPct.toFixed(1)}%</span>
      </div></td>
    </tr>`;

    html += `</tbody></table></div>`;
    return html;
  }

} // end boot()
</script>
@endpush