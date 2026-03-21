<?php

namespace App\Services;

use App\Contracts\PasswordResetMailer;
use App\Contracts\SmsService;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmtpPasswordResetMailer implements PasswordResetMailer
{
    public function __construct(protected SmsService $sms) {}

    public function sendOtp(?string $email, string $otp, ?string $phone = null): void
    {
        // ── SMS ───────────────────────────────────────────────────────
        if ($phone) {
            try {
            $this->sms->send($phone, "OTP for Login is {$otp}. NSEC will never call to verify your OTP. Do not share with anyone. NSEC www.nsec.ac.in Call 9831817307 for any assistance", $otp);
                Log::channel('daily')->info('FP_SEND_OTP:SMS_SENT', ['phone' => $phone]);
            } catch (Throwable $e) {
                // SMS failure is non-fatal — email still proceeds if present
                Log::channel('daily')->error('FP_SEND_OTP:SMS_FAILED', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── Email — skip entirely if no email address ─────────────────
        if (!$email) {
            Log::channel('daily')->info('FP_SEND_OTP:EMAIL_SKIPPED', [
                'reason' => 'no email address on account — SMS only',
                'phone'  => $phone,
            ]);
            return;
        }

        // ── Email dispatch (DB mailer → ENV fallback) ─────────────────
        $envMailer      = config('mail.default', 'smtp');
        $envFromAddress = config('mail.from.address');
        $envFromName    = config('mail.from.name');

        $smtp = DB::table('mailer_settings')
            ->where('status', 'active')
            ->where('is_default', 1)
            ->orderByDesc('id')
            ->first();

        if (!$smtp) {
            $smtp = DB::table('mailer_settings')
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first();
        }

        if (!$smtp) {
            Mail::mailer($envMailer)
                ->to($email)
                ->send(new PasswordResetOtpMail($otp, $email));

            Log::channel('daily')->info('FP_SEND_OTP:MAIL_SOURCE_ENV_NO_DB', ['email' => $email]);
            return;
        }

        try {
            $smtpPassword = !empty($smtp->password)
                ? Crypt::decryptString($smtp->password)
                : null;

            config([
                'mail.mailers.dynamic_smtp' => [
                    'transport'  => $smtp->mailer ?: 'smtp',
                    'host'       => $smtp->host,
                    'port'       => (int) $smtp->port,
                    'encryption' => $smtp->encryption ?: null,
                    'username'   => $smtp->username,
                    'password'   => $smtpPassword,
                    'timeout'    => $smtp->timeout ?: null,
                    'auth_mode'  => null,
                ],
                'mail.from.address' => $smtp->from_address,
                'mail.from.name'    => $smtp->from_name,
            ]);

            Mail::mailer('dynamic_smtp')
                ->to($email)
                ->send(new PasswordResetOtpMail($otp, $email));

            Log::channel('daily')->info('FP_SEND_OTP:MAIL_SENT_SUCCESS_DB', [
                'email'     => $email,
                'mailer_id' => $smtp->id,
            ]);
            return;

        } catch (Throwable $dbMailError) {
            Log::channel('daily')->warning('FP_SEND_OTP:MAIL_DB_FAILED_TRY_ENV', [
                'email' => $email,
                'error' => $dbMailError->getMessage(),
            ]);
        }

        try {
            config([
                'mail.from.address' => $envFromAddress,
                'mail.from.name'    => $envFromName,
            ]);

            Mail::mailer($envMailer)
                ->to($email)
                ->send(new PasswordResetOtpMail($otp, $email));

            Log::channel('daily')->info('FP_SEND_OTP:MAIL_SENT_SUCCESS_ENV_FALLBACK', ['email' => $email]);

        } catch (Throwable $envMailError) {
            Log::channel('daily')->error('FP_SEND_OTP:MAIL_BOTH_FAILED', [
                'email'     => $email,
                'env_error' => $envMailError->getMessage(),
            ]);
            throw $envMailError;
        }
    }
}