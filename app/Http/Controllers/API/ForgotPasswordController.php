<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\PasswordResetMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    // ── Resend cooldown ladder (seconds) ─────────────────────────────
    // 1st send  → sets 2 min cooldown before 1st resend is allowed
    // 1st resend → sets 5 min cooldown before 2nd resend is allowed
    // 2nd resend → hard-locks for 24h
    private const COOLDOWNS = [120, 300];   // indexed by attempt count (0-based)
    private const LOCK_TTL  = 86400;        // 24 hours in seconds

    public function __construct(protected PasswordResetMailer $mailer) {}

    // ═══════════════════════════════════════════════════════════
    // Activity Log — ZERO changes
    // ═══════════════════════════════════════════════════════════

    private function activityActor(Request $r): array
    {
        return [
            'role' => $r->attributes->get('auth_role'),
            'id'   => (int) ($r->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    private function logActivity(
        Request $request,
        string $activity,
        string $note,
        string $tableName,
        ?int $recordId = null,
        ?array $changedFields = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->activityActor($request);

        try {
            DB::table('user_data_activity_log')->insert([
                'performed_by'      => $a['id'] ?: 0,
                'performed_by_role' => $a['role'] ?: null,
                'ip'                => $request->ip(),
                'user_agent'        => (string) $request->userAgent(),
                'activity'          => $activity,
                'module'            => 'ForgotPassword',
                'table_name'        => $tableName,
                'record_id'         => $recordId,
                'changed_fields'    => $changedFields ? json_encode(array_values($changedFields), JSON_UNESCAPED_UNICODE) : null,
                'old_values'        => $oldValues  ? json_encode($oldValues,  JSON_UNESCAPED_UNICODE) : null,
                'new_values'        => $newValues  ? json_encode($newValues,  JSON_UNESCAPED_UNICODE) : null,
                'log_note'          => $note,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ForgotPassword] user_data_activity_log insert failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Cache key helpers ─────────────────────────────────────────────
    private function cacheSlug(string $identifier): string
    {
        return md5(strtolower($identifier));
    }

    private function lockKey(string $slug): string     { return "fp_lock:{$slug}"; }
    private function cooldownKey(string $slug): string { return "fp_cooldown:{$slug}"; }
    private function attemptKey(string $slug): string  { return "fp_attempts:{$slug}"; }

    private function clearRateLimitCache(string $identifier): void
    {
        $slug = $this->cacheSlug($identifier);
        Cache::forget($this->lockKey($slug));
        Cache::forget($this->cooldownKey($slug));
        Cache::forget($this->attemptKey($slug));
    }

    /* =========================================================
     | API 1 — POST /api/auth/forgot-password/send-otp
     | body: { identifier }  ← email OR phone number
     * ========================================================= */

    public function sendOtp(Request $r)
    {
        $reqId = (string) Str::uuid();

        Log::channel('daily')->info('FP_SEND_OTP:HIT', [
            'request_id' => $reqId,
            'method'     => $r->method(),
            'path'       => $r->path(),
            'full_url'   => $r->fullUrl(),
            'ip'         => $r->ip(),
            'ua'         => substr((string) $r->userAgent(), 0, 180),
            'ts'         => now()->toDateTimeString(),
        ]);

        $r->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($r->identifier);
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
        $identifier = $isEmail ? strtolower($identifier) : preg_replace('/\s+/', '', $identifier);

        Log::channel('daily')->info('FP_SEND_OTP:AFTER_VALIDATE', [
            'request_id' => $reqId,
            'identifier' => $identifier,
            'type'       => $isEmail ? 'email' : 'phone',
        ]);

        // ── Rate-limit check ──────────────────────────────────────────
        $slug        = $this->cacheSlug($identifier);
        $lockKey     = $this->lockKey($slug);
        $cooldownKey = $this->cooldownKey($slug);
        $attemptKey  = $this->attemptKey($slug);

        if (Cache::has($lockKey)) {
            $retryAfter = (int) Cache::get($lockKey);

            Log::channel('daily')->warning('FP_SEND_OTP:HARD_LOCKED', [
                'request_id'  => $reqId,
                'identifier'  => $identifier,
                'retry_after' => $retryAfter,
            ]);

            $this->logActivity(
                $r,
                'store',
                'OTP request blocked — identifier hard-locked for 24h',
                'password_reset_tokens',
                null,
                ['identifier'],
                null,
                ['identifier' => $identifier, 'retry_after' => $retryAfter, 'request_id' => $reqId]
            );

            return response()->json([
                'status'      => 'error',
                'message'     => 'Too many attempts. Please try again after 24 hours.',
                'retry_after' => $retryAfter,
                'locked'      => true,
            ], 429);
        }

        if (Cache::has($cooldownKey)) {
            $retryAfter = (int) Cache::get($cooldownKey);

            Log::channel('daily')->warning('FP_SEND_OTP:COOLDOWN_ACTIVE', [
                'request_id'  => $reqId,
                'identifier'  => $identifier,
                'retry_after' => $retryAfter,
            ]);

            return response()->json([
                'status'      => 'error',
                'message'     => 'Please wait before requesting another OTP.',
                'retry_after' => $retryAfter,
                'locked'      => false,
            ], 429);
        }

        // ── Increment attempt counter ─────────────────────────────────
        // attempts starts at 0; after first send it becomes 1, etc.
        $attempts = (int) Cache::get($attemptKey, 0);
        $attempts++;

        if ($attempts > count(self::COOLDOWNS)) {
            // All resends exhausted → hard-lock 24h
            Cache::put($lockKey, self::LOCK_TTL, self::LOCK_TTL);
            Cache::forget($attemptKey);
            Cache::forget($cooldownKey);

            Log::channel('daily')->warning('FP_SEND_OTP:HARD_LOCK_SET', [
                'request_id' => $reqId,
                'identifier' => $identifier,
                'attempts'   => $attempts,
            ]);

            $this->logActivity(
                $r,
                'store',
                'OTP request blocked — max resends exceeded, hard-locked 24h',
                'password_reset_tokens',
                null,
                ['identifier'],
                null,
                ['identifier' => $identifier, 'attempts' => $attempts, 'request_id' => $reqId]
            );

            return response()->json([
                'status'      => 'error',
                'message'     => 'Too many attempts. Please try again after 24 hours.',
                'retry_after' => self::LOCK_TTL,
                'locked'      => true,
            ], 429);
        }

        // Store updated attempt count (survives for 24h)
        Cache::put($attemptKey, $attempts, self::LOCK_TTL);

        // Set cooldown for the NEXT resend click (0-indexed: attempt 1→120s, attempt 2→300s)
        $nextCooldown = self::COOLDOWNS[$attempts - 1];
        Cache::put($cooldownKey, $nextCooldown, $nextCooldown);

        Log::channel('daily')->info('FP_SEND_OTP:RATE_LIMIT_UPDATED', [
            'request_id'   => $reqId,
            'identifier'   => $identifier,
            'attempt'      => $attempts,
            'next_cooldown'=> $nextCooldown,
        ]);

        // ── Everything below is ZERO changes from your original ───────
        $genericMessage = 'If this account exists in our system, an OTP has been sent to your registered contact.';

        $userRow = $isEmail
            ? DB::table('users')->select('id', 'email', 'phone_number')->where('email', $identifier)->first()
            : DB::table('users')->select('id', 'email', 'phone_number')->where('phone_number', $identifier)->first();

        Log::channel('daily')->info('FP_SEND_OTP:USER_EXISTS_CHECK', [
            'request_id'  => $reqId,
            'identifier'  => $identifier,
            'user_exists' => (bool) $userRow,
        ]);

        if (!$userRow) {
            $this->logActivity(
                $r,
                'store',
                'OTP requested — user not found (silent success)',
                'password_reset_tokens',
                null,
                ['identifier'],
                null,
                ['identifier' => $identifier, 'request_id' => $reqId]
            );

            Log::channel('daily')->warning('FP_SEND_OTP:USER_NOT_FOUND_SILENT_SUCCESS', [
                'request_id' => $reqId,
                'identifier' => $identifier,
            ]);

            return response()->json([
                'status'      => 'success',
                'message'     => $genericMessage,
                'retry_after' => $nextCooldown,
                'data'        => [
                    'request_id' => $reqId,
                    'email'      => $isEmail ? $identifier : null,
                    'phone'      => $isEmail ? null : $identifier,
                ],
            ]);
        }

        $email    = !empty($userRow->email)        ? strtolower(trim($userRow->email))   : null;
        $phone    = !empty($userRow->phone_number) ? trim($userRow->phone_number)         : null;
        $tokenKey = $email ?? $phone;

        Log::channel('daily')->info('FP_SEND_OTP:RESOLVED', [
            'request_id' => $reqId,
            'has_email'  => (bool) $email,
            'has_phone'  => (bool) $phone,
            'token_key'  => $tokenKey,
        ]);

        $invalidated = DB::table('password_reset_tokens')
            ->where('email', $tokenKey)
            ->whereNull('verified_at')
            ->update(['verified_at' => Carbon::now()]);

        Log::channel('daily')->info('FP_SEND_OTP:INVALIDATED_OLD_TOKENS', [
            'request_id'        => $reqId,
            'token_key'         => $tokenKey,
            'invalidated_count' => (int) $invalidated,
        ]);

        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now       = Carbon::now();
        $expiresAt = $now->copy()->addMinutes(10);

        Log::channel('daily')->info('FP_SEND_OTP:OTP_GENERATED', [
            'request_id' => $reqId,
            'token_key'  => $tokenKey,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);

        try {
            DB::table('password_reset_tokens')->where('email', $tokenKey)->delete();

            DB::table('password_reset_tokens')->insert([
                'email'       => $tokenKey,
                'token'       => Str::random(64),
                'phone_no'    => $phone,
                'otp'         => $otp,
                'expires_at'  => $expiresAt,
                'verified_at' => null,
                'created_at'  => $now,
            ]);

            Log::channel('daily')->info('FP_SEND_OTP:INSERT_OK', [
                'request_id' => $reqId,
                'token_key'  => $tokenKey,
            ]);

            $this->logActivity(
                $r,
                'store',
                'OTP generated and stored — valid 10 minutes',
                'password_reset_tokens',
                null,
                ['email', 'phone_no', 'otp', 'expires_at'],
                null,
                [
                    'token_key'  => $tokenKey,
                    'phone_no'   => $phone,
                    'expires_at' => $expiresAt->toDateTimeString(),
                    'request_id' => $reqId,
                ]
            );

        } catch (\Throwable $e) {
            Log::channel('daily')->error('FP_SEND_OTP:INSERT_FAILED', [
                'request_id' => $reqId,
                'token_key'  => $tokenKey,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate OTP. Please try again.',
                'data'    => ['request_id' => $reqId],
            ], 500);
        }

        if ($email) {
            $this->mailer->sendOtp($email, $otp, $phone);
        } else {
            $this->mailer->sendOtp(null, $otp, $phone);
        }

        Log::channel('daily')->info('FP_SEND_OTP:DISPATCHED', [
            'request_id' => $reqId,
            'has_email'  => (bool) $email,
            'has_phone'  => (bool) $phone,
        ]);

        return response()->json([
            'status'      => 'success',
            'message'     => $genericMessage,
            'retry_after' => $nextCooldown,   // ← frontend drives its timer from this
            'data'        => [
                'request_id'         => $reqId,
                'expires_in_minutes' => 10,
                'email'              => $email,
                'phone'              => $phone,
                'token_key'          => $tokenKey,
            ],
        ]);
    }

    /* =========================================================
     | API 2 — POST /api/auth/forgot-password/reset
     | Only addition: clear rate-limit cache on success
     * ========================================================= */

    public function resetPassword(Request $r)
    {
        $reqId = (string) Str::uuid();

        Log::channel('daily')->info('FP_RESET:HIT', [
            'request_id' => $reqId,
            'method'     => $r->method(),
            'path'       => $r->path(),
            'ip'         => $r->ip(),
            'ts'         => now()->toDateTimeString(),
        ]);

        $r->validate([
            'token_key' => ['required', 'string', 'max:255'],
            'otp'       => ['required', 'string', 'digits:6'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $tokenKey = trim($r->token_key);
        $otp      = $r->otp;

        $row = DB::table('password_reset_tokens')
            ->where('email', $tokenKey)
            ->whereNull('verified_at')
            ->first();

        Log::channel('daily')->info('FP_RESET:RECORD_FETCH', [
            'request_id'   => $reqId,
            'token_key'    => $tokenKey,
            'record_found' => (bool) $row,
        ]);

        if (!$row) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This OTP is invalid or has expired.',
            ], 422);
        }

        if (Carbon::parse($row->expires_at)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $tokenKey)
                ->update(['verified_at' => Carbon::now()]);

            $this->logActivity(
                $r,
                'update',
                'OTP expired (10 min window passed) — invalidated',
                'password_reset_tokens',
                null,
                ['verified_at'],
                ['verified_at' => null,              'token_key' => $tokenKey],
                ['verified_at' => Carbon::now()->toDateTimeString(), 'token_key' => $tokenKey]
            );

            Log::channel('daily')->warning('FP_RESET:OTP_EXPIRED', [
                'request_id' => $reqId,
                'token_key'  => $tokenKey,
                'expired_at' => $row->expires_at,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'This OTP is invalid or has expired.',
            ], 422);
        }

        if ($row->otp !== $otp) {
            Log::channel('daily')->warning('FP_RESET:OTP_MISMATCH', [
                'request_id' => $reqId,
                'token_key'  => $tokenKey,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'This OTP is invalid or has expired.',
            ], 422);
        }

        $isEmail = filter_var($tokenKey, FILTER_VALIDATE_EMAIL) !== false;

        $userRow = $isEmail
            ? DB::table('users')->select('id', 'email', 'phone_number')->where('email', $tokenKey)->first()
            : DB::table('users')->select('id', 'email', 'phone_number')->where('phone_number', $tokenKey)->first();

        if (!$userRow) {
            DB::table('password_reset_tokens')
                ->where('email', $tokenKey)
                ->update(['verified_at' => Carbon::now()]);

            $this->logActivity(
                $r,
                'update',
                'User not found during reset — invalidated OTP record',
                'password_reset_tokens',
                null,
                ['verified_at'],
                ['verified_at' => null,              'token_key' => $tokenKey],
                ['verified_at' => Carbon::now()->toDateTimeString(), 'token_key' => $tokenKey]
            );

            Log::channel('daily')->error('FP_RESET:USER_NOT_FOUND', [
                'request_id' => $reqId,
                'token_key'  => $tokenKey,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $updateQuery = DB::table('users');
        if ($isEmail) {
            $updateQuery->where('email', $tokenKey);
        } else {
            $updateQuery->where('phone_number', $tokenKey);
        }

        $updateQuery->update([
            'password'   => Hash::make($r->password),
            'updated_at' => Carbon::now(),
        ]);

        Log::channel('daily')->info('FP_RESET:PASSWORD_UPDATED', [
            'request_id' => $reqId,
            'token_key'  => $tokenKey,
            'user_id'    => $userRow->id,
        ]);

        $this->logActivity(
            $r,
            'update',
            'Password reset successful — user password updated',
            'users',
            (int) $userRow->id,
            ['password', 'updated_at'],
            ['token_key' => $tokenKey],
            ['token_key' => $tokenKey]
        );

        DB::table('password_reset_tokens')
            ->where('email', $tokenKey)
            ->update(['verified_at' => Carbon::now()]);

        $this->logActivity(
            $r,
            'update',
            'OTP marked verified and consumed after successful reset',
            'password_reset_tokens',
            null,
            ['verified_at'],
            ['verified_at' => null,                              'token_key' => $tokenKey],
            ['verified_at' => Carbon::now()->toDateTimeString(), 'token_key' => $tokenKey]
        );

        Log::channel('daily')->info('FP_RESET:OTP_CONSUMED', [
            'request_id' => $reqId,
            'token_key'  => $tokenKey,
        ]);

        // ── Clear rate-limit cache so user can reset again cleanly ────
        $this->clearRateLimitCache($tokenKey);

        Log::channel('daily')->info('FP_RESET:RATE_LIMIT_CACHE_CLEARED', [
            'request_id' => $reqId,
            'token_key'  => $tokenKey,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Your password has been successfully updated.',
        ]);
    }
}