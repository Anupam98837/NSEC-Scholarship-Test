{{-- resources/views/auth/forgot-password.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Forgot Password — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    /* =========================
       Namespaced Forgot Password (ux-*)
       - Single centered view (same as login)
       - Scroll-safe and hidden scrollbar
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
      height:100vh;
      height:100svh;
      height:100dvh;
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

    .ux-brand{ margin-top:auto; }
    .ux-form-shell{ margin-bottom:auto; width:100%; display:flex; justify-content:center; }

    .ux-left::before,
    .ux-left::after{
      content:"";
      position:absolute;
      z-index:0;
      pointer-events:none;
      border-radius:50%;
      filter:blur(26px);
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
      position:relative;
      z-index:1;
      background:var(--surface);
      border:1px solid var(--line-strong);
      border-radius:18px;
      padding:24px;
      box-shadow:var(--shadow-2);
      width:100%;
      max-width:min(430px, 100%);
      overflow:hidden;
    }
    .ux-card::before,
    .ux-card::after{
      content:"";
      position:absolute;
      border-radius:50%;
      filter:blur(18px);
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
      max-width:100%;
    }
    .ux-control::placeholder{ color:#aab2c2; }

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

    .ux-notice{
      display:flex;
      align-items:flex-start;
      gap:10px;
      background: rgba(99,102,241,.07);
      border:1px solid rgba(99,102,241,.2);
      border-radius:12px;
      padding:11px 14px;
      font-size:.85rem;
      color:var(--muted-color);
      margin-bottom:18px;
      position:relative;
      z-index:1;
    }
    .ux-notice i{
      margin-top:2px;
      color:var(--primary-color);
      flex-shrink:0;
    }

    .ux-success-state{
      text-align:center;
      padding:6px 0 2px;
      position:relative;
      z-index:1;
    }
    .ux-success-icon{
      font-size:2.7rem;
      color:#27ae60;
      margin-bottom:10px;
      display:block;
    }
    .ux-success-state p{
      color:var(--muted-color);
      font-size:.9rem;
      margin-bottom:0;
    }

    .ux-muted-note{
      font-size:.82rem;
      color:var(--muted-color);
    }

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

    @media (max-width: 576px){
      .ux-left{ padding:16px; }
      .ux-brand img{ height:60px; }
      .ux-card{ padding:18px; border-radius:16px; }
      .ux-control{ height:44px; }
      .ux-login{ height:46px; }
      .ux-secondary{ height:44px; }
    }

    /* Animations */
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
    @keyframes ux-chip{
      0%,100%{ transform:translateY(0); }
      50%{ transform:translateY(-6px); }
    }
  </style>
</head>

<body class="ux-auth-body">
<div class="ux-grid">
  <section class="ux-left">

    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.jpg') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">Forgot your password?</h1>
    <p class="ux-sub">Enter your email and we’ll send you a reset link.</p>

    <div class="ux-form-shell">
      <div class="ux-card">
        {{-- <span class="ux-float-chip">Secure • 10 min reset link</span> --}}

        {{-- Alert --}}
        <div id="fp_alert" class="alert d-none mb-3" role="alert"></div>

        {{-- SUCCESS STATE --}}
        <div id="fp_success_state" class="ux-success-state d-none">
          <i class="fa-solid fa-circle-check ux-success-icon"></i>
          <h6 class="fw-bold mb-2">Check your inbox</h6>
          <p>
            If this email exists in our system, a password reset link has been sent.
            <br><strong>The link is valid for 10 minutes.</strong>
          </p>

          <hr class="my-3">

          <p class="ux-muted-note mb-3">
            Didn’t receive it? Check your spam folder or try again below.
          </p>

          <button type="button" class="ux-secondary" id="fp_try_again">
            <i class="fa-solid fa-rotate-left me-2"></i> Try Again
          </button>

          <button type="button" class="ux-secondary mt-2" id="fp_back_success">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Login
          </button>
        </div>

        {{-- FORM STATE --}}
        <form id="fp_form" action="javascript:void(0)" method="post" novalidate>
          @csrf

          <div class="ux-notice">
            <i class="fa-solid fa-circle-info fa-sm"></i>
            <span>
              Enter your registered email. A secure reset link valid for
              <strong>10 minutes</strong> will be sent to your inbox.
            </span>
          </div>

          <div class="mb-4">
            <label class="ux-label form-label" for="fp_email">Email address</label>
            <div class="ux-input-wrap">
              <input
                id="fp_email"
                type="email"
                class="ux-control form-control"
                placeholder="you@example.com"
                autocomplete="email"
                required
                autofocus
              >
            </div>
          </div>

          <button class="ux-login" id="fp_btn" type="submit">
            <span class="me-2"><i class="fa-solid fa-paper-plane"></i></span>
            Send Reset Link
          </button>

          <button class="ux-secondary mt-2" type="button" id="fp_back">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Login
          </button>
        </form>

      </div>
    </div>

  </section>
</div>

<script>
(function () {
  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Config
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  const SEND_LINK_API = '/api/auth/forgot-password/send-link';
  const LOGIN_PAGE    = '/';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | DOM refs
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  const form         = document.getElementById('fp_form');
  const emailIn      = document.getElementById('fp_email');
  const btn          = document.getElementById('fp_btn');
  const alertEl      = document.getElementById('fp_alert');
  const backBtn      = document.getElementById('fp_back');
  const successState = document.getElementById('fp_success_state');
  const tryAgainBtn  = document.getElementById('fp_try_again');
  const backSuccBtn  = document.getElementById('fp_back_success');

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Helpers
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  function setBusy(b) {
    btn.disabled = b;
    btn.innerHTML = b
      ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Sending…'
      : '<span class="me-2"><i class="fa-solid fa-paper-plane"></i></span> Send Reset Link';
  }

  function showAlert(kind, msg) {
    alertEl.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
    alertEl.classList.add(
      'alert',
      kind === 'error' ? 'alert-danger' :
      kind === 'warn'  ? 'alert-warning' : 'alert-success'
    );
    alertEl.textContent = msg;
  }

  function clearAlert() {
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function showSuccess() {
    form.classList.add('d-none');
    alertEl.classList.add('d-none');
    successState.classList.remove('d-none');
    sessionStorage.setItem('fp_email', (emailIn.value || '').trim().toLowerCase());
  }

  function resetToForm() {
    successState.classList.add('d-none');
    form.classList.remove('d-none');
    emailIn.disabled = false;
    emailIn.value = '';
    clearAlert();
    setBusy(false);
    emailIn.focus();
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Events
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  backBtn?.addEventListener('click',     () => window.location.href = LOGIN_PAGE);
  backSuccBtn?.addEventListener('click', () => window.location.href = LOGIN_PAGE);
  tryAgainBtn?.addEventListener('click', resetToForm);

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   | Form submit
   ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    const email = (emailIn.value || '').trim().toLowerCase();

    if (!email) {
      showAlert('error', 'Please enter your email address.');
      emailIn.focus();
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAlert('error', 'Please enter a valid email address.');
      emailIn.focus();
      return;
    }

    setBusy(true);
    emailIn.disabled = true;

    try {
      const res = await fetch(SEND_LINK_API, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ email }),
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        const msg = data?.message
          || (data?.errors ? Object.values(data.errors).flat().join(', ') : null)
          || 'Something went wrong. Please try again.';
        showAlert('error', msg);
        emailIn.disabled = false;
        return;
      }

      // Always show generic success to avoid email enumeration
      showSuccess();

    } catch (e) {
      showAlert('error', 'Network error. Please check your connection and try again.');
      emailIn.disabled = false;
    } finally {
      setBusy(false);
    }
  });

})();
</script>
</body>
</html>