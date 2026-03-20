{{-- resources/views/auth/reset-password.blade.php (Unzip Examination • same UI as login + reset logic) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Reset Password — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    /* =========================
       Namespaced Reset Password (ux-*)
       - Same single centered UI as login
       - Scroll-safe, hidden scrollbar
       ========================= */

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
      /* height:100vh;
      height:100svh;
      height:100dvh; */
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;

      display:grid;
      grid-template-columns:1fr;
      width:100%;
    }

    .ux-left{ min-width:0; }

    .ux-left{
      height:100vh;
      height:100svh;
      height:100dvh;

      width:100%;
      max-width:760px;
      justify-self:center;

      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:flex-start;

      padding:clamp(18px,5vw,56px);
      position:relative;
      isolation:isolate;

      overflow:auto;
      overscroll-behavior:contain;
      -webkit-overflow-scrolling:touch;
      scrollbar-width:none;
      -ms-overflow-style:none;
    }
    .ux-left::-webkit-scrollbar{ width:0; height:0; }

    /* Center stack (logo/title/form) but remain scroll-safe */
    .ux-brand{ margin-top:auto; }
    #rp_form{ margin-bottom:auto; }

    .ux-left::before,
    .ux-left::after{
      content:"";
      position:absolute;
      z-index:0;
      pointer-events:none;
      border-radius:50%;
      filter: blur(26px);
      opacity:.25;
      display:block;
    }
    .ux-left::before{
      width:320px; height:320px;
      left:-80px; top:10%;
      background: radial-gradient(closest-side, #facc15, transparent 70%);
      animation: ux-floatA 9s ease-in-out infinite;
    }
    .ux-left::after{
      width:280px; height:280px;
      right:-60px; bottom:14%;
      background: radial-gradient(closest-side, var(--accent-color), transparent 70%);
      animation: ux-floatB 11s ease-in-out infinite;
    }

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
      font-weight:700;
      color:var(--ink);
      text-align:center;
      font-size:clamp(1.6rem, 2.6vw, 2.2rem);
      margin:.35rem 0 .25rem;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
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
  position: relative;
  z-index: 1;
  background: var(--surface);
  border: 1px solid var(--line-strong);
  border-radius: 18px;
  padding: 24px;
  box-shadow: var(--shadow-2);
  width: 100%;
  max-width: min(430px, 100%);
  height: auto;
  min-height: max-content;
  overflow: visible;
  display: flex;
  flex-direction: column;
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

    .ux-label{ font-weight:600; color:var(--ink); }
    .ux-input-wrap{ position:relative; }

    .ux-control{
      height:46px;
      border-radius:12px;
      padding-right:48px;
      max-width:100%;
    }
    .ux-control::placeholder{ color:#aab2c2; }

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
    }
    .ux-login:hover:not(:disabled){
      filter:brightness(.98);
      transform:translateY(-1px);
    }
    .ux-login:disabled{
      opacity:.65;
      cursor:not-allowed;
      transform:none;
    }

    .ux-secondary{
      width:100%;
      height:46px;
      border-radius:12px;
      border:1px solid var(--line-strong);
      background:transparent;
      font-weight:700;
      color:var(--ink);
      transition:var(--transition);
    }
    .ux-secondary:hover{
      background: rgba(255,255,255,.04);
    }

    /* Captcha */
    .ux-captcha{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      border:1px dashed var(--line-strong);
      border-radius:14px;
      padding:10px 12px;
      background:rgba(0,0,0,.02);
      position:relative;
      z-index:1;
    }
    .ux-captcha-code{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-weight:800;
      letter-spacing:3px;
      padding:8px 12px;
      border-radius:12px;
      border:1px solid var(--line-strong);
      background:rgba(255,255,255,.55);
      user-select:none;
      min-width:140px;
      text-align:center;
    }
    .ux-captcha .btn{
      border-radius:12px;
      border:1px solid var(--line-strong);
    }

    .ux-note{
      font-size:.82rem;
      color:var(--muted-color);
      line-height:1.35;
    }

    /* Hide browser-native password reveal controls */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear { display:none !important; }
    input[type="password"]::-webkit-textfield-decoration-container,
    input[type="password"]::-webkit-password-toggle-button,
    input[type="password"]::-webkit-credentials-auto-fill-button { display:none !important; }
    input[type="password"]::-webkit-textfield-decoration-container { opacity:0 !important; }

    /* Height tightening */
    @media (max-height: 820px){
      .ux-brand img{ height:64px; }
      .ux-sub{ margin-bottom:12px; }
      .ux-card{ padding:20px; }
    }
    @media (max-height: 760px){
      .ux-brand{ margin-bottom:12px; }
      .ux-sub{ margin-bottom:12px; }
      .ux-card{ padding:18px; }
    }
    @media (max-height: 680px){
      .ux-brand img{ height:56px; }
      .ux-title{ font-size:1.45rem; }
      .ux-card{ padding:16px; }
      .ux-control{ height:44px; }
      .ux-login{ height:46px; }
      .ux-secondary{ height:44px; }
    }
    @media (max-height: 600px){
      .ux-title{ font-size:1.3rem; }
      .ux-sub{ font-size:.9rem; }
      .ux-card{ border-radius:14px; }
    }

    /* Mobile fine-tuning */
    @media (max-width: 576px){
      .ux-left{ padding:16px; }
      .ux-brand img{ height:60px; }
      .ux-card{ padding:18px; border-radius:16px; }
      .ux-control{ height:44px; }
      .ux-login{ height:46px; }
      .ux-secondary{ height:44px; }
      .ux-captcha-code{ min-width:118px; letter-spacing:2px; }
    }

    @keyframes ux-floatA{
      0%,100%{ transform:translate(0,0); }
      50%{ transform:translate(10px,-14px); }
    }
    @keyframes ux-floatB{
      0%,100%{ transform:translate(0,0); }
      50%{ transform:translate(-12px,10px); }
    }
    @keyframes ux-orbitA{
      0%{ transform:translate(0,0); }
      50%{ transform:translate(6px,-6px); }
      100%{ transform:translate(0,0); }
    }
    @keyframes ux-orbitB{
      0%{ transform:translate(0,0); }
      50%{ transform:translate(-6px,6px); }
      100%{ transform:translate(0,0); }
    }
  </style>
</head>
<body class="ux-auth-body">

<div class="ux-grid">
  <section class="ux-left">
    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.jpg') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">Create a new password</h1>
    <p class="ux-sub">Use the reset link token to set a new password.</p>

    <form class="ux-card" id="rp_form" action="javascript:void(0)" method="post" novalidate>
      @csrf

      <!-- Alerts -->
      <div id="rp_alert" class="alert d-none mb-3" role="alert"></div>

      <!-- Email -->
      <div class="mb-3">
        <label class="ux-label form-label" for="rp_email">Email address</label>
        <div class="ux-input-wrap">
          <input id="rp_email" type="email" class="ux-control form-control"
                 placeholder="you@example.com" autocomplete="email" required>
        </div>
      </div>

      <!-- New Password -->
      <div class="mb-3">
        <label class="ux-label form-label" for="rp_pw">New password</label>
        <div class="ux-input-wrap">
          <input id="rp_pw" type="password" class="ux-control form-control"
                 placeholder="Enter at least 8+ characters" minlength="8" required>
          <button type="button" class="ux-eye" id="rp_togglePw" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- Confirm Password -->
      <div class="mb-3">
        <label class="ux-label form-label" for="rp_pw2">Confirm new password</label>
        <div class="ux-input-wrap">
          <input id="rp_pw2" type="password" class="ux-control form-control"
                 placeholder="Re-enter password" minlength="8" required>
          <button type="button" class="ux-eye" id="rp_togglePw2" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- Captcha -->
      <div class="mb-3">
        <label class="ux-label form-label" for="rp_captcha_in">Captcha</label>

        <div class="ux-captcha mb-2">
          <div class="ux-captcha-code" id="rp_captcha_code">------</div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="rp_captcha_refresh" aria-label="Refresh captcha">
            <i class="fa-solid fa-rotate"></i>
          </button>
        </div>

        <div class="ux-input-wrap">
          <input id="rp_captcha_in" type="text" class="ux-control form-control"
                 placeholder="Type the captcha shown above" autocomplete="off" required>
        </div>

        <!-- <div class="ux-note mt-2">
          This captcha is front-end only (basic bot protection). For production, use reCAPTCHA/hCaptcha.
        </div> -->
      </div>

      <button class="ux-login" id="rp_btn" type="submit">
        <span class="me-2"><i class="fa-solid fa-lock"></i></span> Reset Password
      </button>

      <button class="ux-secondary mt-2" type="button" id="rp_back">
        <i class="fa-solid fa-arrow-left me-2"></i> Back to Login
      </button>
    </form>
  </section>
</div>

<script>
(function(){
  const RESET_API  = "/api/auth/forgot-password/reset";
  const LOGIN_PAGE = "/";

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const form        = document.getElementById('rp_form');
  const emailIn     = document.getElementById('rp_email');
  const pwIn        = document.getElementById('rp_pw');
  const pw2In       = document.getElementById('rp_pw2');
  const capCodeEl   = document.getElementById('rp_captcha_code');
  const capIn       = document.getElementById('rp_captcha_in');
  const capRefresh  = document.getElementById('rp_captcha_refresh');
  const btn         = document.getElementById('rp_btn');
  const alertEl     = document.getElementById('rp_alert');
  const backBtn     = document.getElementById('rp_back');
  const t1          = document.getElementById('rp_togglePw');
  const t2          = document.getElementById('rp_togglePw2');

  // Read token + email from URL: /reset-password?token=...&email=...
  const urlParams = new URLSearchParams(window.location.search);
  const urlToken  = (urlParams.get('token') || '').trim();
  const urlEmail  = (urlParams.get('email') || '').trim().toLowerCase();

  function setBusy(b){
    btn.disabled = b;
    btn.innerHTML = b
      ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Resetting…'
      : '<span class="me-2"><i class="fa-solid fa-lock"></i></span> Reset Password';
  }

  function showAlert(kind, msg){
    alertEl.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
    alertEl.classList.add(
      'alert',
      kind === 'error' ? 'alert-danger' : (kind === 'warn' ? 'alert-warning' : 'alert-success')
    );
    alertEl.textContent = msg;
  }

  function clearAlert(){
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  // Password visibility toggles
  t1?.addEventListener('click', () => {
    const show = pwIn.type === 'password';
    pwIn.type = show ? 'text' : 'password';
    t1.innerHTML = show
      ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
      : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
  });

  t2?.addEventListener('click', () => {
    const show = pw2In.type === 'password';
    pw2In.type = show ? 'text' : 'password';
    t2.innerHTML = show
      ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
      : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
  });

  backBtn?.addEventListener('click', () => {
    window.location.href = LOGIN_PAGE;
  });

  // Basic frontend captcha
  function genCaptcha(){
    const alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    let out = "";
    for (let i = 0; i < 6; i++) {
      out += alphabet[Math.floor(Math.random() * alphabet.length)];
    }
    sessionStorage.setItem('rp_captcha', out);
    capCodeEl.textContent = out;
  }

  capRefresh?.addEventListener('click', genCaptcha);

  document.addEventListener('DOMContentLoaded', () => {
    genCaptcha();

    // Prefill email from URL (fallback: forgot password page session cache)
    if (urlEmail) {
      emailIn.value = urlEmail;
      emailIn.readOnly = true;
    } else {
      const savedEmail = sessionStorage.getItem('fp_email');
      if (savedEmail) emailIn.value = savedEmail;
    }

    if (!urlToken) {
      showAlert('warn', 'Reset token missing in URL. Please request a new password reset link.');
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    const email = (emailIn.value || '').trim().toLowerCase();
    const p1    = pwIn.value || '';
    const p2    = pw2In.value || '';
    const cap   = (capIn.value || '').trim().toUpperCase();

    const expectedCap = (sessionStorage.getItem('rp_captcha') || '').toUpperCase();

    if (!email) {
      showAlert('error', 'Please enter your email.');
      emailIn.focus();
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAlert('error', 'Please enter a valid email address.');
      emailIn.focus();
      return;
    }

    if (!p1 || p1.length < 8) {
      showAlert('error', 'Password must be at least 8 characters.');
      pwIn.focus();
      return;
    }

    if (p1 !== p2) {
      showAlert('error', 'Password and confirm password do not match.');
      pw2In.focus();
      return;
    }

    if (!cap) {
      showAlert('error', 'Please enter captcha.');
      capIn.focus();
      return;
    }

    if (!expectedCap || cap !== expectedCap) {
      showAlert('error', 'Captcha does not match. Please try again.');
      genCaptcha();
      capIn.value = '';
      capIn.focus();
      return;
    }

    if (!urlToken) {
      showAlert('error', 'Reset token missing. Please request a new reset link.');
      return;
    }

    setBusy(true);

    try {
      const res = await fetch(RESET_API, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          email: email,
          token: urlToken,
          password: p1,
          password_confirmation: p2
        })
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        const msg = data?.message || data?.error ||
          (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Unable to reset password.');
        showAlert('error', msg);
        genCaptcha();
        capIn.value = '';
        return;
      }

      // Clear old/reset-related temp keys
      sessionStorage.removeItem('fp_reset_token');
      sessionStorage.removeItem('fp_otp');
      sessionStorage.removeItem('fp_request_id');
      sessionStorage.removeItem('fp_expires_in_minutes');
      sessionStorage.removeItem('fp_email');
      sessionStorage.removeItem('rp_captcha');

      showAlert('success', data?.message || 'Password reset successful. Redirecting to login…');

      setTimeout(() => {
        window.location.assign(LOGIN_PAGE);
      }, 900);

    } catch (err) {
      showAlert('error', 'Network error. Please try again.');
    } finally {
      setBusy(false);
    }
  });
})();
</script>
</body>
</html>