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
  /* ── Queue Layout ───────────────────────────── */
.fl-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.queue-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.lead-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow);
  padding: 18px;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.lead-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.lead-card-top {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.lead-card-avatar {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: var(--brand-light);
  border: 1px solid rgba(201,75,80,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--brand);
  font-weight: 800;
  font-size: 1rem;
  flex-shrink: 0;
}
.lead-card-name {
  font-size: 15px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 4px;
}
.lead-card-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12.5px;
  color: var(--muted);
}
.lead-mini-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.lead-mini-item {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px;
}
.lead-mini-item label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--muted);
  margin-bottom: 4px;
}
.lead-mini-item div {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  word-break: break-word;
}
.lead-card-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}
.btn-view-profile,
.btn-back,
.btn-next {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 9px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
}
.btn-view-profile {
  border: 1px solid rgba(201,75,80,.22);
  background: var(--brand-light);
  color: var(--brand-dark);
  width: 100%;
}
.btn-view-profile:hover {
  background: #fde8e8;
}
.detail-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.detail-toolbar-left,
.detail-toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.btn-back {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text);
}
.btn-back:hover {
  background: var(--surface-2);
}
.btn-next {
  border: 1px solid rgba(79,70,229,.22);
  background: var(--info-light);
  color: var(--info);
}
.btn-next:hover:not(:disabled) {
  background: #e0e7ff;
}
.btn-next:disabled {
  opacity: .45;
  cursor: not-allowed;
}
.detail-hint {
  font-size: 12px;
  color: var(--muted);
}
.d-none-force {
  display: none !important;
}

@media (max-width: 640px) {
  .lead-mini-grid {
    grid-template-columns: 1fr;
  }
  .detail-toolbar {
    align-items: stretch;
  }
  .detail-toolbar-left,
  .detail-toolbar-right {
    width: 100%;
  }
  .detail-toolbar-left > *,
  .detail-toolbar-right > * {
    flex: 1;
  }
}
</style>
@endpush
@section('content')
<div class="container-fluid fl-wrap">

  <div class="fl-header">
    <div class="fl-eyebrow"><i class="fa fa-bolt"></i> Live Queue</div>
    <h1 class="fl-title">Fresh Leads</h1>
    <p class="fl-sub">View all unassigned leads, open one profile, assign, and move to the next without losing the queue.</p>
  </div>

  <div class="fl-stats">
    <div class="stat-pill"><span class="dot" style="background:var(--brand)"></span><strong id="statTotal">0</strong> Total</div>
    <div class="stat-pill"><span class="dot" style="background:var(--warn)"></span><strong id="statUnassigned">0</strong> Unassigned</div>
    <div class="stat-pill"><span class="dot" style="background:var(--success)"></span><strong id="statAssigned">0</strong> Assigned to me</div>
  </div>

  <div class="fl-body">

    {{-- Queue View --}}
    <div id="queueView">
      <div id="queueState" class="fl-state">
        <div class="state-icon"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i></div>
        <p class="state-title">Loading queue</p>
        <p class="mt-1" style="font-size:13px">Fetching all fresh unassigned leads…</p>
      </div>

      <div id="leadQueue" class="queue-grid d-none"></div>
    </div>

    {{-- Detail View --}}
    <div id="detailView" class="d-none">
      <div class="detail-toolbar mb-3">
        <div class="detail-toolbar-left">
          <button class="btn-back" id="btnBackToQueue" type="button">
            <i class="fa fa-arrow-left"></i> Back to Queue
          </button>
          <div class="detail-hint" id="detailHint">Viewing selected lead</div>
        </div>
        <div class="detail-toolbar-right">
          <button class="btn-next" id="btnNextLead" type="button">
            Next lead <i class="fa fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <div id="focusWrap">
        <div id="focusCard" class="focus-card locked">

          <div class="focus-head">
            <div class="focus-left">
              <div class="focus-avatar" id="focusAvatar">—</div>
              <div style="min-width:0">
                <p class="focus-name" id="focusName">—</p>
                <div class="focus-meta">
                  <span id="focusStatusBadge" class="badge-unassigned">
                    <i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned
                  </span>
                  <span><i class="fa-regular fa-envelope" style="opacity:.6"></i> <span id="focusEmail">—</span></span>
                  <span><i class="fa fa-phone" style="opacity:.6"></i> <span id="focusPhone">—</span></span>
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

          <div class="sp-tab-nav">
            <button class="sp-tab-btn active" data-tab="tabProfile"><i class="fa fa-address-card"></i> Profile</button>
            <button class="sp-tab-btn" data-tab="tabExams"><i class="fa fa-file-alt"></i> Exams</button>
            <button class="sp-tab-btn" data-tab="tabActivity"><i class="fa fa-chart-line"></i> Activity</button>
            <button class="sp-tab-btn" data-tab="tabComms"><i class="fa fa-comments"></i> Communications</button>
          </div>

          <div class="sp-tab-body">

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
                <i class="fa fa-lock me-2"></i>
                <strong>Locked.</strong> Assign this lead to yourself to view full details.
              </div>
            </div>

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

          </div>
        </div>
      </div>
    </div>

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
  const FRESH_LEADS_URL   = '/api/students/fresh-leads';
  const ASSIGN_URL        = (myId, uuid) => `/api/counsellors/${myId}/students/${uuid}/assign`;
  const AUTH_CHECK_URL    = '/api/auth/check';
  const MY_ASSIGNMENTS_URL = '/api/my-assignments?role=academic_counsellor';

  const USER_QUIZZES_URL  = (id) => `/api/users/${id}/quizzes`;
  const QUIZ_ASSIGN_URL   = (id) => `/api/users/${id}/quizzes/assign`;
  const QUIZ_UNASSIGN_URL = (id) => `/api/users/${id}/quizzes/unassign`;

  const ACT_LIMIT = 15;
  const ACTIVITY_LOGS_URL = (studentId, page, module, activity) => {
    const p = new URLSearchParams({ student_id: studentId, limit: ACT_LIMIT, page, sort: 'desc' });
    if (module) p.set('module', module);
    if (activity) p.set('activity', activity);
    return `/api/activity-logs?${p}`;
  };

  const TOKEN = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
  if (!TOKEN) {
    await Swal.fire({ icon: 'warning', title: 'Session Expired', text: 'Please log in again.' });
    location.href = '/';
    return;
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
  const CAN_ASSIGN = ROLE === 'academic_counsellor';

  const queueView      = document.getElementById('queueView');
  const detailView     = document.getElementById('detailView');
  const queueState     = document.getElementById('queueState');
  const leadQueue      = document.getElementById('leadQueue');
  const btnBackToQueue = document.getElementById('btnBackToQueue');
  const btnNextLead    = document.getElementById('btnNextLead');
  const detailHint     = document.getElementById('detailHint');

  const focusWrap       = document.getElementById('focusWrap');
  const focusCard       = document.getElementById('focusCard');
  const btnAssignToMe   = document.getElementById('btnAssignToMe');
  const btnAssignQuiz   = document.getElementById('btnAssignQuiz');
  const assignQuizModal = new bootstrap.Modal(document.getElementById('assignQuizModal'));

  const actFeed        = document.getElementById('activityFeed');
  const actPlaceholder = document.getElementById('actPlaceholder');
  const actPagination  = document.getElementById('actPagination');
  const actPrevBtn     = document.getElementById('actPrevBtn');
  const actNextBtn     = document.getElementById('actNextBtn');
  const actPagInfo     = document.getElementById('actPaginationInfo');
  const actModFilter   = document.getElementById('actModuleFilter');
  const actTypeFilter  = document.getElementById('actTypeFilter');

  const examAccordion   = document.getElementById('examAccordion');
  const examsPlaceholder = document.getElementById('examsPlaceholder');

  let MY_ID = '';
  let queue = [];
  let focused = null;
  let currentIndex = -1;
  let assignedMineCount = 0;

  let uqData = [];
  let uqUserId = null;

  let actPage = 1;
  let actTotal = 0;
  let actModsLoaded = false;

  const examResultCache = {};

  function esc(s) {
    const m = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' };
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => m[c]);
  }

  function initials(n) {
    return (n || '?').trim().split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();
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
      if (id) {
        sessionStorage.setItem('user_id', String(id));
        return String(id);
      }
    } catch (e) {}
    return '';
  }

  async function loadAssignedMineCount() {
    if (!CAN_ASSIGN) {
      assignedMineCount = 0;
      return;
    }

    try {
      const res = await fetch(MY_ASSIGNMENTS_URL, { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      const mine = Array.isArray(j?.data?.my_students) ? j.data.my_students : [];
      assignedMineCount = mine.length;
    } catch (e) {
      assignedMineCount = 0;
    }
  }

  function updateStats() {
    const unassigned = queue.length;
    const total = unassigned + assignedMineCount;

    document.getElementById('statTotal').textContent      = total;
    document.getElementById('statUnassigned').textContent = unassigned;
    document.getElementById('statAssigned').textContent   = assignedMineCount;
  }

  function showQueueView() {
    detailView.classList.add('d-none');
    queueView.classList.remove('d-none');
  }

  function showDetailView() {
    queueView.classList.add('d-none');
    detailView.classList.remove('d-none');
  }

  function renderQueue() {
    updateStats();

    if (!queue.length) {
      leadQueue.classList.add('d-none');
      queueState.classList.remove('d-none');
      queueState.innerHTML = `
        <div class="state-icon"><i class="fa fa-check-circle" style="color:var(--success)"></i></div>
        <p class="state-title">Queue Empty</p>
        <p class="mt-1" style="font-size:13px">No unassigned students are left in the fresh leads queue.</p>
      `;
      return;
    }

    queueState.classList.add('d-none');
    leadQueue.classList.remove('d-none');
    leadQueue.innerHTML = queue.map((lead, index) => {
      return `
        <div class="lead-card">
          <div class="lead-card-top">
            <div class="lead-card-avatar">${esc(initials(lead.name))}</div>
            <div style="min-width:0;">
              <p class="lead-card-name">${esc(val(lead.name))}</p>
              <div class="lead-card-meta">
                <span><i class="fa-regular fa-envelope" style="opacity:.6"></i> ${esc(val(lead.email))}</span>
                <span><i class="fa fa-phone" style="opacity:.6"></i> ${esc(val(lead.phone || lead.phone_number || lead.mobile_number))}</span>
              </div>
            </div>
          </div>

          <div>
            <span class="badge-unassigned">
              <i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned
            </span>
          </div>

          <div class="lead-mini-grid d-none">
            <div class="lead-mini-item">
              <label>Class</label>
              <div>${esc(val(lead.klass || lead.class_name || lead.enrolled_class))}</div>
            </div>
            <div class="lead-mini-item">
              <label>Exam Type</label>
              <div>${esc(val(lead.exam_type))}</div>
            </div>
          </div>

          <div class="lead-card-actions">
            <button type="button" class="btn-view-profile js-view-profile" data-index="${index}">
              <i class="fa fa-id-card"></i> View Profile
            </button>
          </div>
        </div>
      `;
    }).join('');

    leadQueue.querySelectorAll('.js-view-profile').forEach(btn => {
      btn.addEventListener('click', () => {
        const index = parseInt(btn.dataset.index, 10);
        openLeadAt(index);
      });
    });
  }

  function setField(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    const display = val(value);
    el.textContent = display;
    el.classList.toggle('empty', display === '—');
  }

  function setLocked(isLocked) {
    focusCard.classList.toggle('locked', !!isLocked);
    btnAssignQuiz.classList.toggle('d-none', !!isLocked);

    if (examAccordion) {
      examAccordion.innerHTML = '';
      examAccordion.classList.add('d-none');
    }
    if (examsPlaceholder) {
      examsPlaceholder.classList.remove('d-none');
      examsPlaceholder.innerHTML = `<i class="fa fa-file-circle-question mb-2" style="font-size:1.4rem;opacity:.4"></i><div>No exam results yet. Assign exams using the button above.</div>`;
    }

    actPage = 1;
    actTotal = 0;
    actModsLoaded = false;
    while (actModFilter.options.length > 1) actModFilter.remove(1);
    actModFilter.value = '';
    actTypeFilter.value = '';

    actFeed.classList.add('d-none');
    actPagination.classList.add('d-none');
    actPlaceholder.classList.remove('d-none');
    actPlaceholder.innerHTML = `<i class="fa fa-chart-line mb-2" style="font-size:1.4rem;opacity:.4"></i><div>Activity will appear here once loaded.</div>`;

    Object.keys(examResultCache).forEach(k => delete examResultCache[k]);
  }

  function renderFocus(lead) {
    focused = lead || null;

    if (!focused) {
      showQueueView();
      return;
    }

    showDetailView();

    document.getElementById('focusAvatar').textContent = initials(focused.name);
    document.getElementById('focusName').textContent   = focused.name || '—';
    document.getElementById('focusEmail').textContent  = focused.email || '—';
    document.getElementById('focusPhone').textContent  = focused.phone || focused.phone_number || focused.mobile_number || '—';

    setField('spName',        focused.name);
    setField('spEmail',       focused.email);
    setField('spMobile',      focused.phone || focused.phone_number || focused.mobile_number);
    setField('spWhatsapp',    focused.whatsapp || focused.whatsapp_number);
    setField('spAltEmail',    focused.alt_email || focused.alternative_email);
    setField('spGuardian',    focused.guardian_name || focused.parent_name);
    setField('spGuardianNum', focused.guardian_phone || focused.parent_phone);
    setField('spClass',       focused.klass || focused.class_name || focused.enrolled_class);
    setField('spBoard',       focused.education_board || focused.board);
    setField('spExamType',    focused.exam_type);
    setField('spPassout',     focused.passout_year || focused.passout);

    const assigned = !!focused.assignedTo;
    const badge    = document.getElementById('focusStatusBadge');
    badge.className = assigned ? 'badge-assigned' : 'badge-unassigned';
    badge.innerHTML = assigned
      ? `<i class="fa fa-circle" style="font-size:.45rem"></i> Assigned`
      : `<i class="fa fa-circle" style="font-size:.45rem"></i> Unassigned`;

    btnAssignToMe.disabled = assigned || !CAN_ASSIGN;
    btnAssignToMe.title = !CAN_ASSIGN
      ? 'Only Academic Counsellors can self-assign'
      : (assigned ? 'Already assigned' : '');

    detailHint.textContent = assigned
      ? 'Lead assigned to you. It has been removed from the unassigned queue.'
      : 'Lead is still unassigned. Assign to unlock the profile.';

    btnNextLead.disabled = queue.length === 0;

    document.querySelectorAll('.sp-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sp-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelector('.sp-tab-btn[data-tab="tabProfile"]').classList.add('active');
    document.getElementById('tabProfile').classList.add('active');

    setLocked(!assigned);
  }

  function openLeadAt(index) {
    if (index < 0 || index >= queue.length) return;
    currentIndex = index;
    renderFocus(queue[index]);
  }

  function removeLeadFromQueue(lead) {
    if (!lead) return;
    const leadKey = String(lead.uuid || lead.id || '');
    queue = queue.filter(item => String(item.uuid || item.id || '') !== leadKey);

    if (!queue.length) {
      currentIndex = -1;
    } else if (currentIndex >= queue.length) {
      currentIndex = queue.length - 1;
    }

    renderQueue();
  }

  function openNextLead() {
    if (!queue.length) {
      showQueueView();
      return;
    }

    let nextIndex = currentIndex + 1;
    if (nextIndex >= queue.length) nextIndex = 0;
    openLeadAt(nextIndex);
  }

  async function loadQueue() {
    queueState.classList.remove('d-none');
    leadQueue.classList.add('d-none');

    queueState.innerHTML = `
      <div class="state-icon"><i class="fa fa-circle-notch fa-spin" style="color:var(--brand)"></i></div>
      <p class="state-title">Loading queue</p>
      <p class="mt-1" style="font-size:13px">Fetching all fresh unassigned leads…</p>
    `;

    try {
      await loadAssignedMineCount();

      const res = await fetch(FRESH_LEADS_URL + '?per_page=200', { headers: hdrs() });
      const j   = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load fresh leads');

      queue = Array.isArray(j.data) ? j.data.slice() : [];
      currentIndex = -1;

      renderQueue();
      showQueueView();
    } catch (e) {
      queue = [];
      updateStats();

      queueState.classList.remove('d-none');
      leadQueue.classList.add('d-none');
      queueState.innerHTML = `
        <div class="state-icon"><i class="fa fa-triangle-exclamation" style="color:#dc2626"></i></div>
        <p class="state-title" style="color:#dc2626">Failed to Load</p>
        <p class="mt-1" style="font-size:13px">${esc(e.message || 'Server error')}</p>
      `;
    }
  }

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
            ${note ? `<div class="activity-note">${esc(note)}</div>` : ''}
            ${target ? `<div class="activity-note" style="color:var(--muted);font-size:11px;">Record: ${esc(String(target))}</div>` : ''}
            <div class="activity-meta">
              <span><i class="fa fa-clock"></i> ${timeStr}</span>
              ${row.ip ? `<span><i class="fa fa-network-wired"></i> ${esc(row.ip)}</span>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');

    const totalPages = Math.ceil(actTotal / ACT_LIMIT) || 1;
    actPagInfo.textContent = `Page ${actPage} of ${totalPages} (${actTotal} total)`;
    actPrevBtn.disabled = actPage <= 1;
    actNextBtn.disabled = actPage >= totalPages;
  }

  async function populateModuleDropdown(studentId) {
    try {
      const res = await fetch(`/api/activity-logs?student_id=${studentId}&limit=500`, { headers: hdrs() });
      const j = await res.json().catch(() => ({}));
      if (!j.ok) return;

      const modules = [...new Set((j.data || []).map(r => r.module).filter(Boolean))].sort();
      modules.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        actModFilter.appendChild(opt);
      });
    } catch (e) {}
  }

  async function loadActivityTab(resetPage = false) {
    const studentId = focused?.id ?? '';
    if (!studentId) return;
    if (resetPage) actPage = 1;

    const module   = actModFilter.value || '';
    const activity = actTypeFilter.value || '';

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

      if (!actModsLoaded) {
        actModsLoaded = true;
        populateModuleDropdown(studentId);
      }
    } catch (e) {
      actFeed.classList.add('d-none');
      actPagination.classList.add('d-none');
      actPlaceholder.classList.remove('d-none');
      actPlaceholder.innerHTML = `<i class="fa fa-triangle-exclamation" style="color:#dc2626;font-size:1.4rem;display:block;margin-bottom:6px;"></i><span style="color:#dc2626">${esc(e.message)}</span>`;
    }
  }

  actPrevBtn.addEventListener('click', () => { actPage--; loadActivityTab(); });
  actNextBtn.addEventListener('click', () => { actPage++; loadActivityTab(); });
  actModFilter.addEventListener('change', () => loadActivityTab(true));
  actTypeFilter.addEventListener('change', () => loadActivityTab(true));

  document.querySelectorAll('.sp-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.sp-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.sp-tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');

      const isLocked = focusCard.classList.contains('locked');
      if (btn.dataset.tab === 'tabActivity' && focused && !isLocked) loadActivityTab(true);
    });
  });

  btnBackToQueue.addEventListener('click', () => {
    renderQueue();
    showQueueView();
  });

  btnNextLead.addEventListener('click', () => {
    if (!queue.length) {
      showQueueView();
      return;
    }
    openNextLead();
  });

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
      const activeLead = focused;
      const res = await fetch(ASSIGN_URL(MY_ID, activeLead.uuid || activeLead.id), {
        method: 'POST',
        headers: hdrs({ 'Content-Type': 'application/json' }),
        body: JSON.stringify({}),
      });

      const j = await res.json().catch(() => ({}));

      if (res.status === 409) {
        removeLeadFromQueue(activeLead);
        await loadAssignedMineCount();
        updateStats();

        await Swal.fire({
          icon: 'info',
          title: 'Already assigned',
          text: j.message || 'This lead was already assigned to another counsellor.',
        });

        if (queue.length) openLeadAt(Math.max(0, Math.min(currentIndex, queue.length - 1)));
        else showQueueView();
        return;
      }

      if (!res.ok) throw new Error(j.message || 'Assignment failed');

      focused = { ...activeLead, assignedTo: 'Me' };
      assignedMineCount += 1;

      removeLeadFromQueue(activeLead);
      renderFocus(focused);
      updateStats();

      Swal.fire({
        icon: 'success',
        title: 'Assigned!',
        text: 'Student assigned successfully.',
        timer: 1400,
        showConfirmButton: false
      });

    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Failed', text: e.message || 'Could not assign.' });
    }
  });

  btnAssignQuiz.addEventListener('click', () => {
    if (!focused) return;
    openUserQuizzes(focused.id, focused.name);
  });

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
    } catch (e) {
      uqRows.innerHTML = `<tr><td colspan="8" class="text-danger text-center p-3">${esc(e.message)}</td></tr>`;
    } finally {
      uqLoader.style.display = 'none';
    }
  }

  function renderQuizRows() {
    uqRows.querySelectorAll('tr:not(#uq_loader)').forEach(tr => tr.remove());

    let list = uqData.slice();
    const q = uqSearch.value.trim().toLowerCase();
    const f = uqFilter.value;

    if (q) list = list.filter(x => (x.quiz_name || '').toLowerCase().includes(q));
    if (f === 'assigned') list = list.filter(x => !!x.assigned);
    if (f === 'unassigned') list = list.filter(x => !x.assigned);

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
        : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-uppercase" style="font-size:10px;">${esc(status || '—')}</span>`;

      const publicBadge = (isPublic === 'yes' || isPublic === 'public')
        ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">Yes</span>`
        : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px;">No</span>`;

      const codeHtml = code
        ? `<button type="button" class="btn btn-light btn-sm js-copy-code d-inline-flex align-items-center gap-1" style="border-radius:8px;font-size:11px;font-weight:700;" data-code="${esc(code)}" title="Copy code">
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
  }

  uqSearch.addEventListener('input', renderQuizRows);
  uqFilter.addEventListener('change', renderQuizRows);

  MY_ID = await getMyId();
  await loadQueue();
}
</script>
@endpush