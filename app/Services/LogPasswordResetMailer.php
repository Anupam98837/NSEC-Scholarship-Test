<?php
// app/Services/LogPasswordResetMailer.php

namespace App\Services;

use App\Contracts\PasswordResetMailer;
use Illuminate\Support\Facades\Log;

class LogPasswordResetMailer implements PasswordResetMailer
{
    public function sendOtp(?string $email, string $otp, ?string $phone = null): void
    {
        Log::channel('daily')->info('FP_OTP_LOG', [
            'email' => $email,
            'phone' => $phone,
            'otp'   => $otp,   // DEV ONLY — never log OTP in production
        ]);
    }
}