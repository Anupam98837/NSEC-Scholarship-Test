{{-- resources/views/auth/student-register.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Register — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    html, body { height:100%; }

    body.ux-auth-body{
      min-height:100%;
      height:auto;
      overflow:auto;
      background:var(--bg-body);
      color:var(--text-color);
      font-family:var(--font-sans);
    }

    .ux-grid{
  min-height:100vh;
  min-height:100svh;
  min-height:100dvh;
  height:auto;
  display:flex;
  justify-content:center;
  align-items:flex-start;
  width:100%;
}
    .ux-left, .ux-right{ min-width:0; }

    @media (max-width: 1440px){ .ux-grid{ grid-template-columns: minmax(400px,540px) 1fr; } }
    @media (max-width: 1366px){ .ux-grid{ grid-template-columns: minmax(380px,520px) 1fr; } }
    @media (max-width: 1280px){ .ux-grid{ grid-template-columns: minmax(360px,500px) 1fr; } }
    @media (max-width: 1200px){ .ux-grid{ grid-template-columns: minmax(340px,480px) 1fr; } }
    @media (max-width: 1100px){ .ux-grid{ grid-template-columns: minmax(320px,460px) 1fr; } }
    @media (max-width: 992px){ .ux-grid{ grid-template-columns: 1fr; } }

    .ux-left{
  min-height:100vh;
  min-height:100svh;
  min-height:100dvh;
  height:auto;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  padding:clamp(18px,5vw,56px);
  padding-bottom:clamp(22px,5vw,64px);
  position:relative;
  isolation:isolate;
  overflow:visible;
  width:100%;
  max-width:560px;
  margin:0 auto;
}

    .ux-brand{ margin-top:0; }
    #ux_form{ margin-bottom:0; }

    .ux-brand{
      display:grid;
      place-items:center;
      margin-bottom:18px;
      position:relative;
      z-index:1;
      max-width:100%;
    }
    .ux-brand img{
      height:70px;
      max-width:100%;
      object-fit:contain;
    }

    .ux-title{
      font-family:var(--font-head);
      font-weight:800;
      color:var(--ink);
      text-align:center;
      font-size:clamp(1.5rem, 2.6vw, 2.2rem);
      margin:.35rem 0 .25rem;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
      line-height:1.2;
    }

    .ux-sub{
      text-align:center;
      color:var(--muted-color);
      margin-bottom:18px;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
    }

    .ux-card{
      position:relative;
      z-index:1;
      background:var(--surface);
      border:1px solid var(--line-strong);
      border-radius:18px;
      padding:24px;
      box-shadow:var(--shadow-2);
      width:100%;
      max-width:min(760px, 100%);
      overflow:hidden;
      display:flex;
      flex-direction:column;
    }

    .ux-card::before,
    .ux-card::after{
      content:"";
      position:absolute;
      border-radius:50%;
      filter: blur(18px);
      opacity:.25;
      pointer-events:none;
    }
    .ux-card::before{
      width:160px; height:160px;
      left:-40px; top:-40px;
      background: radial-gradient(closest-side, var(--accent-color), transparent 65%);
      animation: ux-orbitA 12s linear infinite;
    }
    .ux-card::after{
      width:140px; height:140px;
      right:-30px; bottom:-30px;
      background: radial-gradient(closest-side, var(--primary-color), transparent 65%);
      animation: ux-orbitB 14s linear infinite reverse;
    }

    .ux-float-chip{
      position:absolute;
      top:12px; right:12px;
      z-index:1;
      padding:6px 10px;
      border-radius:999px;
      font-size:.78rem;
      background:rgba(255,255,255,.7);
      color:var(--secondary-color);
      border:1px solid var(--line-strong);
      backdrop-filter: blur(4px);
      animation: ux-chip 7s ease-in-out infinite;
    }

    .ux-label{ font-weight:600; color:var(--ink); }
    .ux-input-wrap{ position:relative; }

    .ux-control{
      height:46px;
      border-radius:12px;
      padding-right:48px;
      max-width:100%;
    }

    .ux-control::placeholder{ color:#aab2c2; }

    /* disabled password field style */
    .ux-control:disabled{
      opacity:.5;
      cursor:not-allowed;
      background:var(--bg-body, #f8fafc);
    }

    .ux-eye{
      position:absolute;
      top:50%; right:10px;
      transform:translateY(-50%);
      width:36px; height:36px;
      border:none;
      background:transparent;
      color:#8892a6;
      display:grid;
      place-items:center;
      cursor:pointer;
      border-radius:8px;
    }
    .ux-eye:focus-visible{
      outline:none;
      box-shadow: var(--ring);
    }

    .ux-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      row-gap:8px;
    }

    .ux-login{
      width:100%;
      height:48px;
      border:none;
      border-radius:12px;
      font-weight:700;
      color:#fff;
      background:linear-gradient(
        180deg,
        color-mix(in oklab, var(--primary-color) 92%, #fff 8%),
        var(--primary-color)
      );
      box-shadow:0 10px 22px rgba(20,184,166,.26);
      transition:var(--transition);
      margin-top:6px;
    }
    .ux-login:hover:not(:disabled){
      filter:brightness(.98);
      transform:translateY(-1px);
    }
    .ux-login:disabled{
      opacity:.55;
      cursor:not-allowed;
      transform:none;
      filter:none;
    }

    .ux-field-err{
      font-size:12px;
      margin-top:6px;
      color:var(--danger-color, #dc3545);
      display:none;
    }
    .ux-field-err.show{ display:block; }

    .ux-phone-row,
    .ux-otp-row{
      display:grid;
      grid-template-columns: 1fr auto;
      gap:10px;
      align-items:center;
    }

    .ux-phone-row .ux-control,
    .ux-otp-row .ux-control{
      padding-right:14px;
    }

    .ux-mini-btn{
      height:46px;
      min-width:92px;
      border:none;
      border-radius:12px;
      padding:0 14px;
      font-weight:700;
      color:#fff;
      white-space:nowrap;
      background:linear-gradient(
        180deg,
        color-mix(in oklab, var(--secondary-color) 92%, #fff 8%),
        var(--secondary-color)
      );
      box-shadow:0 10px 22px rgba(2, 132, 199, .18);
      transition:var(--transition);
    }
    .ux-mini-btn:hover:not(:disabled){
      filter:brightness(.98);
      transform:translateY(-1px);
    }
    .ux-mini-btn:disabled{
      opacity:.7;
      cursor:not-allowed;
      transform:none;
      filter:none;
    }

    .ux-hint{
      margin-top:8px;
      font-size:.84rem;
      color:var(--muted-color);
    }

    .ux-verified{
      display:none;
      margin-top:8px;
      font-size:.84rem;
      font-weight:600;
      color:#198754;
    }
    .ux-verified.show{ display:block; }

    /* RIGHT visuals */
    .ux-right{
      position:relative;
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;
      display:grid;
      place-items:center;
      background:
        radial-gradient(120% 100% at 5% 10%, rgba(20,184,166,.18) 0%, rgba(8,47,73,0) 55%),
        linear-gradient(180deg,#022c22,#020617);
      isolation:isolate;
      overflow:hidden;
    }
    @media (max-width: 992px){ .ux-right{ display:none; } }

    .ux-arc{
      position:absolute;
      inset: -18% -10% auto auto;
      width:120%; height:140%;
      background:radial-gradient(110% 110% at 80% 20%,
        rgba(45,212,191,.24) 0%,
        rgba(15,118,110,.18) 35%,
        rgba(15,23,42,0) 62%);
      border-bottom-left-radius:48% 44%;
      pointer-events:none;
      animation: ux-drift 16s ease-in-out infinite;
    }
    .ux-ring{
      position:absolute;
      inset:auto -120px -80px auto;
      width:420px; height:420px;
      border-radius:50%;
      background:
        radial-gradient(closest-side, rgba(255,255,255,.14), rgba(255,255,255,0) 70%),
        conic-gradient(from 0deg,
          rgba(20,184,166,.25),
          rgba(56,189,248,.25),
          rgba(20,184,166,.25));
      filter:blur(18px);
      opacity:.18;
      pointer-events:none;
      animation: ux-spin 24s linear infinite;
    }

    .ux-hero{
      position:relative;
      width:min(680px, 96%);
      aspect-ratio: 3/4;
      animation: ux-pop .7s ease-out both;
      max-width:100%;
    }
    @media (max-width: 1366px){ .ux-hero{ width:min(600px, 96%); } }
    @media (max-width: 1200px){ .ux-hero{ width:min(560px, 96%); } }

    .ux-hero-frame{
      position:relative;
      width:100%; height:100%;
      padding:20px;
      border-radius:36px;
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
      box-shadow:
        0 24px 54px rgba(0,0,0,.35),
        0 0 0 1px rgba(255,255,255,.06) inset;
      transition: transform .25s ease, box-shadow .25s ease;
      will-change: transform;
    }
    .ux-hero-img{
      width:100%; height:100%;
      border-radius:24px;
      overflow:hidden;
      position:relative;
      box-shadow:0 18px 40px rgba(0,0,0,.35);
    }
    .ux-hero-img img{
      width:100%; height:100%;
      object-fit:cover;
      display:block;
      transform:translateZ(0);
      animation: ux-zoom 26s ease-in-out infinite alternate;
      will-change: transform;
    }
    .ux-particles{
      position:absolute;
      inset:0;
      pointer-events:none;
      opacity:.28;
      background:
        radial-gradient(#ffffff 1px, transparent 2px) 0 0/22px 22px,
        radial-gradient(#ffffff 1px, transparent 2px) 11px 11px/22px 22px;
      mix-blend-mode: overlay;
      animation: ux-twinkle 12s linear infinite;
    }

    .ux-obj{
      position:absolute;
      z-index:3;
      opacity:.9;
      filter: drop-shadow(0 8px 18px rgba(0,0,0,.28));
      user-select:none;
      pointer-events:none;
    }
    .ux-badges{
      top: clamp(18px, 3vw, 36px);
      left: clamp(12px, 2vw, 28px);
      display:grid;
      gap:6px;
    }
    .ux-badge-pill{
      min-width:120px; height:24px;
      padding:0 12px;
      border-radius:999px;
      font-size:11px;
      display:flex;align-items:center;gap:6px;
      background:rgba(15,118,110,.88);
      color:#e0f2f1;
    }
    .ux-badge-pill:nth-child(2){ background:rgba(8,47,73,.9); }
    .ux-badge-pill:nth-child(3){ background:rgba(234,179,8,.92); color:#0b1120; }

    @media (max-width: 576px){
      .ux-left{ padding:16px; padding-bottom:26px; }
      .ux-brand img{ height:60px; }
      .ux-card{ padding:18px; border-radius:16px; }
      .ux-control{ height:44px; }
      .ux-mini-btn{ height:44px; min-width:84px; }
      .ux-login{ height:46px; }
      .ux-phone-row, .ux-otp-row{ grid-template-columns: 1fr; }
    }

    @keyframes ux-pop{ from{opacity:0; transform:translateY(10px) scale(.98);} to{opacity:1; transform:none;} }
    @keyframes ux-zoom{ from{transform:scale(1);} to{transform:scale(1.06);} }
    @keyframes ux-drift{ 0%,100%{transform:translate3d(0,0,0);} 50%{transform:translate3d(-2%,2%,0);} }
    @keyframes ux-spin{ 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
    @keyframes ux-orbitA{ 0%{transform:translate(0,0);} 50%{transform:translate(6px, -6px);} 100%{transform:translate(0,0);} }
    @keyframes ux-orbitB{ 0%{transform:translate(-6px, 6px);} 100%{transform:translate(0,0);} }
    @keyframes ux-chip{ 0%,100%{ transform:translateY(0);} 50%{ transform:translateY(-6px);} }
    @keyframes ux-twinkle{ 0%{opacity:.22;} 50%{opacity:.34;} 100%{opacity:.22;} }
  </style>
</head>

<body class="ux-auth-body">

@php
  $REGISTER_API   = url('/api/auth/student-register');
  $SEND_OTP_API   = url('/api/auth/send-phone-otp');
  $VERIFY_OTP_API = url('/api/auth/verify-phone-otp');
  $LOGIN_URL      = url('/');
  $REDIRECT_AFTER = url('/dashboard');
@endphp

<div class="ux-grid">

  <!-- LEFT -->
  <section class="ux-left">
    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.jpg') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">Student Registration</h1>
    <p class="ux-sub">Create your account to proceed.</p>

    <form class="ux-card" id="ux_form" novalidate>
      <span class="ux-float-chip"><i class="fa-solid fa-shield-halved me-1"></i>Secure • OTP verified</span>

      <!-- Inline alert -->
      <div id="ux_alert" class="alert d-none mb-3" role="alert"></div>

      <input type="hidden" id="ux_phone_verified" value="0">
      <input type="hidden" id="ux_verification_token" value="">

      <!-- Name -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_name">Full Name</label>
        <div class="ux-input-wrap">
          <input id="ux_name" type="text" class="ux-control form-control"
                 placeholder="Enter your full name" required>
        </div>
        <div class="ux-field-err" id="err_name"></div>
      </div>

      <!-- Phone + OTP button -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_phone">Phone Number</label>
        <div class="ux-phone-row">
          <div class="ux-input-wrap">
            <input id="ux_phone" type="text" class="ux-control form-control"
                   placeholder="90000 00000" inputmode="numeric" autocomplete="tel" required>
          </div>
          <button type="button" class="ux-mini-btn" id="ux_sendOtpBtn">
            OTP
          </button>
        </div>
        <div class="ux-field-err" id="err_phone_number"></div>
        <div class="ux-hint" id="ux_phoneHint">Click OTP to receive a verification code on your phone.</div>
        <div class="ux-verified" id="ux_phoneVerifiedMsg">
          <i class="fa-solid fa-circle-check me-1"></i>Phone number verified successfully.
        </div>
      </div>

      <!-- OTP block -->
      <div class="mb-3 d-none" id="ux_otpBlock">
        <label class="ux-label form-label" for="ux_otp">Enter OTP</label>
        <div class="ux-otp-row">
          <div class="ux-input-wrap">
            <input id="ux_otp" type="text" class="ux-control form-control"
                   placeholder="Enter OTP" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
          </div>
          <button type="button" class="ux-mini-btn" id="ux_verifyOtpBtn">
            Verify
          </button>
        </div>
        <div class="ux-field-err" id="err_otp"></div>
      </div>

      <!-- Password — always visible, disabled until OTP verified -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_pw">Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw" type="password" class="ux-control form-control"
                 placeholder="Minimum 8+ characters" minlength="8" required disabled>
          <button type="button" class="ux-eye" id="ux_togglePw" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
        <div class="ux-field-err" id="err_password"></div>
      </div>

      <!-- Confirm Password — always visible, disabled until OTP verified -->
      <div class="mb-2">
        <label class="ux-label form-label" for="ux_pw2">Confirm Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw2" type="password" class="ux-control form-control"
                 placeholder="Re-type password" minlength="8" required disabled>
          <button type="button" class="ux-eye" id="ux_togglePw2" aria-label="Toggle confirm password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
        <div class="ux-field-err" id="err_password_confirmation"></div>
      </div>

      <div class="ux-row mb-3">
        <div class="form-check m-0">
          <input class="form-check-input" type="checkbox" id="ux_keep">
          <label class="form-check-label" for="ux_keep">Keep me logged in</label>
        </div>
        <a class="text-decoration-none" href="{{ $LOGIN_URL }}">
          Already have account? Login
        </a>
      </div>

      <!-- Submit — disabled until OTP verified -->
      <button class="ux-login" id="ux_btn" type="submit" disabled>
        <span class="me-2"><i class="fa-solid fa-mobile-screen-button"></i></span> Verify phone to continue
      </button>
    </form>
  </section>

  <!-- RIGHT — completely unchanged -->
  <aside class="ux-right d-none" id="ux_visual">
    <span class="ux-arc" aria-hidden="true"></span>
    <span class="ux-ring" aria-hidden="true"></span>

    <div class="ux-obj ux-badges" aria-hidden="true">
      <div class="ux-badge-pill">
        <i class="fa-solid fa-clipboard-check"></i> Quick signup
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-user-graduate"></i> Student portal
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-shield-halved"></i> Secure access
      </div>
    </div>

    <div class="ux-hero" id="ux_hero">
      <div class="ux-hero-frame">
        <div class="ux-hero-img">
          <img
            src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=1600&auto=format&fit=crop&q=80"
            alt="Student registration">
          <div class="ux-particles" aria-hidden="true"></div>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
(function(){
  const REGISTER_API   = @json($REGISTER_API);
  const SEND_OTP_API   = @json($SEND_OTP_API);
  const VERIFY_OTP_API = @json($VERIFY_OTP_API);
  const LOGIN_URL      = @json($LOGIN_URL);
  const REDIRECT_AFTER = @json($REDIRECT_AFTER);

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const state = {
    otpSent: false,
    phoneVerified: false,
    verificationToken: '',
    verifiedPhone: ''
  };

  const form       = document.getElementById('ux_form');
  const btn        = document.getElementById('ux_btn');
  const alertEl    = document.getElementById('ux_alert');

  const nameIn     = document.getElementById('ux_name');
  const phoneIn    = document.getElementById('ux_phone');
  const otpIn      = document.getElementById('ux_otp');
  const pw1        = document.getElementById('ux_pw');
  const pw2        = document.getElementById('ux_pw2');
  const keepCb     = document.getElementById('ux_keep');

  const otpBlock   = document.getElementById('ux_otpBlock');
  const sendOtpBtn = document.getElementById('ux_sendOtpBtn');
  const verifyBtn  = document.getElementById('ux_verifyOtpBtn');

  const phoneVerifiedHidden = document.getElementById('ux_phone_verified');
  const verificationTokenEl = document.getElementById('ux_verification_token');
  const phoneVerifiedMsg    = document.getElementById('ux_phoneVerifiedMsg');

  const t1 = document.getElementById('ux_togglePw');
  const t2 = document.getElementById('ux_togglePw2');

  const CREATE_BTN_HTML = '<span class="me-2"><i class="fa-solid fa-user-plus"></i></span> Create Account';
  const VERIFY_BTN_HTML = '<span class="me-2"><i class="fa-solid fa-mobile-screen-button"></i></span> Verify phone to continue';

  function normalizePhone(v){ return String(v || '').replace(/\D/g, ''); }
  function normalizeOtp(v){ return String(v || '').replace(/\D/g, '').slice(0, 6); }
  function validPhone(v){ const d = normalizePhone(v); return d.length >= 10 && d.length <= 15; }
  function validOtp(v){ const d = normalizeOtp(v); return d.length >= 4 && d.length <= 6; }

  function showAlert(kind, msg){
    alertEl.classList.remove('d-none','alert-danger','alert-success','alert-warning');
    alertEl.classList.add('alert', kind === 'error' ? 'alert-danger' : (kind === 'warn' ? 'alert-warning' : 'alert-success'));
    alertEl.textContent = msg;
  }
  function clearAlert(){ alertEl.classList.add('d-none'); alertEl.textContent = ''; }

  function clearFieldErrors(){
    document.querySelectorAll('.ux-field-err').forEach(el => { el.classList.remove('show'); el.textContent = ''; });
  }
  function setFieldError(key, msg){
    const el = document.getElementById('err_' + key);
    if(el){ el.textContent = msg || 'Invalid value'; el.classList.add('show'); }
  }

  function togglePw(input, btnEl){
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btnEl.innerHTML = show
      ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
      : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
  }

  function setSubmitBusy(b){
    btn.disabled = b || !state.phoneVerified;
    btn.innerHTML = b
      ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creating account...'
      : (state.phoneVerified ? CREATE_BTN_HTML : VERIFY_BTN_HTML);
  }

  function setSendOtpBusy(b){
    sendOtpBtn.disabled = b;
    sendOtpBtn.innerHTML = b
      ? '<i class="fa-solid fa-spinner fa-spin"></i>'
      : (state.otpSent ? 'Resend' : 'OTP');
  }

  function setVerifyOtpBusy(b){
    verifyBtn.disabled = b;
    verifyBtn.innerHTML = b ? '<i class="fa-solid fa-spinner fa-spin"></i>' : 'Verify';
  }

  function unlockPasswordSection(){
    state.phoneVerified = true;
    phoneVerifiedHidden.value = '1';
    phoneVerifiedMsg.classList.add('show');

    // Enable password fields
    pw1.disabled = false;
    pw2.disabled = false;

    // Enable submit button and update label
    btn.disabled = false;
    btn.innerHTML = CREATE_BTN_HTML;
  }

  function resetVerificationState(hideOtpBlock = true){
    state.phoneVerified = false;
    state.verificationToken = '';
    state.verifiedPhone = '';

    phoneVerifiedHidden.value = '0';
    verificationTokenEl.value = '';

    phoneVerifiedMsg.classList.remove('show');

    // Disable password fields again
    pw1.disabled = true;
    pw2.disabled = true;
    pw1.value = '';
    pw2.value = '';

    if(hideOtpBlock){
      state.otpSent = false;
      otpBlock.classList.add('d-none');
      otpIn.value = '';
      sendOtpBtn.innerHTML = 'OTP';
    }

    btn.disabled = true;
    btn.innerHTML = VERIFY_BTN_HTML;
  }

  function authStoreSet(token, role, keep){
    sessionStorage.setItem('token', token);
    sessionStorage.setItem('role', role);
    if(keep){ localStorage.setItem('token', token); localStorage.setItem('role', role); }
    else { localStorage.removeItem('token'); localStorage.removeItem('role'); }
  }

  t1?.addEventListener('click', () => togglePw(pw1, t1));
  t2?.addEventListener('click', () => togglePw(pw2, t2));

  phoneIn?.addEventListener('input', function(){
    this.value = normalizePhone(this.value);
    if(state.phoneVerified && normalizePhone(this.value) !== state.verifiedPhone){
      clearAlert();
      resetVerificationState(false);
      showAlert('warn', 'Phone number changed. Please verify again.');
    }
  });

  otpIn?.addEventListener('input', function(){ this.value = normalizeOtp(this.value); });

  sendOtpBtn?.addEventListener('click', async function(){
    clearAlert();
    clearFieldErrors();

    const phone = normalizePhone(phoneIn.value);
    if(!phone){ setFieldError('phone_number', 'Please enter your phone number'); showAlert('warn', 'Please enter your phone number first.'); return; }
    if(!validPhone(phone)){ setFieldError('phone_number', 'Enter a valid phone number'); showAlert('warn', 'Please enter a valid phone number.'); return; }
    if(state.phoneVerified && phone === state.verifiedPhone){ showAlert('success', 'This phone number is already verified.'); return; }

    setSendOtpBusy(true);
    try{
      const res = await fetch(SEND_OTP_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ phone_number: phone })
      });
      const data = await res.json().catch(() => ({}));

      if(res.status === 422){
        const errors = data?.errors || {};
        Object.keys(errors).forEach((k) => { const msg = Array.isArray(errors[k]) ? errors[k][0] : errors[k]; setFieldError(k, msg); });
        showAlert('warn', data?.message || 'Please check the phone number.');
        return;
      }
      if(!res.ok){ showAlert('error', data?.message || data?.error || 'Failed to send OTP.'); return; }

      state.otpSent = true;
      state.phoneVerified = false;
      state.verificationToken = data?.verification_token || data?.token || data?.session_id || '';
      verificationTokenEl.value = state.verificationToken || '';

      otpBlock.classList.remove('d-none');
      otpIn.value = '';
      otpIn.focus();
      showAlert('success', data?.message || 'OTP sent successfully.');
    }catch(err){
      showAlert('error', 'Network error while sending OTP.');
    }finally{
      setSendOtpBusy(false);
    }
  });

  verifyBtn?.addEventListener('click', async function(){
    clearAlert();
    clearFieldErrors();

    const phone = normalizePhone(phoneIn.value);
    const otp   = normalizeOtp(otpIn.value);

    if(!state.otpSent){ showAlert('warn', 'Please send OTP first.'); return; }
    if(!validPhone(phone)){ setFieldError('phone_number', 'Enter a valid phone number'); showAlert('warn', 'Please enter a valid phone number.'); return; }
    if(!otp){ setFieldError('otp', 'Please enter the OTP'); showAlert('warn', 'Please enter the OTP.'); return; }
    if(!validOtp(otp)){ setFieldError('otp', 'Enter a valid OTP'); showAlert('warn', 'Please enter a valid OTP.'); return; }

    setVerifyOtpBusy(true);
    try{
      const res = await fetch(VERIFY_OTP_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ phone_number: phone, otp: otp, verification_token: verificationTokenEl.value || state.verificationToken || '' })
      });
      const data = await res.json().catch(() => ({}));

      if(res.status === 422){
        const errors = data?.errors || {};
        Object.keys(errors).forEach((k) => { const msg = Array.isArray(errors[k]) ? errors[k][0] : errors[k]; setFieldError(k, msg); });
        showAlert('warn', data?.message || 'Please check the OTP.');
        return;
      }
      if(!res.ok){ showAlert('error', data?.message || data?.error || 'OTP verification failed.'); return; }

      state.verifiedPhone = phone;
      state.verificationToken = data?.verification_token || verificationTokenEl.value || state.verificationToken || '';
      verificationTokenEl.value = state.verificationToken || '';

      phoneIn.disabled = true;
      otpIn.disabled = true;
      sendOtpBtn.disabled = true;
      verifyBtn.disabled = true;

      unlockPasswordSection();
      showAlert('success', data?.message || 'Phone verified successfully.');
    }catch(err){
      showAlert('error', 'Network error while verifying OTP.');
    }finally{
      setVerifyOtpBusy(false);
    }
  });

  form?.addEventListener('submit', async function(e){
    e.preventDefault();
    clearAlert();
    clearFieldErrors();

    const payload = {
  name:                  (nameIn.value || '').trim(),
  phone_number:          normalizePhone(phoneIn.value),
  password:              pw1.value || '',
  password_confirmation: pw2.value || '',
};
    if(!payload.name || payload.name.length < 2){ setFieldError('name', 'Please enter your full name'); showAlert('warn', 'Please fix the errors below.'); return; }
    if(!payload.phone_number){ setFieldError('phone_number', 'Please enter your phone number'); showAlert('warn', 'Please fix the errors below.'); return; }
    if(!state.phoneVerified){ setFieldError('otp', 'Please verify your OTP first'); showAlert('warn', 'Please verify your phone number first.'); return; }
    if(!payload.password || payload.password.length < 8){ setFieldError('password', 'Password must be at least 8 characters'); showAlert('warn', 'Please fix the errors below.'); return; }
    if(payload.password !== payload.password_confirmation){ setFieldError('password_confirmation', 'Passwords do not match'); showAlert('warn', 'Please fix the errors below.'); return; }

    setSubmitBusy(true);
    try{
      const res = await fetch(REGISTER_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => ({}));

      if(res.status === 422){
        const errors = data?.errors || {};
        Object.keys(errors).forEach((k) => { const msg = Array.isArray(errors[k]) ? errors[k][0] : errors[k]; setFieldError(k, msg); });
        showAlert('warn', data?.message || 'Please fix the highlighted fields.');
        setSubmitBusy(false); return;
      }
      if(!res.ok){
        showAlert('error', data?.message || data?.error || (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Registration failed.'));
        setSubmitBusy(false); return;
      }

      // const token = data?.access_token || data?.token || '';
      // const role  = (data?.user?.role || 'student').toString().toLowerCase();

      // if(!token){ showAlert('error', 'No token received from server.'); setSubmitBusy(false); return; }

      // authStoreSet(token, role, !!keepCb.checked);
      // showAlert('success', 'Registered successfully. Redirecting...');
      // setTimeout(() => window.location.assign(REDIRECT_AFTER), 650);
    showAlert('success', 'Registered successfully! Redirecting to login...');
setTimeout(() => window.location.assign(LOGIN_URL), 1500);
    }catch(err){
      showAlert('error', 'Network error. Please try again.');
    }finally{
      setSubmitBusy(false);
    }
  });

  // Parallax — unchanged
  (function(){
    const stage = document.getElementById('ux_visual');
    const hero  = document.getElementById('ux_hero');
    const frame = document.querySelector('.ux-hero-frame');
    const img   = document.querySelector('.ux-hero-img img');
    if(!stage || !frame || !img || !hero) return;

    const mq = window.matchMedia('(max-width: 992px)');
    let targetTX=0,targetTY=0,targetRX=0,targetRY=0,currTX=0,currTY=0,currRX=0,currRY=0,rafId=null;
    const MAX_T=18,MAX_RX=6,MAX_RY=8,LERP=0.12;

    function onMove(e){
      const rect=stage.getBoundingClientRect();
      const ndx=Math.max(-1,Math.min(1,(e.clientX-rect.left-rect.width/2)/(rect.width/2)));
      const ndy=Math.max(-1,Math.min(1,(e.clientY-rect.top-rect.height/2)/(rect.height/2)));
      targetTX=ndx*MAX_T; targetTY=ndy*MAX_T; targetRY=ndx*MAX_RY; targetRX=-ndy*MAX_RX;
      if(!hero.classList.contains('is-tracking')){ hero.classList.add('is-tracking'); tick(); }
    }
    function onLeave(){ targetTX=targetTY=targetRX=targetRY=0; }
    function tick(){
      currTX+=(targetTX-currTX)*LERP; currTY+=(targetTY-currTY)*LERP;
      currRX+=(targetRX-currRX)*LERP; currRY+=(targetRY-currRY)*LERP;
      frame.style.transform=`translate3d(${currTX.toFixed(2)}px,${currTY.toFixed(2)}px,0) rotateX(${currRX.toFixed(2)}deg) rotateY(${currRY.toFixed(2)}deg)`;
      img.style.transform=`translate3d(${(-currTX*.6).toFixed(2)}px,${(-currTY*.6).toFixed(2)}px,0) scale(1.05)`;
      const near=Math.abs(currTX)<.15&&Math.abs(currTY)<.15&&Math.abs(currRX)<.08&&Math.abs(currRY)<.08&&Math.abs(targetTX)<.15&&Math.abs(targetTY)<.15&&Math.abs(targetRX)<.08&&Math.abs(targetRY)<.08;
      if(!near){ rafId=requestAnimationFrame(tick); }
      else{ frame.style.transform='translate3d(0,0,0) rotateX(0) rotateY(0)'; img.style.transform='translate3d(0,0,0) scale(1)'; hero.classList.remove('is-tracking'); if(rafId)cancelAnimationFrame(rafId); rafId=null; }
    }
    function attach(){ if(mq.matches)return; stage.addEventListener('mousemove',onMove); stage.addEventListener('mouseleave',onLeave); }
    function detach(){ stage.removeEventListener('mousemove',onMove); stage.removeEventListener('mouseleave',onLeave); onLeave(); }
    attach();
    mq.addEventListener('change',()=>{ detach(); attach(); });
    window.addEventListener('blur',onLeave);
  })();
})();
</script>

</body>
</html>