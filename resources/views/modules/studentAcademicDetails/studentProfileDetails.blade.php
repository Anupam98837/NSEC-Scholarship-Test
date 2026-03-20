{{-- resources/views/modules/leads/student-profile.blade.php --}}
@section('title', 'Student Profile')

@push('styles')
<style>
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
    --danger:       #dc2626;
    --danger-light: #fef2f2;
    --text:         #111827;
    --muted:        #6b7280;
    --border:       #e5e7eb;
    --surface:      #ffffff;
    --surface-2:    #f9fafb;
    --surface-3:    #f3f4f6;
    --radius:       12px;
    --radius-sm:    8px;
    --shadow:       0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    --shadow-md:    0 4px 16px rgba(0,0,0,.1);
    --shadow-lg:    0 8px 32px rgba(0,0,0,.12);
    --transition:   all .15s ease;
  }

  /* ── Layout ── */
  .sp-wrap { max-width: 1000px; margin: 0 auto; padding: 32px 20px 72px; }

  /* ── Back bar ── */
  .sp-back {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 13px; font-weight: 600; color: var(--muted);
    text-decoration: none; margin-bottom: 20px;
    padding: 6px 12px 6px 8px;
    border-radius: 8px;
    transition: var(--transition);
  }
  .sp-back:hover { background: var(--surface-2); color: var(--text); }
  .sp-back i { font-size: 11px; }

  /* ── Hero Card ── */
  .sp-hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    padding: 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }
  .sp-avatar-lg {
    width: 72px; height: 72px; border-radius: 18px; flex-shrink: 0;
    background: var(--brand-light); border: 2px solid rgba(201,75,80,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 800; color: var(--brand);
    letter-spacing: -.02em;
  }
  .sp-hero-info { flex: 1; min-width: 0; }
  .sp-hero-name {
    font-size: 1.35rem; font-weight: 800; color: var(--text);
    margin: 0 0 6px; letter-spacing: -.03em;
  }
  .sp-hero-meta {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap; font-size: 13px; color: var(--muted);
  }
  .sp-hero-meta span { display: flex; align-items: center; gap: 5px; }
  .sp-hero-meta i { opacity: .6; font-size: 11px; }
  .sp-hero-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }

  /* ── Badges ── */
  .badge-assigned {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--success-light); color: var(--success); border: 1px solid rgba(22,163,74,.2);
  }
  .badge-unassigned {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: var(--warn-light); color: var(--warn); border: 1px solid rgba(217,119,6,.2);
  }

  /* ── Tabs ── */
  .sp-tabs {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: 20px;
  }
  .sp-tab-nav {
    background: var(--surface-2); border-bottom: 1px solid var(--border); padding: 0 16px;
    display: flex; gap: 2px;
  }
  .sp-tab-btn {
    border: 0; border-bottom: 2px solid transparent;
    border-radius: 0; padding: 12px 14px;
    font-size: 13px; font-weight: 700; color: var(--muted);
    background: transparent; cursor: pointer;
    transition: var(--transition);
    display: flex; align-items: center; gap: 6px;
  }
  .sp-tab-btn:hover { color: var(--text); }
  .sp-tab-btn.active { color: var(--text); border-bottom-color: var(--brand); }
  .sp-tab-btn i { font-size: 12px; }
  .sp-tab-body { padding: 24px; }
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
    border-radius: var(--radius-sm);
    min-height: 36px;
    transition: var(--transition);
    word-break: break-word;
  }
  .info-field .field-val.empty { color: var(--muted); font-weight: 400; font-style: italic; }

  /* ── Edit mode ── */
  .info-field input.field-input,
  .info-field select.field-input,
  .info-field textarea.field-input {
    font-size: 14px; font-weight: 500; color: var(--text);
    padding: 7px 10px;
    background: var(--surface);
    border: 1.5px solid var(--brand);
    border-radius: var(--radius-sm);
    outline: none;
    width: 100%;
    transition: var(--transition);
    font-family: inherit;
    box-shadow: 0 0 0 3px rgba(201,75,80,.08);
  }
  .info-field textarea.field-input { resize: vertical; min-height: 72px; }
  .info-field input.field-input:focus,
  .info-field select.field-input:focus,
  .info-field textarea.field-input:focus {
    border-color: var(--brand-dark);
    box-shadow: 0 0 0 3px rgba(201,75,80,.15);
  }

  /* ── Divider ── */
  .sp-divider { height: 1px; background: var(--border); margin: 20px 0; }

  /* ── Buttons ── */
  .btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
    border: none; background: var(--brand); color: #fff;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(201,75,80,.3);
  }
  .btn-primary:hover { background: var(--brand-dark); transform: translateY(-1px); }
  .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

  .btn-secondary {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
    border: 1px solid var(--border); background: var(--surface); color: var(--muted);
    transition: var(--transition);
  }
  .btn-secondary:hover { background: var(--surface-2); color: var(--text); }

  .btn-edit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1px solid rgba(201,75,80,.25); background: var(--brand-light); color: var(--brand);
    transition: var(--transition);
  }
  .btn-edit:hover { background: #fbe0e1; transform: translateY(-1px); }

  .btn-save {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1px solid rgba(22,163,74,.25); background: var(--success-light); color: var(--success);
    transition: var(--transition);
  }
  .btn-save:hover { background: #dcfce7; transform: translateY(-1px); }
  .btn-save:disabled { opacity: .5; cursor: not-allowed; transform: none; }

  .btn-cancel {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1px solid var(--border); background: var(--surface); color: var(--muted);
    transition: var(--transition);
  }
  .btn-cancel:hover { background: var(--surface-2); color: var(--text); }

  /* ── State ── */
  .sp-state {
    background: var(--surface); border: 1.5px dashed var(--border);
    border-radius: 14px; padding: 52px 24px; text-align: center; color: var(--muted);
  }
  .sp-state .state-icon {
    width: 52px; height: 52px; background: var(--surface-2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin: 0 auto 12px;
  }
  .sp-state .state-title { font-size: 15px; font-weight: 800; color: var(--text); margin: 0 0 4px; }

  /* ── Placeholder ── */
  .tab-placeholder {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 10px; padding: 32px 24px;
    font-size: 13px; color: var(--muted); text-align: center;
  }
  .tab-placeholder i { font-size: 1.6rem; opacity: .35; margin-bottom: 10px; display: block; }

  /* ── Chip tags ── */
  .chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; border: 1px solid;
  }
  .chip-blue   { background: var(--info-light);    color: var(--info);    border-color: rgba(79,70,229,.2); }
  .chip-green  { background: var(--success-light); color: var(--success); border-color: rgba(22,163,74,.2); }
  .chip-orange { background: var(--warn-light);    color: var(--warn);    border-color: rgba(217,119,6,.2); }

  /* ── Exam accordion (reused from fresh-leads) ── */
  .exam-accordion-item {
    border: 1px solid var(--border); border-radius: var(--radius);
    overflow: hidden; margin-bottom: 10px;
    background: var(--surface); box-shadow: var(--shadow); transition: box-shadow .2s;
  }
  .exam-accordion-item:hover { box-shadow: var(--shadow-md); }
  .exam-accordion-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; cursor: pointer; user-select: none;
    border-bottom: 1px solid transparent; transition: background .15s, border-color .15s;
  }
  .exam-accordion-header:hover { background: var(--surface-2); }
  .exam-accordion-item.open .exam-accordion-header { background: var(--surface-2); border-bottom-color: var(--border); }
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

  /* ── Attempt tabs ── */
  .attempt-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .attempt-tab-btn {
    padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; cursor: pointer;
    border: 1.5px solid var(--border); background: var(--surface); color: var(--muted); transition: all .15s;
  }
  .attempt-tab-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }
  .attempt-tab-btn:hover:not(.active) { border-color: var(--brand); color: var(--brand); }

  /* ── Group table ── */
  .group-result-wrap { overflow-x: auto; }
  .group-result-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .group-result-table th {
    padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em; color: var(--muted);
    background: var(--surface-2); border-bottom: 1px solid var(--border); white-space: nowrap;
  }
  .group-result-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); color: var(--text); font-weight: 500; }
  .group-result-table tr:last-child td { border-bottom: none; }
  .group-result-table tr.total-row td { background: var(--surface-2); font-weight: 800; border-top: 2px solid var(--border); }
  .group-result-table tr:hover:not(.total-row) td { background: var(--surface-2); }
  .pct-bar-wrap { display: flex; align-items: center; gap: 8px; }
  .pct-bar-bg { flex: 1; height: 6px; background: var(--border); border-radius: 99px; overflow: hidden; }
  .pct-bar-fill { height: 100%; border-radius: 99px; transition: width .4s cubic-bezier(.4,0,.2,1); }
  .pct-val { font-size: 12px; font-weight: 700; min-width: 38px; text-align: right; }
  .score-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800;
  }
  .score-chip.pass { background: var(--success-light); color: var(--success); border: 1px solid rgba(22,163,74,.2); }
  .score-chip.avg  { background: var(--warn-light);    color: var(--warn);    border: 1px solid rgba(217,119,6,.2); }
  .score-chip.fail { background: #fef2f2;              color: #dc2626;        border: 1px solid rgba(220,38,38,.2); }

  /* ── Exam placeholder ── */
  .exam-placeholder {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px 16px;
    font-size: 13px; color: var(--muted); text-align: center;
  }

  @media (max-width: 640px) {
    .sp-hero { flex-direction: column; align-items: flex-start; }
    .sp-hero-actions { width: 100%; }
    .info-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 420px) {
    .info-grid { grid-template-columns: 1fr; }
  }
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
<div class="container-fluid sp-wrap">

  {{-- Back --}}
  <a href="javascript:history.back()" class="sp-back">
    <i class="fa fa-arrow-left"></i> Back
  </a>

  {{-- Loading / Error state --}}
  <div id="spState" class="sp-state">
    <div class="state-icon"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i></div>
    <p class="state-title">Loading Profile</p>
    <p class="mt-1" style="font-size:13px">Fetching student details…</p>
  </div>

  {{-- Profile (hidden until loaded) --}}
  <div id="spMain" class="d-none">

    {{-- Hero --}}
    <div class="sp-hero">
      <div class="sp-avatar-lg" id="heroAvatar">—</div>
      <div class="sp-hero-info">
        <h1 class="sp-hero-name" id="heroName">—</h1>
        <div class="sp-hero-meta">
          <span id="heroStatusBadge" class="badge-unassigned"><i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned</span>
          <span><i class="fa-regular fa-envelope"></i> <span id="heroEmail">—</span></span>
          <span><i class="fa fa-phone"></i> <span id="heroPhone">—</span></span>
          <span id="heroChips" class="d-flex gap-1 flex-wrap"></span>
        </div>
      </div>
      <div class="sp-hero-actions">
        <button class="btn-primary" id="btnBackToLeads" type="button">
          <i class="fa fa-arrow-left"></i> My Leads
        </button>
      </div>
    </div>

    {{-- Tabs --}}
    <div class="sp-tabs">
      <div class="sp-tab-nav">
        <button class="sp-tab-btn active" data-tab="tabContact"><i class="fa fa-address-card"></i> Contact</button>
        <button class="sp-tab-btn d-none" data-tab="tabAcademic"><i class="fa fa-graduation-cap"></i> Academic</button>
        <button class="sp-tab-btn" data-tab="tabExams"><i class="fa fa-file-alt"></i> Exams</button>
        <button class="sp-tab-btn" data-tab="tabActivity"><i class="fa fa-chart-line"></i> Activity</button>
        <button class="sp-tab-btn" data-tab="tabComms"><i class="fa fa-comments"></i> Communications</button>
      </div>

      <div class="sp-tab-body">

        {{-- ── CONTACT TAB ── --}}
        <div class="sp-tab-pane active" id="tabContact">
          <div class="section-head">
            <div class="section-title"><i class="fa fa-user"></i> Personal &amp; Contact Details</div>
            <div class="d-flex gap-2" id="contactActions">
              <button class="btn-edit" id="btnEditContact"><i class="fa fa-pen"></i> Edit</button>
            </div>
          </div>

          <div class="info-grid" id="contactGrid">
            <!-- rendered by JS -->
          </div>

          <div class="sp-divider"></div>

          <div class="section-head">
            <div class="section-title"><i class="fa fa-shield-halved"></i> Guardian Information</div>
          </div>
          <div class="info-grid" id="guardianGrid"></div>

          <div class="sp-divider"></div>

          <div class="section-head">
            <div class="section-title"><i class="fa fa-school"></i> School / Exam Details</div>
          </div>
          <div class="info-grid" id="schoolGrid"></div>
        </div>

        {{-- ── ACADEMIC TAB ── --}}
        <div class="sp-tab-pane" id="tabAcademic">
          <div class="section-head">
            <div class="section-title"><i class="fa fa-book-open"></i> Academic Background</div>
            <div class="d-flex gap-2" id="acadActions">
              <button class="btn-edit" id="btnEditAcad"><i class="fa fa-pen"></i> Edit</button>
            </div>
          </div>
          <div class="info-grid" id="acadGrid"></div>

          <div class="sp-divider"></div>

          <div class="section-head">
            <div class="section-title"><i class="fa fa-globe"></i> Study Abroad Preferences</div>
          </div>
          <div class="info-grid" id="prefGrid"></div>
        </div>

        {{-- ── EXAMS TAB ── --}}
        <div class="sp-tab-pane" id="tabExams">
          <div class="section-head">
            <div class="section-title"><i class="fa fa-file-circle-check"></i> Assigned Exams &amp; Results</div>
          </div>
          <div id="examsPlaceholder" class="exam-placeholder">
            <i class="fa fa-file-circle-question" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:6px;"></i>
            No exam results yet.
          </div>
          <div id="examAccordion" class="d-none"></div>
        </div>

        {{-- ── ACTIVITY TAB ── --}}
       {{-- ── ACTIVITY TAB ── --}}
<div class="sp-tab-pane" id="tabActivity">
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
      <i class="fa fa-chart-line" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:6px;"></i>
      Activity will appear here once loaded.
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

        {{-- ── COMMS TAB ── --}}
        <div class="sp-tab-pane" id="tabComms">
          <div class="tab-placeholder">
            <i class="fa fa-comments"></i>
            Call logs, WhatsApp and email history will appear here.
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function waitForLibs() {
  if (typeof Swal === 'undefined') return setTimeout(waitForLibs, 50);
  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();

async function boot() {

  /* ══ CONFIG ══════════════════════════════════════════════ */
  const AUTH_CHECK_URL          = '/api/auth/check';
  const USER_URL        = (uuid) => `/api/student-profile-details?uuid=${encodeURIComponent(uuid)}`;
const USER_UPDATE_URL = (id)   => `/api/users/${id}`;
  const USER_ASSIGNED_QUIZZES   = (id) => `/api/users/${id}/quizzes`;
  const GROUP_WISE_URL          = (examUuid, studentUuid) =>
    `/api/exam/group-wise-result?exam_key=${examUuid}&student_key=${studentUuid}`;
    const ACTIVITY_LOGS_URL = (studentId, page, module, activity) => {
  const p = new URLSearchParams({ student_id: studentId, limit: 15, page, sort: 'desc' });
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

  /* ══ URL PARAMS ══════════════════════════════════════════ */
  const params = new URLSearchParams(location.search);
  const SID    = params.get('sid') || params.get('id') || '';
  const UUID   = params.get('uuid') || params.get('student') || '';

  /* ══ DOM ═════════════════════════════════════════════════ */
  const spState   = document.getElementById('spState');
  const spMain    = document.getElementById('spMain');

  /* ══ STATE ═══════════════════════════════════════════════ */
  let student   = null;
  let editMode  = { contact: false, acad: false };
  let examCache = {};

  /* ══ UTILS ═══════════════════════════════════════════════ */
  function esc(s) {
    const m = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' };
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => m[c]);
  }
  function v(val, fb = '—') {
    return (val !== null && val !== undefined && String(val).trim() !== '') ? String(val) : fb;
  }
  function initials(n) {
    return (n||'?').trim().split(' ').slice(0,2).map(p=>p[0]).join('').toUpperCase();
  }

  /* ══ TABS ════════════════════════════════════════════════ */
  /* ══ TABS ════════════════════════════════════════════════ */
document.querySelectorAll('.sp-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.sp-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sp-tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');

    if (btn.dataset.tab === 'tabExams'    && student) loadExamsTab();
    if (btn.dataset.tab === 'tabActivity' && student) loadActivityTab(true);
  });
});

  /* ══ LOAD STUDENT ════════════════════════════════════════ */
  async function loadStudent() {
  if (!UUID && !SID) {
    showError('No student ID in URL.');
    return;
  }
  try {
    const res = await fetch(USER_URL(UUID || SID), { headers: hdrs() });
    const j   = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j.message || 'Failed to load');

    const raw = j.student    ?? {};
    const pad = j.pad        ?? {};
    const asg = j.assignment ?? {};

    student = {
      ...pad,
      ...raw,

      // core user fields (always from raw)
      id:                         raw.id,
      uuid:                       raw.uuid                      ?? '',
      name:                       raw.name                      ?? '',
      email:                      raw.email                     ?? '',
      phone_number:               raw.phone_number              ?? '',
      alternative_phone_number:   raw.alternative_phone_number  ?? '',
      whatsapp_number:            raw.whatsapp_number           ?? '',
      alternative_email:          raw.alternative_email         ?? '',
      address:                    raw.address                   ?? '',
      role:                       raw.role                      ?? '',
      role_short_form:            raw.role_short_form           ?? '',
      status:                     raw.status                    ?? '',

      // personal/academic details (from pad table)
      guardian_name:              pad.guardian_name             ?? '',
      guardian_number:            pad.guardian_number           ?? '',
      student_class:              pad.class ?? pad.student_class ?? '',
      board:                      pad.board                     ?? '',
      exam_type:                  pad.exam_type                 ?? '',
      year_of_passout:            pad.year_of_passout           ?? '',
      highest_qualification:      pad.highest_qualification     ?? '',
      field_of_study:             pad.field_of_study            ?? '',
      institution_name:           pad.institution_name          ?? '',
      graduation_year:            pad.graduation_year           ?? '',
      gpa:                        pad.gpa                       ?? '',
      english_proficiency:        pad.english_proficiency       ?? '',
      english_test_score:         pad.english_test_score        ?? '',
      preferred_country:          pad.preferred_country         ?? '',
      preferred_course:           pad.preferred_course          ?? '',
      intake_month:               pad.intake_month              ?? '',
      intake_year:                pad.intake_year               ?? '',
      study_budget:               pad.study_budget              ?? '',

      // assignment info
      assignedTo:                 asg.counsellor_name ?? asg.counsellor_id ?? null,
      counsellor_id:              asg.counsellor_id   ?? null,
      counsellor_name:            asg.counsellor_name ?? '',
    };

    renderProfile();
  } catch(e) {
    showError(e.message);
  }
}

  function showError(msg) {
    spState.innerHTML = `
      <div class="state-icon"><i class="fa fa-triangle-exclamation" style="color:#dc2626"></i></div>
      <p class="state-title" style="color:#dc2626">Failed to Load</p>
      <p class="mt-1" style="font-size:13px">${esc(msg)}</p>`;
  }

  /* ══ RENDER PROFILE ══════════════════════════════════════ */
  function renderProfile() {
    const s = student;
    spState.classList.add('d-none');
    spMain.classList.remove('d-none');

    // Hero
    const ini = initials(s.name);
    document.getElementById('heroAvatar').textContent = ini;
    document.getElementById('heroName').textContent   = v(s.name);
    document.getElementById('heroEmail').textContent  = v(s.email);
    document.getElementById('heroPhone').textContent  = v(s.phone_number || s.phone || s.mobile);

    // Hero chips
    const chips = [];
    if (s.role_short_form) chips.push(`<span class="chip chip-blue">${esc(s.role_short_form)}</span>`);
    if (s.status)          chips.push(`<span class="chip ${s.status==='active'?'chip-green':'chip-orange'}">${esc(s.status)}</span>`);
    document.getElementById('heroChips').innerHTML = chips.join('');

    // Status badge
    const badge = document.getElementById('heroStatusBadge');
    const isAssigned = !!(s.counsellor_id || s.assignedTo);
    badge.className = isAssigned ? 'badge-assigned' : 'badge-unassigned';
    badge.innerHTML = isAssigned
      ? `<i class="fa fa-circle" style="font-size:.45rem"></i> Assigned`
      : `<i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned`;

    renderContactTab();
    renderAcadTab();
  }

  /* ══ CONTACT TAB ═════════════════════════════════════════ */
  const CONTACT_FIELDS = [
    { key: 'name',                     label: 'Full Name',          type: 'text' },
    { key: 'email',                    label: 'Primary Email',      type: 'email' },
    { key: 'phone_number',             label: 'Mobile Number',      type: 'tel' },
    { key: 'alternative_phone_number', label: 'Alternative Phone',  type: 'tel' },
    { key: 'whatsapp_number',          label: 'WhatsApp',           type: 'tel' },
    { key: 'alternative_email',        label: 'Alternative Email',  type: 'email' },
    { key: 'address',                  label: 'Address',            type: 'textarea' },
  ];
  const GUARDIAN_FIELDS = [
    { key: 'guardian_name',   label: 'Guardian Name',   type: 'text' },
    { key: 'guardian_number', label: 'Guardian Phone',  type: 'tel' },
  ];
  const SCHOOL_FIELDS = [
    { key: 'student_class',    label: 'Enrolled Class',    type: 'text' },
    { key: 'board',            label: 'Education Board',   type: 'text' },
    { key: 'exam_type',        label: 'Exam Type',         type: 'text' },
    { key: 'year_of_passout',  label: 'Year of Passout',   type: 'text' },
  ];

  function renderContactTab(editing = false) {
    renderFieldGrid('contactGrid',  CONTACT_FIELDS,  editing);
    renderFieldGrid('guardianGrid', GUARDIAN_FIELDS, editing);
    renderFieldGrid('schoolGrid',   SCHOOL_FIELDS,   editing);

    const actions = document.getElementById('contactActions');
    if (editing) {
      actions.innerHTML = `
        <button class="btn-cancel" id="btnCancelContact"><i class="fa fa-xmark"></i> Cancel</button>
        <button class="btn-save"   id="btnSaveContact"><i class="fa fa-check"></i> Save Changes</button>`;
      document.getElementById('btnCancelContact').addEventListener('click', () => renderContactTab(false));
      document.getElementById('btnSaveContact').addEventListener('click',   () => saveSection('contact'));
    } else {
      actions.innerHTML = `<button class="btn-edit" id="btnEditContact"><i class="fa fa-pen"></i> Edit</button>`;
      document.getElementById('btnEditContact').addEventListener('click',   () => renderContactTab(true));
    }
  }

  /* ══ ACADEMIC TAB ════════════════════════════════════════ */
  const ACAD_FIELDS = [
    { key: 'highest_qualification', label: 'Qualification',     type: 'text' },
    { key: 'field_of_study',        label: 'Field of Study',    type: 'text' },
    { key: 'institution_name',      label: 'Institution',       type: 'text' },
    { key: 'graduation_year',       label: 'Graduation Year',   type: 'text' },
    { key: 'gpa',                   label: 'GPA / Percentage',  type: 'text' },
    { key: 'english_proficiency',   label: 'English Test',      type: 'select',
      options: ['', 'IELTS', 'TOEFL', 'PTE', 'Duolingo', 'Other', 'None'] },
    { key: 'english_test_score',    label: 'Test Score',        type: 'text' },
  ];
  const PREF_FIELDS = [
    { key: 'preferred_country', label: 'Preferred Country', type: 'text' },
    { key: 'preferred_course',  label: 'Preferred Course',  type: 'text' },
    { key: 'intake_month',      label: 'Intake Month',      type: 'select',
      options: ['', 'January','February','March','April','May','June','July','August','September','October','November','December'] },
    { key: 'intake_year',       label: 'Intake Year',       type: 'text' },
    { key: 'study_budget',      label: 'Budget',            type: 'text' },
  ];

  function renderAcadTab(editing = false) {
    renderFieldGrid('acadGrid', ACAD_FIELDS, editing);
    renderFieldGrid('prefGrid', PREF_FIELDS, editing);

    const actions = document.getElementById('acadActions');
    if (editing) {
      actions.innerHTML = `
        <button class="btn-cancel" id="btnCancelAcad"><i class="fa fa-xmark"></i> Cancel</button>
        <button class="btn-save"   id="btnSaveAcad"><i class="fa fa-check"></i> Save Changes</button>`;
      document.getElementById('btnCancelAcad').addEventListener('click', () => renderAcadTab(false));
      document.getElementById('btnSaveAcad').addEventListener('click',   () => saveSection('acad'));
    } else {
      actions.innerHTML = `<button class="btn-edit" id="btnEditAcad"><i class="fa fa-pen"></i> Edit</button>`;
      document.getElementById('btnEditAcad').addEventListener('click',   () => renderAcadTab(true));
    }
  }
let actPage    = 1;
let actTotal   = 0;
const ACT_LIMIT = 15;

const actFeed        = document.getElementById('activityFeed');
const actPlaceholder = document.getElementById('actPlaceholder');
const actPagination  = document.getElementById('actPagination');
const actPrevBtn     = document.getElementById('actPrevBtn');
const actNextBtn     = document.getElementById('actNextBtn');
const actPagInfo     = document.getElementById('actPaginationInfo');
const actModFilter   = document.getElementById('actModuleFilter');
const actTypeFilter  = document.getElementById('actTypeFilter');

// Icon + colour mapping per activity type
function actIcon(type) {
  const map = {
    store:   { icon: 'fa-plus',        cls: 'store'   },
    update:  { icon: 'fa-pen',         cls: 'update'  },
    delete:  { icon: 'fa-trash',       cls: 'delete'  },
    default: { icon: 'fa-circle-dot',  cls: 'default' },
  };
  const t = (type || '').toLowerCase();
  if (t.startsWith('toggled')) return { icon: 'fa-toggle-on', cls: 'toggled' };
  return map[t] || map.default;
}

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

    const note = row.log_note || row.description || row.message || '';
    const target = row.target || row.record_id || '';

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
          ${note    ? `<div class="activity-note">${esc(note)}</div>` : ''}
          ${target  ? `<div class="activity-note" style="color:var(--muted);font-size:11px;">Record: ${esc(String(target))}</div>` : ''}
          <div class="activity-meta">
            <span><i class="fa fa-clock"></i> ${timeStr}</span>
            ${row.ip ? `<span><i class="fa fa-network-wired"></i> ${esc(row.ip)}</span>` : ''}
          </div>
        </div>
      </div>`;
  }).join('');

  // pagination info + button states
  const totalPages = Math.ceil(actTotal / ACT_LIMIT) || 1;
  actPagInfo.textContent = `Page ${actPage} of ${totalPages}  (${actTotal} total)`;
  actPrevBtn.disabled = actPage <= 1;
  actNextBtn.disabled = actPage >= totalPages;
}

async function loadActivityTab(resetPage = false) {
  // Get the student ID — works for both pages
  // fresh-leads uses `focused.id`, student-profile uses `student.id || SID`
  const studentId = (typeof focused !== 'undefined' ? focused?.raw_id || focused?.id : null)
                 || (typeof student  !== 'undefined' ? student?.id  || SID : null)
                 || '';

  if (!studentId) return;
  if (resetPage) actPage = 1;

  const module   = actModFilter?.value  || '';
  const activity = actTypeFilter?.value || '';

  actPlaceholder.classList.remove('d-none');
  actPlaceholder.innerHTML = `<i class="fa fa-circle-notch fa-spin" style="color:var(--brand);font-size:1.4rem;display:block;margin-bottom:6px;"></i> Loading activity…`;
  actFeed.classList.add('d-none');
  actPagination.classList.add('d-none');

  try {
    const res = await fetch(ACTIVITY_LOGS_URL(studentId, actPage, module, activity), { headers: hdrs() });
    const j   = await res.json().catch(() => ({}));
    if (!res.ok || !j.ok) throw new Error(j.error || 'Failed to load');

    actTotal = j.total ?? 0;
    renderActivityFeed(Array.isArray(j.data) ? j.data : []);

    // Populate module dropdown on first load
    if (resetPage && actModFilter && actModFilter.options.length === 1) {
      populateModuleDropdown(studentId);
    }
  } catch(e) {
    actPlaceholder.classList.remove('d-none');
    actFeed.classList.add('d-none');
    actPagination.classList.add('d-none');
    actPlaceholder.innerHTML = `<i class="fa fa-triangle-exclamation" style="color:#dc2626;font-size:1.4rem;display:block;margin-bottom:6px;"></i><span style="color:#dc2626">${esc(e.message)}</span>`;
  }
}

async function populateModuleDropdown(studentId) {
  try {
    // fetch distinct modules for this student only
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

// Tab click hook — detect which page we're on


if (actPrevBtn) actPrevBtn.addEventListener('click', () => { actPage--; loadActivityTab(); });
if (actNextBtn) actNextBtn.addEventListener('click', () => { actPage++; loadActivityTab(); });
if (actModFilter)  actModFilter.addEventListener('change',  () => loadActivityTab(true));
if (actTypeFilter) actTypeFilter.addEventListener('change', () => loadActivityTab(true));

  /* ══ FIELD GRID RENDERER ═════════════════════════════════ */
  function renderFieldGrid(gridId, fields, editing) {
    const grid = document.getElementById(gridId);
    if (!grid) return;
    grid.innerHTML = fields.map(f => {
      const raw = student[f.key] ?? '';
      const display = v(raw);
      const isEmpty = display === '—';

      if (!editing) {
        return `
          <div class="info-field">
            <label>${esc(f.label)}</label>
            <div class="field-val${isEmpty ? ' empty' : ''}">${esc(display)}</div>
          </div>`;
      }

      if (f.type === 'textarea') {
        return `
          <div class="info-field" style="grid-column: span 2;">
            <label>${esc(f.label)}</label>
            <textarea class="field-input" data-key="${esc(f.key)}" rows="3">${esc(raw)}</textarea>
          </div>`;
      }

      if (f.type === 'select') {
        const opts = (f.options || []).map(o =>
          `<option value="${esc(o)}" ${o === raw ? 'selected' : ''}>${o || '— Select —'}</option>`
        ).join('');
        return `
          <div class="info-field">
            <label>${esc(f.label)}</label>
            <select class="field-input" data-key="${esc(f.key)}">${opts}</select>
          </div>`;
      }

      return `
        <div class="info-field">
          <label>${esc(f.label)}</label>
          <input class="field-input" type="${esc(f.type)}" data-key="${esc(f.key)}" value="${esc(raw)}">
        </div>`;
    }).join('');
  }

  /* ══ SAVE SECTION ════════════════════════════════════════ */
  async function saveSection(section) {
    const allFields = section === 'contact'
      ? [...CONTACT_FIELDS, ...GUARDIAN_FIELDS, ...SCHOOL_FIELDS]
      : [...ACAD_FIELDS, ...PREF_FIELDS];

    const payload = {};
    allFields.forEach(f => {
      const el = document.querySelector(`[data-key="${f.key}"]`);
      if (el) payload[f.key] = el.value;
    });

    const saveBtn = document.getElementById(section === 'contact' ? 'btnSaveContact' : 'btnSaveAcad');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> Saving…'; }

    try {
const id = student.id ?? SID;
      const res = await fetch(USER_UPDATE_URL(id), {
        method: 'PUT',
        headers: hdrs({ 'Content-Type': 'application/json' }),
        body: JSON.stringify(payload),
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || 'Update failed');

      // Merge updates into local student object
      Object.assign(student, payload);

      // Re-render hero
      document.getElementById('heroName').textContent  = v(student.name);
      document.getElementById('heroEmail').textContent = v(student.email);
      document.getElementById('heroPhone').textContent = v(student.phone_number || student.phone);
      document.getElementById('heroAvatar').textContent = initials(student.name);

      if (section === 'contact') renderContactTab(false);
      else                       renderAcadTab(false);

      Swal.fire({ icon:'success', title:'Saved!', text:'Profile updated successfully.', timer:1400, showConfirmButton:false });

    } catch(e) {
      if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fa fa-check"></i> Save Changes'; }
      Swal.fire({ icon:'error', title:'Failed', text: e.message || 'Could not save.' });
    }
  }

  /* ══ EXAMS TAB ═══════════════════════════════════════════ */
  async function loadExamsTab() {
    if (!student) return;
    const userId      = student.id || SID;
    const studentUuid = student.uuid || UUID;

    const placeholder = document.getElementById('examsPlaceholder');
    const accordion   = document.getElementById('examAccordion');

    accordion.innerHTML = '';
    accordion.classList.add('d-none');
    placeholder.classList.remove('d-none');
    placeholder.innerHTML = `<i class="fa fa-circle-notch fa-spin" style="color:var(--brand);font-size:1.4rem;display:block;margin-bottom:6px;"></i> Loading exams…`;

    try {
      const res = await fetch(USER_ASSIGNED_QUIZZES(userId), { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      const quizzes = (Array.isArray(j.data) ? j.data : []).filter(q => !!q.assigned);

      if (!quizzes.length) {
        placeholder.innerHTML = `<i class="fa fa-file-circle-question" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:6px;"></i> No assigned exams yet.`;
        return;
      }

      placeholder.classList.add('d-none');
      accordion.classList.remove('d-none');
      accordion.innerHTML = '';
      quizzes.forEach(qz => accordion.appendChild(buildExamItem(qz, studentUuid)));

    } catch(e) {
      placeholder.innerHTML = `<i class="fa fa-triangle-exclamation" style="color:#dc2626;font-size:1.4rem;display:block;margin-bottom:6px;"></i> <span style="color:#dc2626">${esc(e.message || 'Failed to load')}</span>`;
    }
  }

  function buildExamItem(qz, studentUuid) {
    const wrap = document.createElement('div');
    wrap.className = 'exam-accordion-item';
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
          <div class="exam-placeholder"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i> Loading results…</div>
        </div>
      </div>`;
    wrap.querySelector('.exam-accordion-header').addEventListener('click', () => toggleExam(wrap, qz, studentUuid));
    return wrap;
  }

  async function toggleExam(wrap, qz, studentUuid) {
    const isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.exam-accordion-item.open').forEach(el => { if (el !== wrap) el.classList.remove('open'); });
    if (isOpen) { wrap.classList.remove('open'); return; }
    wrap.classList.add('open');

    const inner    = wrap.querySelector('.exam-body-inner');
    const quizUuid = qz.quiz_uuid || qz.uuid || '';
    if (!quizUuid) { inner.innerHTML = `<div class="exam-placeholder" style="color:#dc2626;">No quiz UUID available.</div>`; return; }
    if (examCache[quizUuid]) { renderGroupWise(inner, examCache[quizUuid]); return; }

    inner.innerHTML = `<div class="exam-placeholder"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i> Loading…</div>`;
    try {
      const res = await fetch(GROUP_WISE_URL(quizUuid, studentUuid), { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!res.ok || !j.success) {
        inner.innerHTML = res.status === 404
          ? `<div class="exam-placeholder"><i class="fa fa-inbox" style="opacity:.4;font-size:1.3rem;display:block;margin-bottom:6px;"></i> Student hasn't attempted this exam yet.</div>`
          : `<div class="exam-placeholder" style="color:#dc2626;"><i class="fa fa-triangle-exclamation"></i> ${esc(j.message || 'Failed')}</div>`;
        return;
      }
      examCache[quizUuid] = j;
      renderGroupWise(inner, j);
    } catch(e) {
      inner.innerHTML = `<div class="exam-placeholder" style="color:#dc2626;"><i class="fa fa-triangle-exclamation"></i> ${esc(e.message)}</div>`;
    }
  }

  function pctChip(pct) {
    if (pct == null) return '';
    const p = parseFloat(pct);
    return `<span class="score-chip ${p>=70?'pass':p>=40?'avg':'fail'}" style="font-size:10px;padding:2px 7px;">${p.toFixed(1)}%</span>`;
  }
  function barColor(pct) {
    const p = parseFloat(pct||0);
    return p>=70 ? 'var(--success)' : p>=40 ? 'var(--warn)' : '#ef4444';
  }

  function renderGroupWise(container, data) {
    const attempts = data.attempts || [];
    if (!attempts.length) { container.innerHTML = `<div class="exam-placeholder">No attempt data found.</div>`; return; }

    let html = `<div class="attempt-tabs">`;
    attempts.forEach((a, i) => {
      html += `<button class="attempt-tab-btn ${i===0?'active':''}" data-idx="${i}">
        Attempt ${a.result?.attempt_number ?? (i+1)} ${pctChip(a.result?.percentage)}
      </button>`;
    });
    html += `</div>`;
    attempts.forEach((a, i) => {
      const groups = a.groups || []; const overall = a.overall || {}; const result = a.result || {}; const attempt = a.attempt || {};
      const finAt = attempt.finished_at ? new Date(attempt.finished_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';
      html += `<div class="exam-attempt-pane" data-idx="${i}" style="${i!==0?'display:none':''}">`;
      html += `<div class="d-flex align-items-center gap-3 flex-wrap mb-3">
        <div style="font-size:13px;color:var(--muted);"><i class="fa fa-calendar-check me-1" style="opacity:.6"></i> Submitted: <strong style="color:var(--text)">${finAt}</strong></div>
        <div style="font-size:13px;color:var(--muted);"><i class="fa fa-hashtag me-1" style="opacity:.6"></i> Score: <strong style="color:var(--text)">${result.marks_obtained??0} / ${result.total_marks??0}</strong></div>
        ${pctChip(result.percentage) ? `<div>${pctChip(result.percentage)}</div>` : ''}
      </div>
      <div class="group-result-wrap"><table class="group-result-table">
        <thead><tr><th>Group / Section</th><th>Total Qs</th><th>Attempted</th><th>Skipped</th><th>Correct</th><th>Incorrect</th><th>Marks</th><th style="min-width:130px;">Score %</th></tr></thead>
        <tbody>`;
      groups.forEach(g => {
        const pct = parseFloat(g.percentage||0);
        html += `<tr>
          <td style="font-weight:700;">${esc(g.group_title)}</td>
          <td>${g.total_questions}</td><td>${g.attempted}</td><td>${g.left}</td>
          <td style="color:var(--success);font-weight:700;">${g.correct}</td>
          <td style="color:#ef4444;font-weight:700;">${g.incorrect}</td>
          <td style="font-weight:700;">${g.marks_obtained} <span style="color:var(--muted);font-weight:400;">/ ${g.total_marks}</span></td>
          <td><div class="pct-bar-wrap"><div class="pct-bar-bg"><div class="pct-bar-fill" style="width:${pct}%;background:${barColor(pct)};"></div></div><span class="pct-val" style="color:${barColor(pct)}">${pct.toFixed(1)}%</span></div></td>
        </tr>`;
      });
      const ovPct = parseFloat(overall.percentage||0);
      html += `<tr class="total-row">
        <td>Total</td><td>${overall.total_questions??0}</td><td>${overall.attempted??0}</td><td>${overall.left??0}</td>
        <td style="color:var(--success);">${overall.correct??0}</td><td style="color:#ef4444;">${overall.incorrect??0}</td>
        <td>${overall.marks_obtained??0} <span style="color:var(--muted);font-weight:400;">/ ${overall.total_marks??0}</span></td>
        <td><div class="pct-bar-wrap"><div class="pct-bar-bg"><div class="pct-bar-fill" style="width:${ovPct}%;background:${barColor(ovPct)};"></div></div><span class="pct-val" style="color:${barColor(ovPct)}">${ovPct.toFixed(1)}%</span></div></td>
      </tr>`;
      html += `</tbody></table></div></div>`;
    });

    container.innerHTML = html;
    container.querySelectorAll('.attempt-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.idx);
        container.querySelectorAll('.attempt-tab-btn').forEach(b => b.classList.remove('active'));
        container.querySelectorAll('.exam-attempt-pane').forEach(p => p.style.display='none');
        btn.classList.add('active');
        container.querySelector(`.exam-attempt-pane[data-idx="${idx}"]`).style.display='';
      });
    });
  }

  /* ══ BACK BUTTON ═════════════════════════════════════════ */
  document.getElementById('btnBackToLeads').addEventListener('click', () => {
    history.back();
  });

  /* ══ BOOT ════════════════════════════════════════════════ */
  await loadStudent();

} // end boot()
</script>
@endpush