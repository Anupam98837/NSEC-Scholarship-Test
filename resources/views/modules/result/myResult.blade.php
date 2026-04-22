{{-- resources/views/modules/student_results/myResults.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','My Results')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.sr-wrap{max-width:1140px;margin:16px auto 40px;overflow:visible}
.panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:14px}

/* Toolbar */
.mfa-toolbar .form-control{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .form-select{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .btn{height:40px;border-radius:12px}
.mfa-toolbar .btn-light{background:var(--surface);border:1px solid var(--line-strong)}
.mfa-toolbar .btn-primary{background:var(--primary-color);border:none}

/* Tabs */
.sr-tabbar{display:flex;flex-wrap:wrap;gap:8px}
.sr-tab{
  height:40px;display:inline-flex;align-items:center;gap:.5rem;
  padding:0 14px;border-radius:12px;border:1px solid var(--line-strong);
  background:var(--surface);color:var(--text-color);cursor:pointer;
  user-select:none;transition:transform .08s ease, box-shadow .08s ease;
}
.sr-tab:hover{transform:translateY(-1px);box-shadow:var(--shadow-1)}
.sr-tab i{opacity:.85}
.sr-tab.active{background:var(--primary-color);border-color:var(--primary-color);color:#fff}
.sr-tab:focus{outline:none;box-shadow:0 0 0 .25rem rgba(158,54,58,.25)}

/* Table Card */
.table-wrap.card{position:relative;border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
.table-wrap .card-body{overflow:hidden}
.table{--bs-table-bg:transparent}
.table thead th{font-weight:600;color:var(--muted-color);font-size:13px;border-bottom:1px solid var(--line-strong);background:var(--surface);white-space:nowrap}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
td{vertical-align:middle;white-space:nowrap}
.small{font-size:12.5px}

/* Responsive scroll */
.table-scrollwrap{position:relative}
.table-responsive{overflow-x:auto !important;overflow-y:visible !important}
.x-scrollbar{height:14px;overflow-x:auto;overflow-y:hidden;border-top:1px solid var(--line-strong);background:color-mix(in oklab,var(--muted-color) 6%,transparent)}
.x-scrollbar.hidden{display:none}
.x-scrollbar-inner{height:1px}
.table-responsive{overflow-x:auto !important;scrollbar-width:none;-ms-overflow-style:none}
.table-responsive::-webkit-scrollbar{height:0px}
.table-responsive::-webkit-scrollbar-thumb{background:transparent}

/* Badges */
.badge-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .55rem;border-radius:999px;border:1px solid var(--line-strong);background:color-mix(in oklab,var(--muted-color) 10%,transparent)}

/* Empty & loader */
.empty{color:var(--muted-color)}
.placeholder{background:linear-gradient(90deg,#00000010,#00000005,#00000010);border-radius:8px}

/* Dark */
html.theme-dark .panel,
html.theme-dark .table-wrap.card{background:#0f172a;border-color:var(--line-strong)}
html.theme-dark .table thead th{background:#0f172a;border-color:var(--line-strong);color:#94a3b8}
html.theme-dark .table tbody tr{border-color:var(--line-soft)}

/* =====================================================
   EMAIL GATE MODAL
   ===================================================== */
#srGateBackdrop{
  position:fixed;inset:0;z-index:9900;
  background:rgba(0,0,0,.6);backdrop-filter:blur(4px);
  display:flex;align-items:center;justify-content:center;padding:16px;
  animation:gFadeIn .2s ease;
}
@keyframes gFadeIn{from{opacity:0}to{opacity:1}}

#srGateModal{
  background:var(--surface,#fff);
  border:1px solid var(--line-strong,#e2e8f0);
  border-radius:22px;
  box-shadow:0 32px 80px rgba(0,0,0,.25);
  width:100%;max-width:460px;
  padding:32px 28px 28px;
  position:relative;
  animation:gSlideUp .25s cubic-bezier(.16,1,.3,1);
}
@keyframes gSlideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}

/* Step indicator */
.gate-steps{display:flex;gap:6px;margin-bottom:24px}
.gate-step{
  height:4px;flex:1;border-radius:99px;
  background:var(--line-strong,#e2e8f0);
  transition:background .3s ease;
}
.gate-step.done{background:var(--primary-color,#9e363a)}
.gate-step.active{background:var(--primary-color,#9e363a);opacity:.5}

.gate-close{
  position:absolute;top:14px;right:16px;
  background:none;border:none;font-size:18px;
  color:var(--muted-color,#94a3b8);cursor:pointer;
  line-height:1;padding:6px;border-radius:8px;
  transition:background .12s;
}
.gate-close:hover{background:var(--line-strong,#e2e8f0)}

.gate-icon{
  width:52px;height:52px;border-radius:14px;
  background:color-mix(in oklab,var(--primary-color,#9e363a) 12%,transparent);
  display:flex;align-items:center;justify-content:center;
  font-size:22px;color:var(--primary-color,#9e363a);
  margin-bottom:14px;
}
#srGateModal h5{font-size:17px;font-weight:700;margin:0 0 4px;color:var(--text-color,#0f172a)}
.gate-sub{font-size:13.5px;color:var(--muted-color,#64748b);margin-bottom:22px;line-height:1.5}

/* Field row */
.gate-row{display:flex;gap:8px;margin-bottom:12px}
.gate-row input{
  flex:1;height:44px;padding:0 14px;
  border:1.5px solid var(--line-strong,#e2e8f0);
  border-radius:12px;font-size:14px;
  background:var(--surface,#fff);color:var(--text-color,#0f172a);
  transition:border .15s,box-shadow .15s;
}
.gate-row input:focus{
  outline:none;
  border-color:var(--primary-color,#9e363a);
  box-shadow:0 0 0 3px color-mix(in oklab,var(--primary-color,#9e363a) 15%,transparent);
}
.gate-row input:disabled{opacity:.55;cursor:not-allowed;background:var(--line-soft,#f8fafc)}
.gate-row input.input-error{border-color:#ef4444}

/* Buttons */
.gate-btn{
  height:44px;padding:0 18px;border-radius:12px;border:none;
  font-size:13.5px;font-weight:600;cursor:pointer;white-space:nowrap;
  display:inline-flex;align-items:center;gap:6px;
  transition:opacity .15s,transform .1s,box-shadow .15s;
}
.gate-btn:active{transform:scale(.97)}
.gate-btn:disabled{opacity:.5;cursor:not-allowed;pointer-events:none}
.gate-btn-primary{background:var(--primary-color,#9e363a);color:#fff;box-shadow:0 2px 8px color-mix(in oklab,var(--primary-color,#9e363a) 35%,transparent)}
.gate-btn-primary:hover{box-shadow:0 4px 16px color-mix(in oklab,var(--primary-color,#9e363a) 45%,transparent)}
.gate-btn-outline{background:transparent;color:var(--primary-color,#9e363a);border:1.5px solid var(--primary-color,#9e363a)}
.gate-btn-full{width:100%;justify-content:center;height:46px;font-size:15px}

/* OTP input special */
#srOtpInput{
  letter-spacing:.2em;font-size:18px;font-weight:600;
  text-align:center;font-family:monospace;
}

/* Help texts */
.gate-help{font-size:12px;color:var(--muted-color,#94a3b8);margin-top:-6px;margin-bottom:12px;line-height:1.5}

/* Resend row */
.gate-resend{font-size:12.5px;color:var(--muted-color,#94a3b8);margin-bottom:14px}
#srResendBtn{
  background:none;border:none;padding:0;
  color:var(--primary-color,#9e363a);font-size:12.5px;
  cursor:pointer;text-decoration:underline;font-weight:600;
}
#srResendBtn:disabled{opacity:.45;cursor:not-allowed;pointer-events:none}

/* Verified badge */
.gate-verified{
  display:flex;align-items:center;gap:10px;
  background:color-mix(in oklab,#22c55e 10%,transparent);
  border:1px solid #bbf7d0;border-radius:12px;
  padding:12px 16px;margin-bottom:16px;
  animation:gFadeIn .3s ease;
}
.gate-verified i{color:#16a34a;font-size:18px}
.gate-verified span{font-size:13.5px;font-weight:600;color:#15803d}

/* Error */
.gate-error{
  font-size:13px;color:#dc2626;
  background:#fef2f2;border:1px solid #fecaca;
  border-radius:10px;padding:10px 14px;
  margin-bottom:12px;display:none;
  animation:gFadeIn .2s ease;
}

/* Divider */
.gate-divider{border:none;border-top:1px solid var(--line-strong,#e2e8f0);margin:16px 0}

/* Spinner */
.g-spin{
  display:inline-block;width:15px;height:15px;
  border:2px solid rgba(255,255,255,.35);border-top-color:#fff;
  border-radius:50%;animation:gSpin .6s linear infinite;
}
.gate-btn-outline .g-spin{border-color:color-mix(in oklab,var(--primary-color) 30%,transparent);border-top-color:var(--primary-color)}
@keyframes gSpin{to{transform:rotate(360deg)}}

/* Cooldown badge */
.gate-cooldown{
  display:inline-flex;align-items:center;gap:6px;
  font-size:12px;color:var(--muted-color,#64748b);
  background:var(--line-soft,#f1f5f9);
  border:1px solid var(--line-strong,#e2e8f0);
  border-radius:8px;padding:4px 10px;margin-left:6px;
}

/* Dark mode adjustments */
html.theme-dark #srGateModal{background:#0f172a;border-color:#1e293b}
html.theme-dark .gate-row input{background:#1e293b;border-color:#334155;color:#e2e8f0}
html.theme-dark .gate-row input:disabled{background:#0f172a}
html.theme-dark .gate-close:hover{background:#1e293b}
html.theme-dark .gate-error{background:#3b0a0a;border-color:#7f1d1d;color:#fca5a5}
</style>
@endpush

@section('content')
<div class="sr-wrap">

  {{-- Toolbar --}}
  <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
    <div class="col-12 d-flex align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <label class="text-muted small mb-0">Per page</label>
        <select id="per_page" class="form-select" style="width:96px;">
          <option>10</option>
          <option selected>20</option>
          <option>30</option>
          <option>50</option>
          <option>100</option>
        </select>
      </div>
      <div class="position-relative" style="min-width:320px;">
        <input id="q" type="text" class="form-control ps-5" placeholder="Search game / folder…">
        <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.6;"></i>
      </div>
      <button id="btnReset" class="btn btn-primary">
        <i class="fa fa-rotate-left me-1"></i>Reset
      </button>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="panel mb-2">
    <div class="sr-tabbar" id="srTabbar">
      <button type="button" class="sr-tab active" data-seen="not_seen"><i class="fa-regular fa-eye-slash"></i> Not Seen</button>
      <button type="button" class="sr-tab" data-seen="seen"><i class="fa-regular fa-eye"></i> Seen</button>
    </div>
  </div>

  {{-- Table --}}
  <div class="card table-wrap">
    <div class="card-body p-0">
      <div class="table-scrollwrap">
        <div class="table-responsive" id="tr-student">
          <table class="table table-hover table-borderless align-middle mb-0" id="tbl-student">
            <thead class="sticky-top">
              <tr>
                <th style="width:120px;">MODULE</th>
                <th>GAME / TEST</th>
                <th style="width:120px;">ATTEMPT</th>
                <th style="width:120px;">SCORE</th>
                <th style="width:170px;">SUBMITTED</th>
                <th class="text-end" style="width:140px;">ACTION</th>
              </tr>
            </thead>
            <tbody id="rows-student">
              <tr id="loaderRow-student" style="display:none;">
                <td colspan="6" class="p-0">
                  <div class="p-4">
                    <div class="placeholder-wave">
                      <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="x-scrollbar" id="xs-student"><div class="x-scrollbar-inner"></div></div>
      </div>

      <div id="empty-student" class="empty p-4 text-center" style="display:none;">
        <i class="fa fa-circle-info mb-2" style="font-size:32px;opacity:.6;"></i>
        <div>No published results found.</div>
      </div>

      <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
        <div class="text-muted small" id="metaTxt-student">—</div>
        <nav style="position:relative;z-index:1;">
          <ul id="pager-student" class="pagination mb-0"></ul>
        </nav>
      </div>
    </div>
  </div>

</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:10000">
  <div id="errToast" class="toast text-bg-danger border-0">
    <div class="d-flex">
      <div id="errMsg" class="toast-body">Something went wrong</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="successToast" class="toast text-bg-success border-0">
    <div class="d-flex">
      <div id="successMsg" class="toast-body">Done!</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  'use strict';

  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  if (!TOKEN){ location.href = '/'; return; }

  /* ── API endpoints ── */
  const API_MY_RESULTS   = '/api/student-results/my';
  const API_EMAIL_STATUS = '/api/my-email-status';
  const API_SEND_OTP     = '/api/student-results/send-email-otp';
  const API_VERIFY_OTP   = '/api/student-results/verify-email-otp';
  const API_SEND_RESULT  = '/api/student-results/send-result-email';

  /* ── DOM ── */
  const perPageSel = document.getElementById('per_page');
  const q          = document.getElementById('q');
  const btnReset   = document.getElementById('btnReset');
  const tabBtns    = Array.from(document.querySelectorAll('.sr-tab[data-seen]'));
  const rowsEl     = document.getElementById('rows-student');
  const loaderRow  = document.getElementById('loaderRow-student');
  const emptyEl    = document.getElementById('empty-student');
  const metaEl     = document.getElementById('metaTxt-student');
  const pagerEl    = document.getElementById('pager-student');
  const trWrap     = document.getElementById('tr-student');
  const xsWrap     = document.getElementById('xs-student');
  const tbl        = document.getElementById('tbl-student');

  /* ── Toasts ── */
  const errToast     = new bootstrap.Toast(document.getElementById('errToast'));
  const successToast = new bootstrap.Toast(document.getElementById('successToast'));
  const showErr  = m => { document.getElementById('errMsg').textContent     = m || 'Something went wrong'; errToast.show(); };
  const showSucc = m => { document.getElementById('successMsg').textContent = m || 'Done!'; successToast.show(); };

  /* ── State ── */
  const state = { page: 1, seenStatus: 'not_seen' };
  const cache = new Map();
  let aborter = null, reqSeq = 0;

  /* ── Email status cache (session-level, bust after verify) ── */
  let emailStatusCache = null;

  /* ── Helpers ── */
  const dtFmt = new Intl.DateTimeFormat(undefined,{ year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit' });

  function esc(s){
    if(s===null||s===undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }
  function fmtDate(iso){
    if(!iso) return '-';
    const d = new Date(iso);
    return isNaN(d) ? esc(iso) : dtFmt.format(d);
  }

  const modBadgeMap = {
    door_game:   `<span class="badge-pill"><i class="fa fa-door-open"></i> Door</span>`,
    quizz:       `<span class="badge-pill"><i class="fa fa-clipboard-question"></i> Quizz</span>`,
    bubble_game: `<span class="badge-pill"><i class="fa fa-circle"></i> Bubble</span>`,
    path_game:   `<span class="badge-pill"><i class="fa fa-route"></i> Path</span>`,
  };
  function moduleBadge(mod){
    const v = String(mod||'').toLowerCase();
    return modBadgeMap[v] || `<span class="badge-pill"><i class="fa fa-layer-group"></i> ${esc(mod||'-')}</span>`;
  }
  function viewUrlFor(item){
    const rid = item?.result?.uuid || '';
    const mod = String(item?.module||'').toLowerCase();
    if(!rid) return '#';
    if(mod==='door_game')   return `/decision-making-test/results/${encodeURIComponent(rid)}/view`;
    if(mod==='quizz')       return `/exam/results/${encodeURIComponent(rid)}/view`;
    if(mod==='bubble_game') return `/test/results/${encodeURIComponent(rid)}/view`;
    if(mod==='path_game')   return `/path-game/results/${encodeURIComponent(rid)}/view`;
    return '#';
  }

  async function authFetch(url, opts={}){
    return fetch(url, {
      ...opts,
      headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json', 'Content-Type':'application/json', ...(opts.headers||{}) }
    });
  }

  /* ================================================================
   | EMAIL GATE MODAL
   |================================================================ */

  async function fetchEmailStatus(force=false){
    if(emailStatusCache && !force) return emailStatusCache;
    const res  = await authFetch(API_EMAIL_STATUS);
    const json = await res.json().catch(()=>({}));
    emailStatusCache = json;
    return json;
  }

  /**
   * Main entry — called when "View Result" is clicked.
   * Checks email status then either redirects or shows modal.
   */
  async function handleViewResult(item){
    let status;
    try{
      status = await fetchEmailStatus();
    }catch(e){
      showErr('Unable to check email status. Please try again.');
      return;
    }

    const hasEmail   = status?.is_email          === 'yes';
    const isVerified = status?.is_email_verified === 'yes';
    const viewUrl    = viewUrlFor(item);

    // ✅ Already verified → direct redirect
    if(hasEmail && isVerified){
      window.location.href = viewUrl;
      return;
    }

    // ✅ Show gate modal
    openGateModal({
      existingEmail : hasEmail ? (status?.email || '') : '',
      viewUrl,
      item,
    });
  }

  /* ── Build & open modal ── */
  function openGateModal({ existingEmail, viewUrl, item }){
    document.getElementById('srGateBackdrop')?.remove();

    const hasExisting = !!existingEmail;
    const title       = item?.game?.title || 'Result';

    const tpl = `
      <div id="srGateBackdrop" role="dialog" aria-modal="true" aria-labelledby="srGateTitle">
        <div id="srGateModal">

          <button class="gate-close" id="srGateClose" aria-label="Close"><i class="fa fa-xmark"></i></button>

          <!-- Step indicators: step1=email, step2=otp, step3=done -->
          <div class="gate-steps">
            <div class="gate-step active" id="gStep1"></div>
            <div class="gate-step"        id="gStep2"></div>
            <div class="gate-step"        id="gStep3"></div>
          </div>

          <div class="gate-icon"><i class="fa fa-envelope-circle-check"></i></div>

          <h5 id="srGateTitle">Verify your email</h5>
          <p class="gate-sub">
            To receive your result for <strong>${esc(title)}</strong>,
            please verify your email address first.
          </p>

          <!-- ── STEP 1: Email + Send OTP ── -->
          <div id="gStepEmail">
            <div class="gate-row">
              <input
                type="email" id="srEmailInput"
                placeholder="Enter your email address"
                value="${esc(existingEmail)}"
                ${hasExisting ? 'readonly' : ''}
                autocomplete="email"
              />
              <button class="gate-btn gate-btn-primary" id="srSendOtpBtn">
                <i class="fa fa-paper-plane"></i> Send OTP
              </button>
            </div>
            ${hasExisting ? `<div class="gate-help"><i class="fa fa-lock" style="opacity:.6"></i> Your registered email is pre-filled.</div>` : ''}
            <div class="gate-error" id="srEmailError"></div>
          </div>

          <!-- ── STEP 2: OTP verify (hidden initially) ── -->
         <!-- ── STEP 2: OTP verify (hidden initially) ── -->
<div id="gStepOtp" style="display:none;">
  <div class="gate-row">
    <input type="text" id="srOtpInput" placeholder="Enter 6-digit OTP"
           maxlength="6" inputmode="numeric" autocomplete="one-time-code"/>
    <div class="gate-btn gate-btn-outline" style="pointer-events:none;opacity:.5;" id="srOtpStatus">
      <i class="fa fa-spinner fa-spin" id="srOtpSpinIcon" style="display:none;"></i>
      <i class="fa fa-key" id="srOtpKeyIcon"></i>
      Auto
    </div>
  </div>
  <div class="gate-help" id="srOtpHelp">
    <i class="fa fa-inbox" style="opacity:.6"></i>
    OTP sent to <strong id="srOtpEmailLabel"></strong> — check your inbox &amp; spam folder.
  </div>
  <div class="gate-resend">
    Didn't receive it?
    <button id="srResendBtn" disabled>
      Resend in <span id="srCountdown">—</span>s
    </button>
  </div>
  <div class="gate-error" id="srOtpError"></div>
</div>
          <!-- ── STEP 3: Verified + Get Result (hidden initially) ── -->
          <div id="gStepDone" style="display:none;">
            <div class="gate-verified">
              <i class="fa fa-circle-check"></i>
              <span>Email verified successfully!</span>
            </div>
            <div class="gate-error" id="srResultError"></div>
          </div>

          <hr class="gate-divider"/>

          <!-- Get Result button — always present, hidden until verified -->
          <button class="gate-btn gate-btn-primary gate-btn-full" id="srGetResultBtn" style="display:none;" disabled>
            <i class="fa fa-file-pdf"></i> Send Result to my Email
          </button>

        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', tpl);
    wireGateModal({ viewUrl, item });
  }

  /* ── Wire all modal interactions ── */
  function wireGateModal({ viewUrl, item }){
    const backdrop    = document.getElementById('srGateBackdrop');
    const closeBtn    = document.getElementById('srGateClose');

    // Step panels
    const stepEmail   = document.getElementById('gStepEmail');
    const stepOtp     = document.getElementById('gStepOtp');
    const stepDone    = document.getElementById('gStepDone');

    // Step indicators
    const gStep1      = document.getElementById('gStep1');
    const gStep2      = document.getElementById('gStep2');
    const gStep3      = document.getElementById('gStep3');

    // Inputs & buttons
    const emailInput  = document.getElementById('srEmailInput');
    const sendOtpBtn  = document.getElementById('srSendOtpBtn');
    const otpInput    = document.getElementById('srOtpInput');
    // const verifyBtn   = document.getElementById('srVerifyOtpBtn');
    const otpStatusEl  = document.getElementById('srOtpStatus');
    const otpSpinIcon  = document.getElementById('srOtpSpinIcon');
    const otpKeyIcon   = document.getElementById('srOtpKeyIcon');

    const resendBtn   = document.getElementById('srResendBtn');
    const countdownEl = document.getElementById('srCountdown');
    const otpEmailLbl = document.getElementById('srOtpEmailLabel');
    const getResultBtn= document.getElementById('srGetResultBtn');

    // Error els
    const emailErr    = document.getElementById('srEmailError');
    const otpErr      = document.getElementById('srOtpError');
    const resultErr   = document.getElementById('srResultError');

    /* helpers */
    function showError(el, msg){
      el.textContent = msg;
      el.style.display = msg ? '' : 'none';
    }
    function clearErrors(){ showError(emailErr,''); showError(otpErr,''); showError(resultErr,''); }

    function setLoading(btn, loading, idleHtml){
      btn.disabled = loading;
      btn.innerHTML = loading
        ? `<span class="g-spin"></span> ${btn.dataset.loadingText || 'Please wait…'}`
        : idleHtml;
    }

    /* close */
    function closeModal(){
      backdrop.style.animation='gFadeIn .15s ease reverse';
      setTimeout(()=> backdrop.remove(), 150);
      document.removeEventListener('keydown', onEsc);
    }
    function onEsc(e){ if(e.key==='Escape') closeModal(); }
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', e=>{ if(e.target===backdrop) closeModal(); });
    document.addEventListener('keydown', onEsc);

    /* step progress */
    function goStep(n){
      [gStep1,gStep2,gStep3].forEach((s,i)=>{
        s.classList.toggle('done',   i+1 <  n);
        s.classList.toggle('active', i+1 === n);
      });
    }

    /* countdown timer */
    let countTimer = null;
    function startCountdown(seconds){
      resendBtn.disabled = true;
      let left = seconds;
      countdownEl.textContent = left;
      clearInterval(countTimer);
      countTimer = setInterval(()=>{
        left--;
        if(left <= 0){
          clearInterval(countTimer);
          resendBtn.disabled = false;
          resendBtn.innerHTML = 'Resend OTP';
        } else {
          countdownEl.textContent = left;
          resendBtn.innerHTML = `Resend in <span id="srCountdown">${left}</span>s`;
        }
      },1000);
    }

    /* ── Send OTP ── */
    async function doSendOtp(){
      clearErrors();
      const email = emailInput.value.trim();
      if(!email || !/\S+@\S+\.\S+/.test(email)){
        showError(emailErr,'Please enter a valid email address.');
        emailInput.classList.add('input-error');
        emailInput.focus();
        return;
      }
      emailInput.classList.remove('input-error');

      sendOtpBtn.dataset.loadingText = 'Sending…';
      setLoading(sendOtpBtn, true, `<i class="fa fa-paper-plane"></i> Send OTP`);

      try{
        const res  = await authFetch(API_SEND_OTP,{ method:'POST', body:JSON.stringify({ email }) });
        const json = await res.json().catch(()=>({}));

       if(!res.ok || json?.success===false){
  showError(emailErr, json?.message || 'Failed to send OTP. Try again.');
  return;
}


        // ✅ OTP sent — show OTP step
        stepOtp.style.display = '';
        goStep(2);
        otpEmailLbl.textContent = email;
        otpInput.value = '';
        otpInput.focus();

        // Default: 2 min cooldown for 1st send
       startCountdown(120);
      }catch(e){
        showError(emailErr, e.message || 'Network error. Please try again.');
      }finally{
        setLoading(sendOtpBtn, false, `<i class="fa fa-paper-plane"></i> Send OTP`);
      }
    }

    sendOtpBtn.addEventListener('click', doSendOtp);

    resendBtn.addEventListener('click', ()=>{
      clearErrors();
      showError(otpErr,'');
      otpInput.value='';
      doSendOtp();
    });

    /* ── Verify OTP ── *//* ── Auto-verify when 6 digits entered ── */
    async function doVerifyOtp(){
      clearErrors();
      const email = emailInput.value.trim();
      const otp   = otpInput.value.trim();
      if(otp.length < 6) return;

      // Show spinner
      otpInput.disabled          = true;
      otpSpinIcon.style.display  = '';
      otpKeyIcon.style.display   = 'none';
      resendBtn.disabled         = true;

      try{
        const res  = await authFetch(API_VERIFY_OTP,{ method:'POST', body:JSON.stringify({ email, otp }) });
        const json = await res.json().catch(()=>({}));

        if(!res.ok || json?.success===false){
          showError(otpErr, json?.message || 'Incorrect OTP.');
          otpInput.value             = '';
          otpInput.disabled          = false;
          otpInput.focus();
          otpSpinIcon.style.display  = 'none';
          otpKeyIcon.style.display   = '';
          if(json?.expired){
            clearInterval(countTimer);
            resendBtn.disabled  = false;
            resendBtn.innerHTML = 'Resend OTP';
          } else {
            resendBtn.disabled = false;
          }
          return;
        }

        // ✅ Verified
        emailStatusCache = null;
        clearInterval(countTimer);
        stepOtp.style.display      = 'none';
        stepEmail.style.display    = 'none';
        stepDone.style.display     = '';
        getResultBtn.style.display = '';
        getResultBtn.disabled      = false;
        goStep(3);

      }catch(e){
        showError(otpErr, e.message || 'Network error. Please try again.');
        otpInput.disabled          = false;
        otpInput.value             = '';
        otpInput.focus();
        otpSpinIcon.style.display  = 'none';
        otpKeyIcon.style.display   = '';
        resendBtn.disabled         = false;
      }
    }

    // Fire when user finishes typing 6 digits
    otpInput.addEventListener('input', ()=>{
      otpInput.value = otpInput.value.replace(/\D/g, ''); // digits only
      if(otpInput.value.length === 6) doVerifyOtp();
    });

    /* ── Send Result Email ── */
   /* ── Send Result Link Email ── */
    getResultBtn.dataset.loadingText = 'Sending…';
    getResultBtn.addEventListener('click', async ()=>{
      clearErrors();

      setLoading(getResultBtn, true, `<i class="fa fa-link"></i> Send Result Link to my Email`);

      try{
        const res = await authFetch(API_SEND_RESULT,{
          method: 'POST',
          body: JSON.stringify({
            result_uuid : item?.result?.uuid,
            module      : item?.module,
            view_url    : viewUrl,
            email       : emailInput.value.trim(),
          })
        });
        const json = await res.json().catch(()=>({}));

        if(!res.ok || json?.success===false){
          showError(resultErr, json?.message || 'Failed to send. Please try again.');
          setLoading(getResultBtn, false, `<i class="fa fa-link"></i> Send Result Link to my Email`);
          return;
        }

        // ✅ Sent!
        getResultBtn.innerHTML        = '<i class="fa fa-circle-check"></i> Link sent! Check your inbox.';
        getResultBtn.style.background = '#16a34a';
        showSucc('Result link sent to your email!');
        setTimeout(()=> closeModal(), 2800);

      }catch(e){
        showError(resultErr, e.message || 'Network error. Please try again.');
        setLoading(getResultBtn, false, `<i class="fa fa-link"></i> Send Result Link to my Email`);
      }
    });
  
  }

  /* ================================================================
   | TABLE RENDERING
   |================================================================ */
  function renderRows(items){
    if(!items.length) return;
    const html = new Array(items.length);
    for(let i=0;i<items.length;i++){
      const item   = items[i];
      const mod    = item?.module || '-';
      const title  = item?.game?.title || '-';
      const result = item?.result || {};
      const attempt= Number(result.attempt_no || 0);
      const score  = Number(result.score || 0);
      const date   = fmtDate(result.result_created_at || result.created_at);
      const disabled = (!result.uuid) ? 'disabled' : '';

      // Store item as data attribute for the gate handler
      const itemJson = esc(JSON.stringify(item));

      html[i] = `
        <tr>
          <td>${moduleBadge(mod)}</td>
          <td><div class="fw-semibold">${esc(title)}</div></td>
          <td><span class="badge-pill"><i class="fa fa-repeat"></i> #${attempt}</span></td>
          <td><div class="fw-semibold">${score}</div></td>
          <td>${esc(date)}</td>
          <td class="text-end">
            <button
              class="btn btn-primary btn-sm sr-view-btn"
              data-item="${itemJson}"
              ${disabled}
            >
              <i class="fa fa-eye me-1"></i>View Result
            </button>
          </td>
        </tr>
      `;
    }
    rowsEl.insertAdjacentHTML('beforeend', html.join(''));
  }

  /* ── Delegate click for View Result buttons ── */
  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.sr-view-btn');
    if(!btn || btn.disabled || btn.hasAttribute('disabled')) return;
    e.preventDefault();

    let item = null;
    try{ item = JSON.parse(btn.dataset.item || 'null'); }catch(_){}
    if(!item) return;

    // Show loading state on button while checking
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

    try{
      await handleViewResult(item);
    }finally{
      btn.disabled = false;
      btn.innerHTML = orig;
    }
  });

  /* ================================================================
   | PAGINATION
   |================================================================ */
  function renderPagerTotal(page, totalPages){
    function li(disabled,active,label,target){
      const cls=['page-item',disabled?'disabled':'',active?'active':''].filter(Boolean).join(' ');
      return `<li class="${cls}"><a class="page-link" href="javascript:void(0)" data-page="${target||''}">${label}</a></li>`;
    }
    let html='';
    html += li(page<=1,false,'Previous',page-1);
    const w=3,start=Math.max(1,page-w),end=Math.min(totalPages,page+w);
    if(start>1){ html+=li(false,false,1,1); if(start>2) html+=`<li class="page-item disabled"><span class="page-link">…</span></li>`; }
    for(let p2=start;p2<=end;p2++) html+=li(false,p2===page,p2,p2);
    if(end<totalPages){ if(end<totalPages-1) html+=`<li class="page-item disabled"><span class="page-link">…</span></li>`; html+=li(false,false,totalPages,totalPages); }
    html+=li(page>=totalPages,false,'Next',page+1);
    pagerEl.innerHTML=html;
  }
  function renderPagerHasMore(page,hasMore){
    pagerEl.innerHTML=`
      <li class="page-item ${page<=1?'disabled':''}"><a class="page-link" href="javascript:void(0)" data-page="${page-1}">Previous</a></li>
      <li class="page-item ${!hasMore?'disabled':''}"><a class="page-link" href="javascript:void(0)" data-page="${page+1}">Next</a></li>
    `;
  }
  pagerEl.addEventListener('click',e=>{
    const a=e.target.closest('a.page-link[data-page]');
    if(!a) return;
    const target=Number(a.dataset.page);
    if(!target||target===state.page||target<1) return;
    state.page=target;
    load(true);
    window.scrollTo({top:0,behavior:'smooth'});
  });

  /* ================================================================
   | LOAD / PAINT
   |================================================================ */
  function buildKey(){ return ['p='+state.page,'pp='+Number(perPageSel.value||20),'s='+(state.seenStatus||''),'q='+(q.value||'').trim()].join('&'); }
  function buildUrl(){
    const usp=new URLSearchParams();
    usp.set('page',state.page);
    usp.set('per_page',Number(perPageSel.value||20));
    const qq=(q.value||'').trim(); if(qq) usp.set('q',qq);
    const seenStatus=String(state.seenStatus||'').trim(); if(seenStatus) usp.set('seen_status',seenStatus);
    return `${API_MY_RESULTS}?${usp.toString()}`;
  }
  function showLoader(v){ loaderRow.style.display=v?'':'none'; }
  function clearRowsExceptLoader(){ rowsEl.querySelectorAll('tr:not(#loaderRow-student)').forEach(n=>n.remove()); }
  function invalidateCache(){ cache.clear(); }

  let scrollBound=false;
  function updateXScroll(){
    if(!trWrap||!xsWrap||!tbl) return;
    const inner=xsWrap.querySelector('.x-scrollbar-inner');
    const need=tbl.scrollWidth>trWrap.clientWidth+2;
    xsWrap.classList.toggle('hidden',!need);
    if(!need) return;
    inner.style.width=tbl.scrollWidth+'px';
  }
  function bindXScrollOnce(){
    if(scrollBound) return; scrollBound=true;
    let lock=false;
    trWrap.addEventListener('scroll',()=>{ if(lock)return;lock=true;xsWrap.scrollLeft=trWrap.scrollLeft;lock=false; });
    xsWrap.addEventListener('scroll',()=>{ if(lock)return;lock=true;trWrap.scrollLeft=xsWrap.scrollLeft;lock=false; });
    const ro=new ResizeObserver(()=>updateXScroll());
    ro.observe(trWrap);ro.observe(tbl);
  }

  async function load(fromUserAction=false){
    emptyEl.style.display='none';
    metaEl.textContent='—';
    clearRowsExceptLoader();
    showLoader(true);
    if(aborter) aborter.abort();
    aborter=new AbortController();
    const mySeq=++reqSeq;
    const key=buildKey();
    const url=buildUrl();
    if(cache.has(key)){ showLoader(false); paint(cache.get(key)); return; }
    try{
      const res=await fetch(url,{ method:'GET', headers:{ 'Authorization':'Bearer '+TOKEN,'Accept':'application/json' }, signal:aborter.signal });
      if(mySeq!==reqSeq) return;
      const json=await res.json().catch(()=>({}));
      if(!res.ok||json?.success===false) throw new Error(json?.message||'Failed to load');
      cache.set(key,json);
      paint(json);
    }catch(e){
      if(e.name==='AbortError') return;
      emptyEl.style.display='';
      metaEl.textContent='Failed to load';
      showErr(e.message||'Load failed');
    }finally{
      if(mySeq===reqSeq) showLoader(false);
    }
  }

  function paint(json){
    const items=Array.isArray(json?.data)?json.data:[];
    const p=json?.pagination||{};
    const page=Number(p.page??state.page??1);
    const per=Number(p.per_page??perPageSel.value??20);
    if(!items.length){ emptyEl.style.display=''; } else { renderRows(items); }
    if(p.total_pages!==undefined||p.total!==undefined){
      const total=Number(p.total??items.length??0);
      const totalPages=Number(p.total_pages??Math.max(1,Math.ceil(total/per)));
      renderPagerTotal(page,totalPages);
      metaEl.textContent=`Showing page ${page} of ${totalPages} — ${total} result(s)`;
    } else {
      const hasMore=!!p.has_more;
      renderPagerHasMore(page,hasMore);
      metaEl.textContent=`Showing page ${page}${hasMore?' (more available)':''}`;
    }
    bindXScrollOnce();
    updateXScroll();
  }

  /* ── Tab / search / per-page / reset wiring ── */
  function setActiveTab(seenStatus){ tabBtns.forEach(btn=>btn.classList.toggle('active',String(btn.dataset.seen??'')===String(seenStatus))); }

  tabBtns.forEach(btn=>{
    btn.addEventListener('click',()=>{
      const seenStatus=btn.dataset.seen??'';
      if(String(seenStatus)===String(state.seenStatus)) return;
      state.seenStatus=String(seenStatus); state.page=1;
      invalidateCache(); setActiveTab(state.seenStatus); load(true);
      window.scrollTo({top:0,behavior:'smooth'});
    });
  });
  let tmr;
  q.addEventListener('input',()=>{ clearTimeout(tmr); tmr=setTimeout(()=>{ state.page=1; invalidateCache(); load(true); },450); });
  perPageSel.addEventListener('change',()=>{ state.page=1; invalidateCache(); load(true); });
  btnReset.addEventListener('click',()=>{
    q.value=''; perPageSel.value='20'; state.page=1; state.seenStatus='not_seen';
    invalidateCache(); setActiveTab('not_seen'); load(true);
  });

  /* ── Init ── */
  setActiveTab('not_seen');
  load(false);

})();
</script>
@endpush
