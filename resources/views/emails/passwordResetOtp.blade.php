{{-- resources/views/emails/passwordResetOtp.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Password Reset OTP</title>
  <style>
    body{
      margin:0;
      padding:0;
      background:#f4f6f9;
      font-family:Arial, Helvetica, sans-serif;
    }
    .wrapper{
      max-width:520px;
      margin:40px auto;
      background:#ffffff;
      border-radius:12px;
      overflow:hidden;
      box-shadow:0 4px 24px rgba(0,0,0,.07);
    }
    .header{
      background:linear-gradient(135deg,#14b8a6,#6366f1);
      padding:32px 24px;
      text-align:center;
    }
    .header img{
  display:inline-block;
  border:0;
  outline:none;
  text-decoration:none;
}
    .body{
      padding:32px 28px;
    }
    .label{
      font-size:13px;
      color:#8892a0;
      text-transform:uppercase;
      letter-spacing:.08em;
      margin-bottom:8px;
      text-align:center;
    }
    .otp-wrap{
      text-align:center;
      margin:0 0 28px;
    }
    .otp-box{
      display:inline-block;
      background:#f0fdf9;
      border:2px dashed #14b8a6;
      border-radius:12px;
      padding:16px 36px;
      text-align:center;
      font-size:38px;
      font-weight:700;
      letter-spacing:14px;
      color:#0f766e;
      line-height:1;
    }
    .message{
      font-size:15px;
      color:#374151;
      line-height:1.7;
      margin:0 0 20px;
    }
    .notice{
      background:#fefce8;
      border-left:4px solid #eab308;
      border-radius:6px;
      padding:12px 16px;
      font-size:13px;
      color:#713f12;
      margin-bottom:24px;
    }
    .footer{
      text-align:center;
      padding:18px 24px;
      font-size:12px;
      color:#9ca3af;
      border-top:1px solid #f0f0f0;
    }
  </style>
</head>
<body>
  @php
    $logoPath = public_path('assets/media/images/web/logo.png');
    $logoSrc = (isset($message) && file_exists($logoPath))
        ? $message->embed($logoPath)
        : asset('assets/media/images/web/logo2.jpg');
  @endphp

  <div class="wrapper">
    <div class="header">
      <img src="{{ $logoSrc }}" width="42" height="42" alt="NSEC">
    </div>

    <div class="body">
      <p class="message">
        Hi
        @if(!empty($userEmail))
          <strong>{{ $userEmail }}</strong>
        @endif,
      </p>

      <p class="message">
        We received a request to reset the password for your account.
        Use the OTP below to proceed. It is valid for <strong>10 minutes</strong>.
      </p>

      <p class="label">Your one-time password</p>

      <div class="otp-wrap">
        <span class="otp-box">{{ $otp }}</span>
      </div>

      <div class="notice">
        <strong>Do not share this OTP</strong> with anyone — including our support team.
        If you did not request a password reset, you can safely ignore this email.
      </div>

      <p class="message" style="font-size:13px; color:#6b7280; margin-bottom:0;">
        This OTP will expire at <strong>{{ now()->addMinutes(10)->format('h:i A') }}</strong> (server time).
        If it has expired, please request a new one from the login page.
      </p>
    </div>

    <div class="footer">
      &copy; {{ date('Y') }} NSEC. All rights reserved.<br>
      This is an automated email — please do not reply.
    </div>
  </div>
</body>
</html>