@php
  $brandName = config('app.name', 'TechnoHere');
  $initiativeName = config('app.initiative_name', '');
  $brandTitle = $initiativeName !== '' ? "{$brandName} - {$initiativeName}" : $brandName;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login with OTP — {{ $brandTitle }}</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    html, body { min-height:100%; }
    body.ux-auth-body{
      min-height:100vh;
      overflow:auto;
      background:var(--bg-body);
      color:var(--text-color);
      font-family:var(--font-sans);
    }
    .ux-grid{
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;
      display:grid;
      grid-template-columns:1fr;
      width:100%;
    }
    .ux-left{
      min-width:0;
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
      overflow:visible;
    }
    .ux-brand{ margin-top:0; }
    #ux_form{ margin-bottom:0; width:100%; }
    .ux-left::before,
    .ux-left::after{
      content:"";
      position:absolute;
      z-index:0;
      pointer-events:none;
      border-radius:50%;
      filter:blur(26px);
      opacity:.25;
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
      margin-bottom:10px;
      position:relative;
      z-index:1;
      max-width:100%;
      flex-shrink:0;
    }
    .ux-brand img{
      height:96px;
      width:auto;
      max-width:min(220px, 72vw);
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
      margin-bottom:10px;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
    }
    .ux-sub{
      text-align:center;
      color:var(--muted-color);
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
      max-width:min(460px, 100%);
      overflow:visible;
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
    }
    .ux-card::after{
      width:140px; height:140px;
      right:-30px; bottom:-30px;
      background: radial-gradient(closest-side, var(--primary-color), transparent 65%);
    }
    .ux-label{ font-weight:600; color:var(--ink); }
    .ux-input-wrap{ position:relative; }
    .ux-control{
      height:46px;
      border-radius:12px;
      max-width:100%;
    }
    .ux-control::placeholder{ color:#aab2c2; }
    .ux-send{
      border:none;
      border-radius:12px;
      height:46px;
      padding:0 18px;
      font-weight:700;
      color:#fff;
      background:linear-gradient(180deg, color-mix(in oklab, var(--primary-color) 92%, #fff 8%), var(--primary-color));
      box-shadow:0 10px 22px rgba(20,184,166,.26);
      transition:var(--transition);
      white-space:nowrap;
    }
    .ux-send:disabled{ opacity:.7; cursor:not-allowed; }
    .ux-send:hover:not(:disabled){ filter:brightness(.98); transform:translateY(-1px); }
    .ux-login{
      width:100%;
      height:48px;
      border:none;
      border-radius:12px;
      font-weight:700;
      color:#fff;
      background:linear-gradient(180deg, color-mix(in oklab, var(--secondary-color) 90%, #fff 10%), var(--secondary-color));
      transition:var(--transition);
    }
    .ux-login:hover{ filter:brightness(.98); transform:translateY(-1px); }
    .ux-login:disabled{ opacity:.7; cursor:not-allowed; }
    .ux-stack{
      display:grid;
      gap:10px;
    }
    .ux-captcha-row{
      display:grid;
      grid-template-columns:140px minmax(0, 1fr) auto;
      gap:10px;
      align-items:center;
    }
    .ux-captcha-code{
      height:46px;
      border-radius:12px;
      border:1px dashed var(--line-strong);
      background:linear-gradient(135deg, rgba(20,184,166,.09), rgba(250,204,21,.12));
      display:flex;
      align-items:center;
      justify-content:center;
      user-select:none;
      cursor:pointer;
      transition:var(--transition);
      padding:4px 8px;
      overflow:hidden;
    }
    .ux-captcha-code canvas{
      width:100%;
      height:100%;
      display:block;
    }
    .ux-captcha-code:hover{
      filter:brightness(.98);
      transform:translateY(-1px);
    }
    .ux-otp-row{
      display:flex;
      gap:8px;
      justify-content:center;
      margin-bottom:4px;
    }
    .ux-otp-box{
      width:48px;
      height:54px;
      border:1px solid var(--line-strong);
      border-radius:12px;
      text-align:center;
      font-size:1.3rem;
      font-weight:700;
      color:var(--ink);
      background:#fff;
      outline:none;
      transition:var(--transition);
    }
    .ux-otp-box:focus{
      border-color:var(--primary-color);
      box-shadow:0 0 0 3px rgba(20,184,166,.15);
    }
    .ux-otp-box.ux-filled{ border-color:var(--primary-color); }
    .ux-helper{
      font-size:.92rem;
      color:var(--muted-color);
    }
    .ux-timer{
      font-size:.92rem;
      color:var(--secondary-color);
      font-weight:600;
    }
    .ux-link-btn{
      padding:0;
      border:none;
      background:transparent;
      color:var(--primary-color);
      font-weight:600;
      text-decoration:none;
    }
    .ux-link-btn:disabled{
      color:var(--muted-color);
      opacity:.7;
    }
    @media (max-width: 576px){
      .ux-left{ padding:16px; }
      .ux-brand img{ height:68px; }
      .ux-card{ padding:18px; border-radius:16px; }
      .ux-control{ height:44px; }
      .ux-send{ height:44px; width:100%; }
      .ux-captcha-row{ grid-template-columns:1fr; }
      .ux-captcha-code{ height:44px; }
      .ux-otp-box{ width:40px; height:50px; font-size:1.15rem; }
    }
    @keyframes ux-floatA{
      0%,100%{ transform:translate(0,0); }
      50%{ transform:translate(10px, -14px); }
    }
    @keyframes ux-floatB{
      0%,100%{ transform:translate(0,0); }
      50%{ transform:translate(-12px, 10px); }
    }
  </style>
</head>
<body class="ux-auth-body">

<div class="ux-grid">
  <section class="ux-left">
    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">{{ $brandName }}</h1>
    @if($initiativeName !== '')
      <p>An Initiative of</p>
      <h1 class="ux-title2">{{ $initiativeName }}</h1>
    @endif
    <p class="ux-sub">Login with your mobile number and OTP.</p>

    <form class="ux-card" id="ux_form" novalidate>
      @csrf

      <div id="ux_alert" class="alert d-none mb-3" role="alert"></div>

      <div class="mb-3">
        <label class="ux-label form-label" for="ux_phone">Mobile Number</label>
        <div class="ux-stack">
          <div class="ux-input-wrap">
            <input id="ux_phone" type="tel" class="ux-control form-control" inputmode="numeric" maxlength="10"
                   placeholder="Enter 10 digit mobile number" autocomplete="tel-national" required>
          </div>
          <div>
            <label class="ux-label form-label" for="ux_captcha">Enter Captcha</label>
            <div class="ux-captcha-row">
              <div class="ux-captcha-code" id="ux_captchaCode" role="button" tabindex="0" aria-label="Refresh captcha">
                <canvas id="ux_captchaCanvas" width="140" height="46" aria-hidden="true"></canvas>
              </div>
              <input id="ux_captcha" type="text" class="ux-control form-control" maxlength="6"
                     placeholder="Type captcha" autocomplete="off" required>
              <button class="ux-send" id="ux_sendOtpBtn" type="button">
                <span class="me-2"><i class="fa-solid fa-paper-plane"></i></span> OTP
              </button>
            </div>
            <div class="ux-helper mt-2">Click the captcha to refresh it.</div>
          </div>
        </div>
        <div class="ux-helper mt-2" id="ux_statusText">We’ll send a one-time password to this mobile number.</div>
        <div class="ux-timer mt-1 d-none" id="ux_timerText"></div>
      </div>

      <div class="mb-3 d-none" id="ux_otpBlock">
        <label class="ux-label form-label">Enter OTP</label>
        <div class="ux-otp-row" id="ux_otpRow">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="0" autocomplete="one-time-code">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="1" autocomplete="off">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="2" autocomplete="off">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="3" autocomplete="off">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="4" autocomplete="off">
          <input class="ux-otp-box" type="text" inputmode="numeric" maxlength="1" data-idx="5" autocomplete="off">
        </div>
        <div class="text-center mt-2">
          <button type="button" class="ux-link-btn" id="ux_resendBtn">Resend OTP</button>
        </div>
      </div>

      <button class="ux-login" id="ux_btn" type="submit">
        <span class="me-2"><i class="fa-solid fa-mobile-screen-button"></i></span> Login with OTP
      </button>

      <div class="text-center mt-3">
        <a href="/register" class="text-decoration-none">Register</a>
      </div>
    </form>
  </section>
</div>

<script>
  (function(){
    const SEND_OTP_API = '/api/auth/login-otp/send';
    const STATUS_API   = '/api/auth/login-otp/status';
    const VERIFY_API   = '/api/auth/login-otp/verify';
    const CHECK_API    = '/api/auth/check';
    const PHONE_KEY    = 'otp_login_phone';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const form        = document.getElementById('ux_form');
    const phoneIn     = document.getElementById('ux_phone');
    const captchaIn   = document.getElementById('ux_captcha');
    const captchaCode = document.getElementById('ux_captchaCode');
    const captchaCanvas = document.getElementById('ux_captchaCanvas');
    const sendBtn     = document.getElementById('ux_sendOtpBtn');
    const resendBtn   = document.getElementById('ux_resendBtn');
    const loginBtn    = document.getElementById('ux_btn');
    const alertEl     = document.getElementById('ux_alert');
    const statusText  = document.getElementById('ux_statusText');
    const timerText   = document.getElementById('ux_timerText');
    const otpBlock    = document.getElementById('ux_otpBlock');
    const otpBoxes    = document.querySelectorAll('.ux-otp-box');

    let resendInterval = null;
    let syncTimeout = null;
    let currentCaptcha = '';

    function normalizePhone(v){ return String(v || '').replace(/\D/g, '').slice(0, 10); }
    function normalizeCaptcha(v){ return String(v || '').replace(/[^a-zA-Z0-9]/g, '').slice(0, 6).toUpperCase(); }
    function formatCountdown(secs){
      secs = Math.max(0, Math.ceil(Number(secs || 0)));
      const mins = Math.floor(secs / 60);
      const seconds = secs % 60;
      return mins > 0
        ? `${mins}m ${String(seconds).padStart(2, '0')}s`
        : `${seconds}s`;
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
    function generateCaptcha(){
      const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      currentCaptcha = Array.from({ length: 5 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
      captchaIn.value = '';
      drawCaptcha(currentCaptcha);
    }
    function drawCaptcha(text){
      if (!captchaCanvas) return;

      const ctx = captchaCanvas.getContext('2d');
      const width = captchaCanvas.width;
      const height = captchaCanvas.height;

      ctx.clearRect(0, 0, width, height);

      const bg = ctx.createLinearGradient(0, 0, width, height);
      bg.addColorStop(0, 'rgba(255,255,255,0.92)');
      bg.addColorStop(1, 'rgba(240,253,250,0.98)');
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, width, height);

      for (let i = 0; i < 18; i++) {
        ctx.fillStyle = `rgba(${20 + Math.floor(Math.random() * 80)}, ${140 + Math.floor(Math.random() * 70)}, ${120 + Math.floor(Math.random() * 80)}, 0.08)`;
        ctx.beginPath();
        ctx.arc(
          Math.random() * width,
          Math.random() * height,
          1 + Math.random() * 2,
          0,
          Math.PI * 2
        );
        ctx.fill();
      }

      for (let i = 0; i < 4; i++) {
        ctx.strokeStyle = `rgba(${50 + Math.floor(Math.random() * 120)}, ${120 + Math.floor(Math.random() * 80)}, ${90 + Math.floor(Math.random() * 90)}, 0.35)`;
        ctx.lineWidth = 1 + Math.random() * 1.2;
        ctx.beginPath();
        ctx.moveTo(0, 8 + Math.random() * (height - 16));
        ctx.bezierCurveTo(
          width * 0.3, Math.random() * height,
          width * 0.6, Math.random() * height,
          width, 8 + Math.random() * (height - 16)
        );
        ctx.stroke();
      }

      const charsArr = text.split('');
      charsArr.forEach((char, index) => {
        const x = 16 + index * 22 + (Math.random() * 4 - 2);
        const y = 28 + (Math.random() * 10 - 5);
        const angle = (Math.random() * 0.6) - 0.3;

        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.font = `${700 + Math.floor(Math.random() * 200)} ${22 + Math.floor(Math.random() * 4)}px Georgia`;
        ctx.fillStyle = ['#0f766e', '#155e75', '#854d0e', '#1f2937'][Math.floor(Math.random() * 4)];
        ctx.fillText(char, 0, 0);
        ctx.restore();
      });

      for (let i = 0; i < 2; i++) {
        ctx.strokeStyle = `rgba(31, 41, 55, ${0.18 + Math.random() * 0.12})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(Math.random() * 20, Math.random() * height);
        ctx.lineTo(width - Math.random() * 20, Math.random() * height);
        ctx.stroke();
      }
    }
    function getOtp(){
      return Array.from(otpBoxes).map(box => box.value).join('');
    }
    function clearOtpBoxes(){
      otpBoxes.forEach(box => {
        box.value = '';
        box.classList.remove('ux-filled');
      });
    }
    function setOtpVisible(show){
      otpBlock.classList.toggle('d-none', !show);
    }
    function setSendBusy(busy, label){
      sendBtn.disabled = busy;
      sendBtn.innerHTML = busy
        ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Please wait...'
        : label;
    }
    function setLoginBusy(busy){
      loginBtn.disabled = busy;
      loginBtn.innerHTML = busy
        ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Signing you in...'
        : '<span class="me-2"><i class="fa-solid fa-mobile-screen-button"></i></span> Login with OTP';
    }
    function authStoreSet(token, role){
      sessionStorage.setItem('token', token);
      sessionStorage.setItem('role', role);
      localStorage.setItem('token', token);
      localStorage.setItem('role', role);
    }
    function authStoreClear(){
      sessionStorage.removeItem('token');
      sessionStorage.removeItem('role');
      localStorage.removeItem('token');
      localStorage.removeItem('role');
    }
    function rolePath(){
      return '/dashboard';
    }
    function applyIdleState(hasActiveOtp){
      clearInterval(resendInterval);
      resendInterval = null;
      timerText.classList.add('d-none');
      timerText.textContent = '';
      sendBtn.disabled = false;
      resendBtn.disabled = false;
      sendBtn.innerHTML = hasActiveOtp
        ? '<span class="me-2"><i class="fa-solid fa-rotate-right"></i></span> Resend'
        : '<span class="me-2"><i class="fa-solid fa-paper-plane"></i></span> OTP';
    }
    function startCountdown(seconds, message){
      clearInterval(resendInterval);
      resendInterval = null;
      seconds = Math.max(0, Math.ceil(Number(seconds || 0)));

      if (!seconds || seconds <= 0){
        timerText.classList.add('d-none');
        timerText.textContent = '';
        statusText.textContent = message || 'You can request a new OTP now.';
        applyIdleState(true);
        return;
      }

      let secs = seconds;
      sendBtn.disabled = true;
      resendBtn.disabled = true;
      timerText.classList.remove('d-none');
      statusText.textContent = message || 'OTP already sent. Please wait before requesting another one.';
      timerText.textContent = `Resend available in ${formatCountdown(secs)}`;
      sendBtn.innerHTML = `<span class="me-2"><i class="fa-regular fa-clock"></i></span> ${formatCountdown(secs)}`;

      resendInterval = setInterval(() => {
        secs -= 1;
        if (secs <= 0){
          clearInterval(resendInterval);
          resendInterval = null;
          timerText.classList.add('d-none');
          timerText.textContent = '';
          statusText.textContent = 'You can request a new OTP now.';
          applyIdleState(true);
          return;
        }

        timerText.textContent = `Resend available in ${formatCountdown(secs)}`;
        sendBtn.innerHTML = `<span class="me-2"><i class="fa-regular fa-clock"></i></span> ${formatCountdown(secs)}`;
      }, 1000);
    }
    function applyStatus(data, messageOverride){
      const hasActiveOtp = !!data?.has_active_otp || !!data?.attempt_count;
      setOtpVisible(hasActiveOtp);

      if (hasActiveOtp){
        if (data?.wait_seconds > 0){
          startCountdown(data.wait_seconds, messageOverride || 'OTP already sent. Please wait before requesting another one.');
        } else {
          applyIdleState(true);
          statusText.textContent = messageOverride || (data?.has_active_otp
            ? 'OTP sent. Enter it below or request a new one.'
            : 'You can request an OTP for this number.');
        }
      } else {
        applyIdleState(false);
        statusText.textContent = messageOverride || 'We’ll send a one-time password to this mobile number.';
      }
    }
    async function apiPost(url, body){
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(body)
      });

      const data = await res.json().catch(() => ({}));
      return { res, data };
    }
    async function tryAutoLoginFromLocal(){
      const token = localStorage.getItem('token');
      const role  = localStorage.getItem('role');
      if (!token) return false;

      try{
        const res = await fetch(CHECK_API, {
          headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json().catch(() => ({}));

        if (res.ok && data?.user){
          authStoreSet(token, (data.user.role || role || 'student').toLowerCase());
          window.location.replace(rolePath());
          return true;
        }
      } catch(e){
      }

      authStoreClear();
      return false;
    }
    async function syncStatus(phone, silent){
      const normalized = normalizePhone(phone);
      if (normalized.length !== 10){
        applyStatus({ has_active_otp: false, attempt_count: 0 }, 'We’ll send a one-time password to this mobile number.');
        setOtpVisible(false);
        return;
      }

      try{
        const { res, data } = await apiPost(STATUS_API, { phone_number: normalized });
        if (!res.ok){
          if (!silent) showAlert('warn', data?.message || 'Unable to check OTP status right now.');
          return;
        }
        applyStatus(data);
      } catch(e){
        if (!silent) showAlert('error', 'Unable to sync OTP timer right now.');
      }
    }

    otpBoxes.forEach((box, i) => {
      box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        box.classList.toggle('ux-filled', box.value !== '');
        if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
      });

      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && i > 0) otpBoxes[i - 1].focus();
        if (e.key === 'Enter') form.requestSubmit();
      });

      box.addEventListener('paste', (e) => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((digit, idx) => {
          if (otpBoxes[idx]) {
            otpBoxes[idx].value = digit;
            otpBoxes[idx].classList.add('ux-filled');
          }
        });
      });
    });

    phoneIn.addEventListener('input', () => {
      phoneIn.value = normalizePhone(phoneIn.value);
      const phone = phoneIn.value;

      if (phone) localStorage.setItem(PHONE_KEY, phone);
      else localStorage.removeItem(PHONE_KEY);

      clearTimeout(syncTimeout);
      syncTimeout = setTimeout(() => {
        if (phone.length === 10){
          syncStatus(phone, true);
        } else {
          clearOtpBoxes();
          setOtpVisible(false);
          applyStatus({ has_active_otp: false, attempt_count: 0 }, 'We’ll send a one-time password to this mobile number.');
        }
      }, 250);
    });
    captchaIn.addEventListener('input', () => {
      captchaIn.value = normalizeCaptcha(captchaIn.value);
    });
    captchaCode.addEventListener('click', () => {
      generateCaptcha();
      captchaIn.focus();
    });
    captchaCode.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      generateCaptcha();
      captchaIn.focus();
    });

    async function requestOtp(){
      clearAlert();
      const phone = normalizePhone(phoneIn.value);
      const captcha = normalizeCaptcha(captchaIn.value);
      let stateHandled = false;

      if (phone.length !== 10){
        showAlert('warn', 'Please enter a valid 10 digit mobile number.');
        phoneIn.focus();
        return;
      }
      if (!captcha){
        showAlert('warn', 'Please enter the captcha first.');
        captchaIn.focus();
        return;
      }
      if (captcha !== currentCaptcha){
        showAlert('warn', 'Captcha does not match. Please try again.');
        generateCaptcha();
        captchaIn.focus();
        return;
      }

      localStorage.setItem(PHONE_KEY, phone);
      setSendBusy(true, '');

      try{
        const { res, data } = await apiPost(SEND_OTP_API, { phone_number: phone });

        if (!res.ok){
          if (res.status === 429){
            applyStatus(data, data?.message || 'Please wait before requesting another OTP.');
          } else {
            applyStatus({ has_active_otp: false, attempt_count: 0 }, 'We’ll send a one-time password to this mobile number.');
          }
          stateHandled = true;
          showAlert(res.status === 429 ? 'warn' : 'error', data?.message || 'Unable to send OTP.');
          return;
        }

        clearOtpBoxes();
        setOtpVisible(true);
        applyStatus(data, data?.message || 'OTP sent successfully.');
        generateCaptcha();
        stateHandled = true;
        showAlert(data?.status === 'warning' ? 'warn' : 'success', data?.message || 'OTP sent successfully.');
        otpBoxes[0]?.focus();
      } catch(e){
        applyStatus({ has_active_otp: false, attempt_count: 0 }, 'We’ll send a one-time password to this mobile number.');
        generateCaptcha();
        stateHandled = true;
        showAlert('error', 'Network error while sending OTP.');
      } finally {
        if (!stateHandled){
          applyIdleState(false);
        }
      }
    }

    sendBtn.addEventListener('click', requestOtp);
    resendBtn.addEventListener('click', requestOtp);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearAlert();

      const phone = normalizePhone(phoneIn.value);
      const otp   = getOtp();

      if (phone.length !== 10){
        showAlert('warn', 'Please enter a valid 10 digit mobile number.');
        phoneIn.focus();
        return;
      }

      if (otp.length !== 6){
        showAlert('warn', 'Please enter the 6 digit OTP.');
        otpBoxes[Math.min(otp.length, otpBoxes.length - 1)]?.focus();
        return;
      }

      setLoginBusy(true);

      try{
        const { res, data } = await apiPost(VERIFY_API, {
          phone_number: phone,
          otp,
          remember: true
        });

        if (!res.ok){
          showAlert('error', data?.message || 'OTP login failed.');
          if ((data?.message || '').toLowerCase().includes('otp')){
            clearOtpBoxes();
            otpBoxes[0]?.focus();
          }
          if (data?.wait_seconds || data?.has_active_otp){
            applyStatus(data);
          }
          return;
        }

        const token = data?.access_token || '';
        const role  = (data?.user?.role || 'student').toLowerCase();

        if (!token){
          showAlert('error', 'No token received from server.');
          return;
        }

        authStoreSet(token, role);
        localStorage.removeItem(PHONE_KEY);
        showAlert('success', 'Login successful. Redirecting...');
        setTimeout(() => window.location.assign(rolePath()), 400);
      } catch(e){
        showAlert('error', 'Network error. Please try again.');
      } finally {
        setLoginBusy(false);
      }
    });

    document.addEventListener('DOMContentLoaded', async () => {
      generateCaptcha();
      const redirected = await tryAutoLoginFromLocal();
      if (redirected) return;

      const savedPhone = normalizePhone(localStorage.getItem(PHONE_KEY) || '');
      if (savedPhone){
        phoneIn.value = savedPhone;
        syncStatus(savedPhone, true);
      } else {
        applyStatus({ has_active_otp: false, attempt_count: 0 }, 'We’ll send a one-time password to this mobile number.');
      }
    });
  })();
</script>
</body>
</html>
