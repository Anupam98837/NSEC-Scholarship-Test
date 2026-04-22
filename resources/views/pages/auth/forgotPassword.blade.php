{{-- resources/views/auth/forgot-password.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Forgot Password — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    html, body { height:100%; }
    body.ux-auth-body{
      height:100%;
      overflow-x:hidden;
      overflow-x:visible;
      background:var(--bg-body);
      color:var(--text-color);
      font-family:var(--font-sans);
    }
    .ux-grid{
      height:100vh; height:100svh; height:100dvh;
      min-height:100vh; min-height:100svh; min-height:100dvh;
      display:grid; grid-template-columns:1fr; width:100%;
    }
    .ux-left{ min-width:0; }
    .ux-left{
      height:100vh; height:100svh; height:100dvh;
      width:100%; max-width:760px; justify-self:center;
      display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
      padding:clamp(18px,5vw,56px); position:relative; isolation:isolate;
      overflow:auto; overscroll-behavior:contain; -webkit-overflow-scrolling:touch;
      scrollbar-width:none; -ms-overflow-style:none;
    }
    .ux-left::-webkit-scrollbar{ width:0; height:0; }
    .ux-brand{ margin-top:auto; }
    .ux-form-shell{ margin-bottom:auto; width:100%; display:flex; justify-content:center; }
    .ux-left::before, .ux-left::after{
      content:""; position:absolute; z-index:0; pointer-events:none;
      border-radius:50%; filter:blur(26px); opacity:.25; display:block;
    }
    .ux-left::before{
      width:320px; height:320px; left:-80px; top:10%;
      background: radial-gradient(closest-side, #facc15, transparent 70%);
      animation: ux-floatA 9s ease-in-out infinite;
    }
    .ux-left::after{
      width:280px; height:280px; right:-60px; bottom:14%;
      background: radial-gradient(closest-side, var(--accent-color), transparent 70%);
      animation: ux-floatB 11s ease-in-out infinite;
    }
    .ux-brand{ display:grid; place-items:center; margin-bottom:18px; position:relative; z-index:1; max-width:100%; }
    .ux-brand img{ height:103px; max-width:100%; object-fit:contain; }
    .ux-title{
      font-family:var(--font-head); font-weight:700; color:var(--ink); text-align:center;
      font-size:clamp(1.6rem, 2.6vw, 2.2rem); margin:.35rem 0 .25rem;
      position:relative; z-index:1; max-width:min(560px, 100%);
    }
    .ux-sub{ text-align:center; color:var(--muted-color); margin-bottom:18px; position:relative; z-index:1; max-width:min(560px, 100%); }
    .ux-card{
      position:relative; z-index:1; background:var(--surface);
      border:1px solid var(--line-strong); border-radius:18px; padding:24px;
      box-shadow:var(--shadow-2); width:100%; max-width:min(430px, 100%); overflow:hidden;
    }
    .ux-card::before, .ux-card::after{
      content:""; position:absolute; border-radius:50%; filter:blur(18px); opacity:.25; pointer-events:none;
    }
    .ux-card::before{
      width:160px; height:160px; left:-40px; top:-40px;
      background: radial-gradient(closest-side, var(--accent-color), transparent 65%);
      animation: ux-orbitA 12s linear infinite;
    }
    .ux-card::after{
      width:140px; height:140px; right:-30px; bottom:-30px;
      background: radial-gradient(closest-side, var(--primary-color), transparent 65%);
      animation: ux-orbitB 14s linear infinite reverse;
    }
    .ux-label{ font-weight:600; color:var(--ink); }
    .ux-input-wrap{ position:relative; }
    .ux-control{ height:46px; border-radius:12px; max-width:100%; }
    .ux-control::placeholder{ color:#aab2c2; }
    .ux-login{
      width:100%; height:48px; border:none; border-radius:12px; font-weight:700; color:#fff;
      background:linear-gradient(180deg, color-mix(in oklab, var(--primary-color) 92%, #fff 8%), var(--primary-color));
      box-shadow:0 10px 22px rgba(20,184,166,.26); transition:var(--transition);
    }
    .ux-login:hover:not(:disabled){ filter:brightness(.98); transform:translateY(-1px); }
    .ux-login:disabled{ opacity:.65; cursor:not-allowed; transform:none; }
    .ux-secondary{
      width:100%; height:46px; border-radius:12px; border:1px solid var(--line-strong);
      background:transparent; font-weight:700; color:var(--ink); transition:var(--transition);
    }
    .ux-secondary:hover{ background: rgba(255,255,255,.04); }
    .ux-notice{
      display:flex; align-items:flex-start; gap:10px;
      background: rgba(99,102,241,.07); border:1px solid rgba(99,102,241,.2);
      border-radius:12px; padding:11px 14px; font-size:.85rem; color:var(--muted-color);
      margin-bottom:18px; position:relative; z-index:1;
    }
    .ux-notice i{ margin-top:2px; color:var(--primary-color); flex-shrink:0; }
    .ux-success-state{ text-align:center; padding:6px 0 2px; position:relative; z-index:1; }
    .ux-success-icon{ font-size:2.7rem; color:#27ae60; margin-bottom:10px; display:block; }
    .ux-success-state p{ color:var(--muted-color); font-size:.9rem; margin-bottom:0; }
    .ux-muted-note{ font-size:.82rem; color:var(--muted-color); }

    /* OTP input row */
    .ux-otp-row{ display:flex; gap:8px; justify-content:center; margin-bottom:4px; }
    .ux-otp-box{
      width:46px; height:54px; text-align:center; font-size:1.35rem; font-weight:700;
      border-radius:10px; border:1.5px solid var(--line-strong); background:var(--surface);
      color:var(--ink); outline:none; transition:border-color .18s, box-shadow .18s;
      caret-color: var(--primary-color);
    }
    .ux-otp-box:focus{ border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(20,184,166,.15); }
    .ux-otp-box.ux-filled{ border-color:var(--primary-color); }

    @media (max-height: 820px){ .ux-brand img{ height:103px; } .ux-sub{ margin-bottom:12px; } .ux-card{ padding:20px; } }
    @media (max-height: 760px){ .ux-brand{ margin-bottom:12px; } .ux-sub{ margin-bottom:12px; } .ux-card{ padding:18px; } }
    @media (max-height: 680px){ .ux-brand img{ height:56px; } .ux-title{ font-size:1.45rem; } .ux-card{ padding:16px; } .ux-control{ height:44px; } .ux-login{ height:46px; } .ux-secondary{ height:44px; } }
    @media (max-height: 600px){ .ux-title{ font-size:1.3rem; } .ux-sub{ font-size:.9rem; } .ux-card{ border-radius:14px; } }
    @media (max-width: 576px){
      .ux-left{ padding:16px; } .ux-brand img{ height:60px; }
      .ux-card{ padding:18px; border-radius:16px; } .ux-control{ height:44px; }
      .ux-login{ height:46px; } .ux-secondary{ height:44px; }
      .ux-otp-box{ width:40px; height:50px; font-size:1.2rem; }
    }

    @keyframes ux-floatA{ 0%,100%{ transform:translate(0,0); } 50%{ transform:translate(10px,-14px); } }
    @keyframes ux-floatB{ 0%,100%{ transform:translate(0,0); } 50%{ transform:translate(-12px,10px); } }
    @keyframes ux-orbitA{ 0%{ transform:translate(0,0); } 50%{ transform:translate(6px,-6px); } 100%{ transform:translate(0,0); } }
    @keyframes ux-orbitB{ 0%{ transform:translate(0,0); } 50%{ transform:translate(-6px,6px); } 100%{ transform:translate(0,0); } }
    @keyframes ux-chip{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }
  </style>
</head>

<body class="ux-auth-body">
<div class="ux-grid">
  <section class="ux-left">

    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title" id="fp_title">Forgot your password?</h1>
    <p class="ux-sub" id="fp_sub">Enter your email or mobile number and we'll send you an OTP.</p>

    <div class="ux-form-shell">
      <div class="ux-card">

        {{-- Alert --}}
        <div id="fp_alert" class="alert d-none mb-3" role="alert"></div>

        {{-- ══════════════════════════════════════
             STEP 1 — Email or phone input
        ══════════════════════════════════════ --}}
        <form id="fp_form" action="javascript:void(0)" method="post" novalidate>
          @csrf

          <div class="ux-notice">
            <i class="fa-solid fa-circle-info fa-sm"></i>
            <span>
              Enter your registered email address or mobile number. A <strong>6-digit OTP</strong>
              valid for <strong>10 minutes</strong> will be sent to your mobile and email.
            </span>
          </div>

          <div class="mb-4">
            <label class="ux-label form-label" for="fp_identifier">Email or mobile number</label>
            <div class="ux-input-wrap">
              <input
                id="fp_identifier"
                type="text"
                class="ux-control form-control"
                placeholder="you@example.com or +91XXXXXXXXXX"
                autocomplete="email"
                required
                autofocus
              >
            </div>
          </div>

          <button class="ux-login" id="fp_btn" type="submit">
            <span class="me-2"><i class="fa-solid fa-paper-plane"></i></span>
            Send OTP
          </button>

          <button class="ux-secondary mt-2" type="button" id="fp_back">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Login
          </button>
        </form>

        {{-- ══════════════════════════════════════
             STEP 2 — OTP verification
        ══════════════════════════════════════ --}}
        <div id="fp_otp_state" class="d-none">

          <div class="ux-notice">
            <i class="fa-solid fa-mobile-screen-button fa-sm"></i>
            <span>
              An OTP has been sent to the mobile number and email linked to your account.
              Enter it below — valid for <strong>10 minutes</strong>.
            </span>
          </div>

          {{-- 6-box OTP input --}}
          <div class="ux-otp-row mb-1" id="fp_otp_row">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="0" autocomplete="off">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="1" autocomplete="off">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="2" autocomplete="off">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="3" autocomplete="off">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="4" autocomplete="off">
            <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="5" autocomplete="off">
          </div>

          <p class="ux-muted-note text-center mb-3">
            Didn't receive it?
            <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="fp_resend_btn">Resend OTP</button>
            <span id="fp_resend_timer" class="ms-1"></span>
          </p>

          <button class="ux-login" id="fp_otp_btn" type="button">
            <span class="me-2"><i class="fa-solid fa-shield-halved"></i></span>
            Verify OTP
          </button>

          <button class="ux-secondary mt-2" type="button" id="fp_otp_back">
            <i class="fa-solid fa-arrow-left me-2"></i> Change Email / Phone
          </button>
        </div>

        {{-- ══════════════════════════════════════
             STEP 3 — New password
        ══════════════════════════════════════ --}}
        <div id="fp_reset_state" class="d-none">

          <div class="ux-notice">
            <i class="fa-solid fa-lock fa-sm"></i>
            <span>OTP verified. Set your new password below.</span>
          </div>

          <div class="mb-3">
            <label class="ux-label form-label" for="fp_password">New password</label>
            <input
              id="fp_password"
              type="password"
              class="ux-control form-control"
              placeholder="Minimum 8 characters"
              autocomplete="new-password"
            >
          </div>

          <div class="mb-4">
            <label class="ux-label form-label" for="fp_password_confirm">Confirm new password</label>
            <input
              id="fp_password_confirm"
              type="password"
              class="ux-control form-control"
              placeholder="Repeat your new password"
              autocomplete="new-password"
            >
          </div>

          <button class="ux-login" id="fp_reset_btn" type="button">
            <span class="me-2"><i class="fa-solid fa-key"></i></span>
            Reset Password
          </button>

          <button class="ux-secondary mt-2" type="button" id="fp_reset_back">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
          </button>
        </div>

        {{-- ══════════════════════════════════════
             STEP 4 — Done
        ══════════════════════════════════════ --}}
        <div id="fp_success_state" class="ux-success-state d-none">
          <i class="fa-solid fa-circle-check ux-success-icon"></i>
          <h6 class="fw-bold mb-2">Password updated!</h6>
          <p>
            Your password has been successfully reset.<br>
            You can now log in with your new password.
          </p>
          <hr class="my-3">
          <button type="button" class="ux-login" id="fp_go_login">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Go to Login
          </button>
        </div>

      </div>
    </div>

  </section>
</div>

<script>
(function () {
  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Config
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  const SEND_OTP_API    = '/api/auth/forgot-password/send-otp';
  const RESET_API       = '/api/auth/forgot-password/reset';
  const LOGIN_PAGE      = '/login-passhallienz';
  // const RESEND_COOLDOWN = 60; // seconds

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | State
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  let currentEmail   = ''; // always stores resolved email after step 1
  let verifiedOtp    = '';
  let resendInterval = null;

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | DOM refs
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  const title          = document.getElementById('fp_title');
  const sub            = document.getElementById('fp_sub');
  const alertEl        = document.getElementById('fp_alert');

  // Step 1
  const form           = document.getElementById('fp_form');
  const identifierIn   = document.getElementById('fp_identifier');
  const sendBtn        = document.getElementById('fp_btn');
  const backBtn        = document.getElementById('fp_back');

  // Step 2
  const otpState       = document.getElementById('fp_otp_state');
  const otpBoxes       = document.querySelectorAll('.ux-otp-box');
  const otpBtn         = document.getElementById('fp_otp_btn');
  const otpBack        = document.getElementById('fp_otp_back');
  const resendBtn      = document.getElementById('fp_resend_btn');
  const resendTimer    = document.getElementById('fp_resend_timer');

  // Step 3
  const resetState     = document.getElementById('fp_reset_state');
  const passwordIn     = document.getElementById('fp_password');
  const passwordConfIn = document.getElementById('fp_password_confirm');
  const resetBtn       = document.getElementById('fp_reset_btn');
  const resetBack      = document.getElementById('fp_reset_back');

  // Step 4
  const successState   = document.getElementById('fp_success_state');
  const goLoginBtn     = document.getElementById('fp_go_login');

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Helpers
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  function showAlert(kind, msg) {
    alertEl.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
    alertEl.classList.add(
      'alert',
      kind === 'error'   ? 'alert-danger'  :
      kind === 'warn'    ? 'alert-warning' : 'alert-success'
    );
    alertEl.textContent = msg;
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function clearAlert() {
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function hideAll() {
    form.classList.add('d-none');
    otpState.classList.add('d-none');
    resetState.classList.add('d-none');
    successState.classList.add('d-none');
    clearAlert();
  }

  function setBusy(btn, busy, idleHtml) {
    btn.disabled  = busy;
    btn.innerHTML = busy
      ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Please wait…'
      : idleHtml;
  }

  function getOtp() {
    return Array.from(otpBoxes).map(b => b.value).join('');
  }

  function clearOtpBoxes() {
    otpBoxes.forEach(b => { b.value = ''; b.classList.remove('ux-filled'); });
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Resend countdown
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
 function formatCountdown(secs) {
    if (secs >= 60) {
      const m = Math.floor(secs / 60);
      const s = secs % 60;
      return `(${m}m ${s.toString().padStart(2, '0')}s)`;
    }
    return `(${secs}s)`;
  }

  function startResendCountdown(seconds) {
    resendBtn.disabled = true;
    clearInterval(resendInterval);

    if (seconds >= 3600) {
      resendTimer.textContent = '';
      resendBtn.textContent   = 'Try again tomorrow';
      return;
    }

    let secs = seconds;
    resendTimer.textContent = formatCountdown(secs);

    resendInterval = setInterval(() => {
      secs--;
      if (secs <= 0) {
        clearInterval(resendInterval);
        resendBtn.disabled      = false;
        resendBtn.textContent   = 'Resend OTP';
        resendTimer.textContent = '';
      } else {
        resendTimer.textContent = formatCountdown(secs);
      }
    }, 1000);
  }
  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | OTP box keyboard UX
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  otpBoxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      box.classList.toggle('ux-filled', box.value !== '');
      if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
    });

    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && i > 0) otpBoxes[i - 1].focus();
      if (e.key === 'Enter') otpBtn.click();
    });

    box.addEventListener('paste', (e) => {
      e.preventDefault();
      const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      digits.split('').forEach((d, j) => {
        if (otpBoxes[j]) { otpBoxes[j].value = d; otpBoxes[j].classList.add('ux-filled'); }
      });
      otpBoxes[Math.min(digits.length, otpBoxes.length - 1)].focus();
    });
  });

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Step transitions
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  function goToStep1() {
    hideAll();
    form.classList.remove('d-none');
    identifierIn.disabled = false;
    identifierIn.value    = '';
    currentEmail          = '';
    verifiedOtp           = '';
    title.textContent     = 'Forgot your password?';
    sub.textContent       = "Enter your email or mobile number and we'll send you an OTP.";
    clearInterval(resendInterval);
    identifierIn.focus();
  }

  function goToStep2(cooldownSeconds = 120) {
    hideAll();
    otpState.classList.remove('d-none');
    clearOtpBoxes();
    title.textContent = 'Enter your OTP';
    sub.textContent   = 'OTP sent to the mobile & email linked to your account.';

    if (cooldownSeconds === null) {
        // 3rd attempt — permanently disable until tomorrow
        clearInterval(resendInterval);
        resendBtn.disabled      = true;
        resendBtn.textContent   = 'Try again tomorrow';
        resendTimer.textContent = '';
    } else {
        startResendCountdown(cooldownSeconds);
    }

    otpBoxes[0].focus();
}

  function goToStep3() {
    hideAll();
    resetState.classList.remove('d-none');
    passwordIn.value    = '';
    passwordConfIn.value = '';
    title.textContent   = 'Set a new password';
    sub.textContent     = 'Almost there — choose a strong password.';
    passwordIn.focus();
  }

  function goToStep4() {
    hideAll();
    successState.classList.remove('d-none');
    title.textContent = 'All done!';
    sub.textContent   = 'Your password has been reset successfully.';
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | API helper
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  async function apiPost(url, body) {
    const res  = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept':       'application/json',
      },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  }

  function extractError(data) {
    return data?.message
      || (data?.errors ? Object.values(data.errors).flat().join(', ') : null)
      || 'Something went wrong. Please try again.';
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Step 1 — Send OTP
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  async function doSendOtp() {
    clearAlert();
    const identifier = (identifierIn.value || '').trim();

    if (!identifier) {
      showAlert('error', 'Please enter your email or mobile number.');
      identifierIn.focus();
      return;
    }

    const looksLikeEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);
    const looksLikePhone = /^\+?[\d\s\-]{7,15}$/.test(identifier);

    if (!looksLikeEmail && !looksLikePhone) {
      showAlert('error', 'Please enter a valid email address or mobile number.');
      identifierIn.focus();
      return;
    }

    setBusy(sendBtn, true, '<span class="me-2"><i class="fa-solid fa-paper-plane"></i></span> Send OTP');
    identifierIn.disabled = true;

    try {
      const { ok, data } = await apiPost(SEND_OTP_API, { identifier });

      if (!ok) {
        showAlert('error', extractError(data));
        identifierIn.disabled = false;
        return;
      }

      // Backend returns the resolved email — store it for the reset call
currentEmail = data?.data?.token_key || data?.data?.email || identifier;

if (data?.is_final_attempt) {
    goToStep2(null); // goes to step 2 but button permanently disabled
} else {
    goToStep2(data?.cooldown_seconds ?? 120);
}
    } catch {
      showAlert('error', 'Network error. Please check your connection and try again.');
      identifierIn.disabled = false;
    } finally {
      setBusy(sendBtn, false, '<span class="me-2"><i class="fa-solid fa-paper-plane"></i></span> Send OTP');
    }
  }

  form.addEventListener('submit', (e) => { e.preventDefault(); doSendOtp(); });
  backBtn.addEventListener('click', () => window.location.href = LOGIN_PAGE);

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Step 2 — Collect OTP → go to step 3
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  otpBtn.addEventListener('click', () => {
    clearAlert();
    const otp = getOtp();

    if (otp.length !== 6) {
      showAlert('error', 'Please enter all 6 digits of your OTP.');
      otpBoxes[otp.length]?.focus();
      return;
    }

    // OTP is verified at reset time by the backend — just store and advance
    verifiedOtp = otp;
    goToStep3();
  });

  otpBack.addEventListener('click', goToStep1);

 resendBtn.addEventListener('click', async () => {
    clearAlert();
    clearInterval(resendInterval);
    resendBtn.disabled      = true;
    resendTimer.textContent = '';

    try {
      const { ok, status, data } = await apiPost(SEND_OTP_API, {
        identifier: identifierIn.value.trim()
      });

      if (!ok) {
        const msg = extractError(data);
        if (status === 429) {
          if (data?.wait_seconds) {
            startResendCountdown(data.wait_seconds);
          }
          showAlert('warn', msg);
          return;
        }
        showAlert('error', msg);
        resendBtn.disabled = false;
        return;
      }

      if (data?.data?.token_key) currentEmail = data.data.token_key;
      else if (data?.data?.email) currentEmail = data.data.email;

      clearOtpBoxes();
      otpBoxes[0].focus();
      showAlert('success', 'A new OTP has been sent to your mobile and email.');

      if (data?.is_final_attempt) {
        clearInterval(resendInterval);
        resendBtn.disabled      = true;
        resendBtn.textContent   = 'Try again tomorrow';
        resendTimer.textContent = '';
      } else if (data?.cooldown_seconds) {
        startResendCountdown(data.cooldown_seconds);
      }

    } catch {
      showAlert('error', 'Network error. Please try again.');
      resendBtn.disabled = false;
    }
  });
  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Step 3 — Reset Password
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  resetBtn.addEventListener('click', async () => {
    clearAlert();
    const password              = passwordIn.value;
    const password_confirmation = passwordConfIn.value;

    if (!password || password.length < 8) {
      showAlert('error', 'Password must be at least 8 characters.');
      passwordIn.focus();
      return;
    }

    if (password !== password_confirmation) {
      showAlert('error', 'Passwords do not match.');
      passwordConfIn.focus();
      return;
    }

    setBusy(resetBtn, true, '<span class="me-2"><i class="fa-solid fa-key"></i></span> Reset Password');

    try {
const { ok, data } = await apiPost(RESET_API, {
    token_key:             currentEmail,   // ← key name changed to match backend
    otp:                   verifiedOtp,
    password,
    password_confirmation,
});

      if (!ok) {
        // OTP invalid/expired — send back to OTP step
        const msg = extractError(data);
        const isOtpError = ['otp', 'invalid', 'expired'].some(k => msg.toLowerCase().includes(k));
        if (isOtpError) {
          showAlert('error', msg || 'OTP is invalid or has expired. Please request a new one.');
          goToStep2();
          return;
        }
        showAlert('error', msg);
        return;
      }

      goToStep4();

    } catch {
      showAlert('error', 'Network error. Please check your connection and try again.');
    } finally {
      setBusy(resetBtn, false, '<span class="me-2"><i class="fa-solid fa-key"></i></span> Reset Password');
    }
  });

  resetBack.addEventListener('click', goToStep2);

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Step 4 — Done
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  goLoginBtn.addEventListener('click', () => window.location.href = LOGIN_PAGE);

})();
</script>
</body>
</html>
