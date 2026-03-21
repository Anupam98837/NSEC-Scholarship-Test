{{-- resources/views/auth/login.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login — TechnoHere - Netaji Subhas Engineering College</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

 <style>
  /* =========================
     Namespaced Login (ux-*)
     - Single centered view (no 2-side layout)
     - Form stays perfectly centered
     - LEFT can scroll when needed
     - Scrollbar hidden
     - Safe for all laptop ratios / zoom
     ========================= */

  html, body { height:100%; }
  body.ux-auth-body{
    height:100%;
    overflow:hidden;
    background:var(--bg-body);
    color:var(--text-color);
    font-family:var(--font-sans);
  }

  /* =========================
     GRID (single column)
     ========================= */
  .ux-grid{
    height:100vh;
    height:100svh; /* stable viewport */
    height:100dvh; /* dynamic viewport */
    min-height:100vh;
    min-height:100svh;
    min-height:100dvh;

    display:grid;
    grid-template-columns: 1fr;
    width:100%;
  }

  /* Prevent overflow in weird ratios */
  .ux-left{ min-width:0; }

  /* =========================
     CENTER: form column
     - Centered always (even when scroll)
     - Scrollbar hidden
     ========================= */
  .ux-left{
    height:100vh;
    height:100svh;
    height:100dvh;

    /* center the whole column */
    width:100%;
    max-width: 760px;          /* keeps the page nicely centered on desktop */
    justify-self:center;

    display:flex;
    flex-direction:column;
    align-items:center;

    /* Important: use flex-start + auto margins for TRUE centering + scroll safety */
    justify-content:flex-start;

    padding:clamp(18px,5vw,56px);
    position:relative;
    isolation:isolate;

    /* Scrollable but no scrollbar */
    overflow:auto;
    overscroll-behavior:contain;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:none;        /* Firefox */
    -ms-overflow-style:none;     /* IE/Edge legacy */
  }
  .ux-left::-webkit-scrollbar{ width:0; height:0; }

  /* This pair makes the whole stack (logo+title+form) sit centered,
     but when content is taller, it gracefully scrolls from top */
  .ux-brand{ margin-top:auto; }
  #ux_form{ margin-bottom:auto; }

  .ux-left::before,
  .ux-left::after{
    content:"";
    position:absolute;
    z-index:0;
    pointer-events:none;
    border-radius:50%;
    filter: blur(26px);
    opacity:.25;
    display:block; /* previously only mobile; now single-view so keep it */
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
    height:170px;
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
  .ux-title2{
  font-family:var(--font-head);
  font-weight:700;
  color:var(--ink);
  text-align:center;
 
  font-size:clamp(1.08rem, 1.9vw, 1.35rem);
  margin-bottom: 10px;
  position:relative;
  z-index:1;
  max-width:min(560px, 100%);
}
  .ux-sub{
    text-align:center;
    color:var(--muted-color);
    /* margin-bottom:18px; */
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
    flex-wrap:wrap; /* prevents overlap on tight widths */
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
  }
  .ux-login:hover{
    filter:brightness(.98);
    transform:translateY(-1px);
  }

  /* =========================
     Height tightening (without breaking centering)
     ========================= */
  @media (max-height: 820px){
    .ux-brand img{ height:103px; }
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
  }
  @media (max-height: 600px){
    .ux-title{ font-size:1.3rem; }
    .ux-sub{ font-size:.9rem; }
    .ux-card{ border-radius:14px; }
  }

  /* Mobile fine-tuning */
  @media (max-width: 576px){
    .ux-left{ padding:16px; }
    .ux-brand img{ height:80px; }
    .ux-card{ padding:18px; border-radius:16px; }
    .ux-control{ height:44px; }
    .ux-login{ height:46px; }
  }

  /* =========================
     Animations
     ========================= */
  @keyframes ux-floatA{
    0%,100%{ transform:translate(0,0);}
    50%{ transform:translate(10px, -14px);}
  }
  @keyframes ux-floatB{
    0%,100%{ transform:translate(0,0);}
    50%{ transform:translate(-12px, 10px);}
  }
  @keyframes ux-orbitA{
    0%{transform:translate(0,0);}
    50%{transform:translate(6px, -6px);}
    100%{transform:translate(0,0);}
  }
  @keyframes ux-orbitB{
    0%{transform:translate(0,0);}
    50%{transform:translate(-6px, 6px);}
    100%{transform:translate(0,0);}
  }
  @keyframes ux-chip{
    0%,100%{ transform:translateY(0);}
    50%{ transform:translateY(-6px);}
  }
</style>

</head>
<body class="ux-auth-body">

<div class="ux-grid">
  <!-- CENTER: LOGIN FORM -->
  <section class="ux-left">
    <div class="ux-brand">
      {{-- Put your Unzip Exam logo here --}}
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">TechnoHere</h1>
    <p>
  An Initiative of</p>
   <h1 class="ux-title2">Netaji Subhas Engineering College</h1>

   


    <form class="ux-card" id="ux_form" action="/login" method="post" novalidate>
      {{-- <span class="ux-float-chip">Secure • Token based login</span> --}}
      @csrf

      <!-- Alerts -->
      <div id="ux_alert" class="alert d-none mb-3" role="alert"></div>

      <!-- Email (or phone label — API expects email) -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_id_or_email">Email or Phone Number</label>
        <div class="ux-input-wrap">
          <input id="ux_id_or_email" type="text" class="ux-control form-control" name="identifier"
                 placeholder="you@example.com or 90000 00000" required>
        </div>
      </div>

      <!-- Password -->
      <div class="mb-2">
        <label class="ux-label form-label" for="ux_pw">Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw" type="password" class="ux-control form-control" name="password"
                 placeholder="Enter at least 8+ characters" minlength="8" required>
          <button type="button" class="ux-eye" id="ux_togglePw" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div class="ux-row mb-3">
        <div class="form-check m-0">
          <input class="form-check-input" type="checkbox" id="ux_keep">
          <label class="form-check-label" for="ux_keep">Keep me logged in</label>
        </div>
        <a class="text-decoration-none" href="/forgot-password">Forgot password?</a>
      </div>

      <button class="ux-login" id="ux_btn" type="submit">
        <span class="me-2"><i class="fa-solid fa-right-to-bracket"></i></span> Login
      </button>
      <div class="text-center mt-3">
  <span class="text-muted">Don’t have an account? </span>
  <a href="/register" class="text-decoration-none fw-semibold">Register</a>
</div>
    </form>
  </section>
</div>

<script>
  (function(){
    // ---- CONFIG (uses your Unzip Exam UserController APIs) ----
    const LOGIN_API = "/api/auth/login";
    const CHECK_API = "/api/auth/check";

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ---- DOM ----
    const form    = document.getElementById('ux_form');
    const emailIn = document.getElementById('ux_id_or_email');
    const pwIn    = document.getElementById('ux_pw');
    const keepCb  = document.getElementById('ux_keep');
    const btn     = document.getElementById('ux_btn');
    const alertEl = document.getElementById('ux_alert');
    const toggle  = document.getElementById('ux_togglePw');

    // ---- UI helpers ----
    function setBusy(b){
      btn.disabled = b;
      btn.innerHTML = b
        ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Signing you in…'
        : '<span class="me-2"><i class="fa-solid fa-right-to-bracket"></i></span> Login';
    }
    function showAlert(kind, msg){
      alertEl.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
      alertEl.classList.add(
        'alert',
        kind === 'error'
          ? 'alert-danger'
          : (kind === 'warn' ? 'alert-warning' : 'alert-success')
      );
      alertEl.textContent = msg;
    }
    function clearAlert(){
      alertEl.classList.add('d-none');
      alertEl.textContent = '';
    }

    // ---- Storage helpers (keys EXACTLY "token" and "role") ----
    const authStore = {
      set(token, role, keep){
        sessionStorage.setItem('token', token);
        sessionStorage.setItem('role', role);
        if (keep){
          localStorage.setItem('token', token);
          localStorage.setItem('role', role);
        } else {
          localStorage.removeItem('token');
          localStorage.removeItem('role');
        }
      },
      clear(){
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('role');
        localStorage.removeItem('token');
        localStorage.removeItem('role');
      },
      getLocal(){
        return {
          token: localStorage.getItem('token'),
          role:  localStorage.getItem('role')
        };
      }
    };

    // ---- Build role dashboard path ----
    function rolePath(role){
      const r = (role || '').toString().trim().toLowerCase();
      if(!r) return '/dashboard';
      return `/dashboard`;
    }

    // ---- Password eye toggle ----
    toggle?.addEventListener('click', () => {
      const show = pwIn.type === 'password';
      pwIn.type = show ? 'text' : 'password';
      toggle.innerHTML = show
        ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
        : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
    });

    // ---- Auto-redirect if a remembered token exists (verify via /auth/check) ----
    async function tryAutoLoginFromLocal(){
      const { token, role } = authStore.getLocal();
      if(!token) return;

      try{
        const res = await fetch(CHECK_API, {
          headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json().catch(() => ({}));
        if(res.ok && data && data.user){
          const resolvedRole = (data.user.role || role || '').toString().toLowerCase();
          authStore.set(token, resolvedRole, true);
          window.location.replace(rolePath(resolvedRole));
        } else {
          authStore.clear();
          showAlert('error', data?.message || 'Your session expired. Please log in again.');
        }
      } catch(e){
        // network error -> stay on login page silently
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      tryAutoLoginFromLocal();
    });

    // ---- Handle form submit -> call /api/auth/login ----
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearAlert();

      const identifier = (emailIn.value || '').trim();
      const password   = pwIn.value || '';
      const keep       = !!keepCb.checked;

      if(!identifier || !password){
        showAlert('error','Please enter both email and password.');
        return;
      }

      setBusy(true);
      try{
        const res = await fetch(LOGIN_API, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf
          },
         body: JSON.stringify({ login: identifier, password, remember: keep })
        });

        const data = await res.json().catch(() => ({}));

        if(!res.ok){
          const msg = data?.message || data?.error ||
            (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Unable to log in.');
          showAlert('error', msg);
          setBusy(false);
          return;
        }

        const token = data?.access_token || data?.token || '';
        const role  = (data?.user?.role || localStorage.getItem('role') || 'student').toLowerCase();

        if(!token){
          showAlert('error', 'No token received from server.');
          setBusy(false);
          return;
        }

        authStore.set(token, role, keep);

        showAlert('success', 'Login successful. Redirecting…');
        setTimeout(() => {
          window.location.assign(rolePath(role));
        }, 500);

      } catch(err){
        showAlert('error','Network error. Please try again.');
      } finally {
        setBusy(false);
      }
    });
  })();
</script>
</body>
</html>
