<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OtpLoginController extends Controller
{
    private const USER_TYPE = 'App\\Models\\User';
    private const OTP_EXPIRY_MINUTES = 10;
    private const CYCLE_RESET_HOURS = 24;
    private const COOLDOWN_STEPS = [30, 60, 120];

    public function sendOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        $phone = (string) $request->input('phone_number');
        $user = $this->findActiveUserByPhone($phone);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active user found with this mobile number.',
            ], 404);
        }

        $record = $this->latestOtpRecord($phone);
        $status = $this->buildOtpStatus($record);

        if (!$status['can_send']) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP already sent. Please wait before requesting another one.',
            ] + $status, 429);
        }

        $attemptCount = $status['attempt_count'] + 1;
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();
        $expiresAt = $now->copy()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $cycleStart = $status['cycle_active'] ? Carbon::parse($record->created_at) : $now->copy();

        if ($record && $status['cycle_active']) {
            DB::table('otp_verifications')
                ->where('id', $record->id)
                ->update([
                    'otp'           => $otp,
                    'expires_at'    => $expiresAt,
                    'is_used'       => false,
                    'system_ip'     => $request->ip(),
                    'attempt_count' => $attemptCount,
                    'updated_at'    => $now,
                ]);
        } elseif ($record) {
            DB::table('otp_verifications')
                ->where('id', $record->id)
                ->update([
                    'otp'           => $otp,
                    'expires_at'    => $expiresAt,
                    'is_used'       => false,
                    'system_ip'     => $request->ip(),
                    'attempt_count' => 1,
                    'created_at'    => $cycleStart,
                    'updated_at'    => $now,
                ]);
            $attemptCount = 1;
        } else {
            DB::table('otp_verifications')->insert([
                'phone_number'  => $phone,
                'system_ip'     => $request->ip(),
                'otp'           => $otp,
                'expires_at'    => $expiresAt,
                'is_used'       => false,
                'attempt_count' => 1,
                'created_at'    => $cycleStart,
                'updated_at'    => $now,
            ]);
            $attemptCount = 1;
        }

        $smsSent = $this->sendOtpSms($phone, $otp);

        $fresh = $this->latestOtpRecord($phone);
        $responseStatus = $this->buildOtpStatus($fresh);

        return response()->json([
            'status'  => $smsSent ? 'success' : 'warning',
            'message' => $smsSent
                ? 'OTP sent successfully.'
                : 'OTP generated, but SMS delivery could not be confirmed.',
            'attempt_count' => $attemptCount,
        ] + $responseStatus);
    }

    public function status(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        $phone = (string) $request->input('phone_number');
        $record = $this->latestOtpRecord($phone);

        return response()->json([
            'status' => 'success',
        ] + $this->buildOtpStatus($record));
    }

    public function verifyAndLogin(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'otp'          => ['required', 'digits:6'],
            'remember'     => ['sometimes', 'boolean'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        $phone = (string) $request->input('phone_number');
        $otp = (string) $request->input('otp');
        $user = $this->findActiveUserByPhone($phone);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active user found with this mobile number.',
            ], 404);
        }

        $record = DB::table('otp_verifications')
            ->where('phone_number', $phone)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$record || $this->isCycleExpired($record)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'OTP not found. Please request a new OTP.',
            ], 422);
        }

        if (!empty($record->is_used)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This OTP has already been used. Please request a new OTP.',
            ], 422);
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'OTP expired. Please request a new OTP.',
            ], 422);
        }

        if ((string) $record->otp !== $otp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid OTP.',
            ], 422);
        }

        DB::table('otp_verifications')
            ->where('id', $record->id)
            ->update([
                'is_used'    => true,
                'updated_at' => now(),
            ]);

        $remember = (bool) ($request->input('remember', true));
        $expiresAt = $remember ? now()->addDays(30) : now()->addHours(12);
        $plainToken = $this->issueToken((int) $user->id, $expiresAt);

        DB::table('users')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login successful',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $expiresAt->toIso8601String(),
            'user'         => $this->publicUserPayload($user),
        ]);
    }

    private function latestOtpRecord(string $phone): ?object
    {
        return DB::table('otp_verifications')
            ->where('phone_number', $phone)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    private function buildOtpStatus(?object $record): array
    {
        if (!$record || $this->isCycleExpired($record)) {
            return [
                'can_send'                 => true,
                'cycle_active'             => false,
                'attempt_count'            => 0,
                'wait_seconds'             => 0,
                'cooldown_seconds'         => 0,
                'has_active_otp'           => false,
                'otp_expires_in_seconds'   => 0,
                'cycle_resets_in_seconds'  => self::CYCLE_RESET_HOURS * 3600,
            ];
        }

        $attemptCount = max(0, (int) ($record->attempt_count ?? 0));
        $cooldown = $this->cooldownForAttempt($attemptCount);
        $nextAllowedAt = Carbon::parse($record->updated_at)->addSeconds($cooldown);
        $waitSeconds = max(0, now()->diffInSeconds($nextAllowedAt, false));
        $cycleResetsAt = Carbon::parse($record->created_at)->addHours(self::CYCLE_RESET_HOURS);
        $otpExpiresAt = Carbon::parse($record->expires_at);

        return [
            'can_send'                => $waitSeconds <= 0,
            'cycle_active'            => true,
            'attempt_count'           => $attemptCount,
            'wait_seconds'            => $waitSeconds,
            'cooldown_seconds'        => $cooldown,
            'has_active_otp'          => !$record->is_used && $otpExpiresAt->isFuture(),
            'otp_expires_in_seconds'  => !$record->is_used && $otpExpiresAt->isFuture()
                ? max(0, now()->diffInSeconds($otpExpiresAt, false))
                : 0,
            'cycle_resets_in_seconds' => max(0, now()->diffInSeconds($cycleResetsAt, false)),
        ];
    }

    private function cooldownForAttempt(int $attemptCount): int
    {
        if ($attemptCount <= 0) {
            return 0;
        }

        $index = min($attemptCount - 1, count(self::COOLDOWN_STEPS) - 1);

        return self::COOLDOWN_STEPS[$index];
    }

    private function isCycleExpired(object $record): bool
    {
        if (empty($record->created_at)) {
            return true;
        }

        return Carbon::parse($record->created_at)
            ->addHours(self::CYCLE_RESET_HOURS)
            ->isPast();
    }

    private function findActiveUserByPhone(string $phone): ?object
    {
        $user = DB::table('users')
            ->where('phone_number', $phone)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return null;
        }

        if (isset($user->status) && $user->status !== 'active') {
            return null;
        }

        return $user;
    }

    private function issueToken(int $userId, ?Carbon $expiresAt = null): string
    {
        $plain = bin2hex(random_bytes(40));

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => self::USER_TYPE,
            'tokenable_id'   => $userId,
            'name'           => 'unzip_exam_user_token',
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
            'last_used_at'   => null,
            'expires_at'     => $expiresAt ? $expiresAt->toDateTimeString() : null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $plain;
    }

    private function publicUserPayload(object $user): array
    {
        return [
            'id'              => (int) $user->id,
            'uuid'            => (string) ($user->uuid ?? ''),
            'name'            => (string) ($user->name ?? ''),
            'email'           => (string) ($user->email ?? ''),
            'role'            => (string) ($user->role ?? ''),
            'role_short_form' => (string) ($user->role_short_form ?? ''),
            'slug'            => (string) ($user->slug ?? ''),
            'image'           => $this->publicImageUrl($user->image ?? null),
            'status'          => (string) ($user->status ?? ''),
            'user_folder_id'  => isset($user->user_folder_id) ? (int) $user->user_folder_id : null,
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    private function sendOtpSms(string $phone, string $otp): bool
    {
        try {
            $payload = [
                'ukey'       => config('services.voicensms.api_key'),
                'senderid'   => config('services.voicensms.sender_id'),
                'msisdn'     => [$phone],
                'message'    => "{$otp} is the OTP for Login Registration valid for 10 mins. Please do not share it with anyone. Netaji Subhash Engineering College. Call Us at 9831817307",
                'args'       => [$otp],
                'filetype'   => 2,
                'language'   => 0,
                'credittype' => 2,
                'templateid' => 0,
                'isrefno'    => true,
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post('https://api.voicensms.in/SMSAPI/webresources/CreateSMSCampaignPost', $payload);

            $json = $response->json();

            return isset($json['status']) && strtolower((string) $json['status']) === 'success';
        } catch (\Throwable $e) {
            Log::error('[OtpLoginController] SMS send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
