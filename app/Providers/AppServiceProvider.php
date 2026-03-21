<?php

namespace App\Providers;

use App\Contracts\PasswordResetMailer;
use App\Contracts\SmsService;
use App\Services\SmtpPasswordResetMailer;
use App\Services\VoicenSmsService;
use App\Services\LogSmsService;
// use App\Services\LogPasswordResetMailer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Live SMTP mailer — actually sends the email
        $this->app->bind(PasswordResetMailer::class, SmtpPasswordResetMailer::class);

        // Dev/debug only (logs link instead of sending) — swap back when needed:
        // $this->app->bind(PasswordResetMailer::class, LogPasswordResetMailer::class);

        // Live SMS — VoicenSMS
        $this->app->bind(SmsService::class, VoicenSmsService::class);

        // Dev/debug only (logs OTP instead of sending) — swap back when needed:
        // $this->app->bind(SmsService::class, LogSmsService::class);
    }

    public function boot(): void
    {
        //
    }
}