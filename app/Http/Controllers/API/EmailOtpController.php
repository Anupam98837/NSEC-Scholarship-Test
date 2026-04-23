<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Mail\ResultLinkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class EmailOtpController extends Controller
{
    private const OTP_TTL      = 10;
    private const MAX_ATTEMPTS = 5;

    private function emailOwnedByAnotherActiveUser(string $email, int $userId): bool
    {
        $query = DB::table('users')
            ->where('email', $email)
            ->where('id', '!=', $userId);

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function actor(Request $request): array
    {
        return [
            'id' => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /* ── Reuse your DB mailer → ENV fallback chain ── */
    private function sendOtpMail(string $email, string $otp): void
    {
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

        // ── No DB mailer — use ENV directly ──
        if (!$smtp) {
            Mail::mailer($envMailer)
                ->to($email)
                ->send(new EmailVerificationOtpMail($otp, $email));

            Log::channel('daily')->info('EV_SEND_OTP:MAIL_SOURCE_ENV_NO_DB', ['email' => $email]);
            return;
        }

        // ── Try DB mailer first ──
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
                ->send(new EmailVerificationOtpMail($otp, $email));

            Log::channel('daily')->info('EV_SEND_OTP:MAIL_SENT_DB', [
                'email'     => $email,
                'mailer_id' => $smtp->id,
            ]);
            return;

        } catch (\Throwable $e) {
            Log::channel('daily')->warning('EV_SEND_OTP:MAIL_DB_FAILED_TRY_ENV', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        // ── ENV fallback ──
        config([
            'mail.from.address' => $envFromAddress,
            'mail.from.name'    => $envFromName,
        ]);

        Mail::mailer($envMailer)
            ->to($email)
            ->send(new EmailVerificationOtpMail($otp, $email));

        Log::channel('daily')->info('EV_SEND_OTP:MAIL_SENT_ENV_FALLBACK', ['email' => $email]);
    }

    /* ── Cooldown logic ── */
    private function getCooldown(int $userId): ?array
    {
        $now   = now();
        $sends = DB::table('email_verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->orderByDesc('created_at')
            ->get(['created_at']);

        $total = $sends->count();

        if ($total >= 3) {
            $unlocksAt = \Carbon\Carbon::parse($sends->last()->created_at)
                ->addDay()->startOfDay();
            return [
                'message'      => 'Too many OTP requests today. Please try again tomorrow.',
                'retry_after'  => $unlocksAt->toDateTimeString(),
                'seconds_left' => max(0, (int) $now->diffInSeconds($unlocksAt, false)),
            ];
        }

        if ($total === 2) {
            $unlocksAt = \Carbon\Carbon::parse($sends->first()->created_at)->addMinutes(5);
            if ($now->isBefore($unlocksAt)) {
                return [
                    'message'      => 'Please wait 5 minutes before requesting another OTP.',
                    'retry_after'  => $unlocksAt->toDateTimeString(),
                    'seconds_left' => max(0, (int) $now->diffInSeconds($unlocksAt, false)),
                ];
            }
        }

        if ($total === 1) {
            $unlocksAt = \Carbon\Carbon::parse($sends->first()->created_at)->addMinutes(2);
            if ($now->isBefore($unlocksAt)) {
                return [
                    'message'      => 'Please wait 2 minutes before requesting another OTP.',
                    'retry_after'  => $unlocksAt->toDateTimeString(),
                    'seconds_left' => max(0, (int) $now->diffInSeconds($unlocksAt, false)),
                ];
            }
        }

        return null;
    }

    /* =========================================================
     | POST /api/student-results/check-email
     |========================================================= */
    public function checkEmail(Request $request)
    {
        $userId = $this->actor($request)['id'];
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 403);
        }

        $email = strtolower(trim((string) $request->input('email', '')));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'A valid email address is required.',
            ], 422);
        }

        $available = !$this->emailOwnedByAnotherActiveUser($email, $userId);

        return response()->json([
            'success' => true,
            'available' => $available,
            'message' => $available
                ? 'Email is available.'
                : 'This email address is already used by another account.',
        ]);
    }

    /* =========================================================
     | POST /api/student-results/send-email-otp
     |========================================================= */
    public function send(Request $request)
{
    $userId = $this->actor($request)['id'];
    if ($userId <= 0) {
        return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 403);
    }

    $email = strtolower(trim((string) $request->input('email', '')));
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['success' => false, 'message' => 'A valid email address is required.'], 422);
    }

    if ($this->emailOwnedByAnotherActiveUser($email, $userId)) {
        return response()->json([
            'success' => false,
            'message' => 'This email address is already used by another account.',
        ], 409);
    }

    // ── Invalidate previous unused OTPs ──
    DB::table('email_verifications')
        ->where('user_id', $userId)
        ->where('is_used', 0)
        ->update(['is_used' => 1, 'updated_at' => now()]);

    // ── Generate OTP ──
    $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    // ── Persist ──
    DB::table('email_verifications')->insert([
        'user_id'       => $userId,
        'email'         => $email,
        'system_ip'     => $request->ip(),
        'otp'           => $otp,
        'attempt_count' => 0,
        'is_used'       => 0,
        'expires_at'    => now()->addMinutes(self::OTP_TTL),
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    // ── Send mail ──
    try {
        $this->sendOtpMail($email, $otp);
    } catch (\Throwable $e) {
        DB::table('email_verifications')
            ->where('user_id', $userId)
            ->where('is_used', 0)
            ->delete();

        Log::channel('daily')->error('EV_SEND_OTP:BOTH_MAILERS_FAILED', [
            'email' => $email,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP email. Please try again.',
        ], 500);
    }

    return response()->json([
        'success'            => true,
        'message'            => 'OTP sent. Check your inbox.',
        'expires_in_minutes' => self::OTP_TTL,
    ]);
}

    /* =========================================================
     | POST /api/student-results/verify-email-otp
     |========================================================= */
    public function verify(Request $request)
    {
        $userId = $this->actor($request)['id'];
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 403);
        }

        $email = strtolower(trim((string) $request->input('email', '')));
        $otp   = trim((string) $request->input('otp', ''));

        if (!$email || !$otp) {
            return response()->json(['success' => false, 'message' => 'Email and OTP are required.'], 422);
        }

        $record = DB::table('email_verifications')
            ->where('user_id', $userId)
            ->where('email', $email)
            ->where('is_used', 0)
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No active OTP found. Please request a new one.',
            ], 404);
        }

        // ── Expired ──
        if (now()->isAfter($record->expires_at)) {
            DB::table('email_verifications')
                ->where('id', $record->id)
                ->update(['is_used' => 1, 'updated_at' => now()]);

            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
                'expired' => true,
            ], 422);
        }

        // ── Too many attempts ──
        if ((int) $record->attempt_count >= self::MAX_ATTEMPTS) {
            DB::table('email_verifications')
                ->where('id', $record->id)
                ->update(['is_used' => 1, 'updated_at' => now()]);

            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
                'expired' => true,
            ], 429);
        }

        // ── Wrong OTP ──
        if ($otp !== $record->otp) {
            DB::table('email_verifications')
                ->where('id', $record->id)
                ->update(['attempt_count' => DB::raw('attempt_count + 1'), 'updated_at' => now()]);

            $remaining = self::MAX_ATTEMPTS - ((int) $record->attempt_count + 1);

            return response()->json([
                'success'            => false,
                'message'            => 'Incorrect OTP. ' . max(0, $remaining) . ' attempt(s) remaining.',
                'attempts_remaining' => max(0, $remaining),
            ], 422);
        }

        // ── ✅ Correct ──
        DB::table('email_verifications')
            ->where('id', $record->id)
            ->update(['is_used' => 1, 'updated_at' => now()]);

        $userUpdate = ['updated_at' => now()];
        if (Schema::hasColumn('users', 'email'))             $userUpdate['email']             = $email;
        if (Schema::hasColumn('users', 'email_verified_at')) $userUpdate['email_verified_at'] = now();

        if ($this->emailOwnedByAnotherActiveUser($email, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'This email address is already used by another account.',
            ], 409);
        }

        DB::table('users')->where('id', $userId)->update($userUpdate);

        Log::channel('daily')->info('EV_VERIFY_OTP:SUCCESS', ['user_id' => $userId, 'email' => $email]);

        return response()->json([
            'success'           => true,
            'message'           => 'Email verified successfully.',
            'email'             => $email,
            'email_verified_at' => now()->toDateTimeString(),
        ]);
    }
    /* =========================================================
     | POST /api/student-results/send-result-email
     | Body: { result_uuid, module, view_url, email }
     |========================================================= */
  public function sendResultEmail(Request $request)
    {
        $userId = $this->actor($request)['id'];
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 403);
        }

        $email      = strtolower(trim((string) $request->input('email', '')));
        $resultUuid = trim((string) $request->input('result_uuid', ''));
        $module     = trim((string) $request->input('module', ''));
        $viewUrl    = trim((string) $request->input('view_url', ''));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Valid email required.'], 422);
        }
        if (!$resultUuid) {
            return response()->json(['success' => false, 'message' => 'Result UUID required.'], 422);
        }

        // ── Safe column select (schema may not have email_verified_at) ──
        $selects = ['id', 'email'];
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $selects[] = 'email_verified_at';
        } else {
            $selects[] = DB::raw('NULL as email_verified_at');
        }

        $user = DB::table('users')
            ->where('id', $userId)
            ->select($selects)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // ── Only block if column EXISTS but timestamp is empty ──
        $colExists  = Schema::hasColumn('users', 'email_verified_at');
        $verifiedAt = $user->email_verified_at ?? null;

        if ($colExists && empty($verifiedAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified. Please verify your email first.',
            ], 403);
        }

        // ── If user has a stored email, submitted email must match ──
        if (!empty($user->email) && strtolower(trim($user->email)) !== $email) {
            return response()->json([
                'success' => false,
                'message' => 'Email address does not match your account.',
            ], 422);
        }

        $fullUrl = url($viewUrl);

        try {
            $this->sendResultLinkMail($email, $fullUrl, $module);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('SEND_RESULT_EMAIL:FAILED', [
                'user_id' => $userId,
                'email'   => $email,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again.',
            ], 500);
        }

        Log::channel('daily')->info('SEND_RESULT_EMAIL:SENT', [
            'user_id'     => $userId,
            'email'       => $email,
            'result_uuid' => $resultUuid,
            'module'      => $module,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Result link sent to your email.',
        ]);
    }

   private function sendResultLinkMail(string $email, string $fullUrl, string $module): void
{
    $moduleLabel = match($module) {
        'quizz'       => 'Quiz',
        'door_game'   => 'Door Game',
        'bubble_game' => 'Bubble Game',
        'path_game'   => 'Path Game',
        default       => 'Result',
    };

    $appName     = config('app.name', 'Portal');
    $envMailer   = config('mail.default', 'smtp');
    $envFromAddr = config('mail.from.address');
    $envFromName = config('mail.from.name');

    $mailable = new ResultLinkMail($fullUrl, $moduleLabel, $appName);

    // ── Try DB mailer first ──
    $smtp = DB::table('mailer_settings')
        ->where('status', 'active')->where('is_default', 1)
        ->orderByDesc('id')->first()
        ?? DB::table('mailer_settings')
            ->where('status', 'active')
            ->orderByDesc('id')->first();

    if ($smtp) {
        try {
            $smtpPassword = !empty($smtp->password)
                ? \Illuminate\Support\Facades\Crypt::decryptString($smtp->password)
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

            \Illuminate\Support\Facades\Mail::mailer('dynamic_smtp')
                ->to($email)
                ->send($mailable);

            return;

        } catch (\Throwable $e) {
            Log::channel('daily')->warning('SEND_RESULT_EMAIL:DB_MAILER_FAILED', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── ENV fallback ──
    config([
        'mail.from.address' => $envFromAddr,
        'mail.from.name'    => $envFromName,
    ]);

    \Illuminate\Support\Facades\Mail::mailer($envMailer)
        ->to($email)
        ->send($mailable);
}
}
