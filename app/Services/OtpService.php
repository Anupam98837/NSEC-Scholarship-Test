<?php

namespace App\Services;

use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
public function sendOtp(string $phone): array
{
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    OtpVerification::where('phone_number', $phone)->delete();

    OtpVerification::create([
        'phone_number' => $phone,
        'otp'          => $otp,
        'expires_at'   => Carbon::now()->addMinutes(10),
        'is_used'      => false,
    ]);

    // Try sending SMS — but don't block registration if it fails
    $sent = $this->callVoicenSmsApi($phone, $otp);

    return [
        'success' => true,
        'message' => $sent ? 'OTP sent to your phone.' : 'OTP generated (SMS pending activation).',
        'otp'     => $otp, // ← REMOVE THIS IN PRODUCTION
    ];
}
    public function verifyOtp(string $phone, string $otp): array
    {
        $record = OtpVerification::where('phone_number', $phone)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'OTP not found. Please request a new one.'];
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            $record->delete();
            return ['success' => false, 'message' => 'OTP expired. Please request a new one.'];
        }

        if ($record->otp !== $otp) {
            return ['success' => false, 'message' => 'Invalid OTP.'];
        }

        $record->update(['is_used' => true]);

        // Store verified phone in Cache for 10 minutes
        Cache::put('otp_verified_' . $phone, true, now()->addMinutes(10));

        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }

    public function isPhoneVerified(string $phone): bool
    {
        return Cache::has('otp_verified_' . $phone);
    }

    public function clearVerified(string $phone): void
    {
        Cache::forget('otp_verified_' . $phone);
    }

    // ─────────────────────────────────────────────
    // voicensms.in API call
    // ─────────────────────────────────────────────
 private function callVoicenSmsApi(string $phone, string $otp): bool
{
    try {
        $payload = [
            'ukey'       => config('voicensms.api_key'),
            'senderid'   => 'NSEC',
            'msisdn'     => [$phone],
            'message'    => "OTP for Login is {$otp}. NSEC will never call to verify your OTP. Do not share with anyone. NSEC www.nsec.ac.in Call 9831817307 for any assistance",
            'filetype'   => 2,
            'language'   => 0,
            'credittype' => 2,
            'templateid' => 0,
            'isrefno'    => true,
        ];

        $url = 'https://api.voicensms.in/SMSAPI/webresources/CreateSMSCampaignPost';

        Log::info('[VoicenSMS] Sending', [
            'url'   => $url,
            'phone' => $phone,
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post($url, $payload);

        Log::info('[VoicenSMS] Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        $json = $response->json();

        if (isset($json['status']) && strtolower($json['status']) === 'success') {
            return true;
        }

        Log::warning('[VoicenSMS] Failed', ['response' => $json ?? $response->body()]);
        return false;

    } catch (\Exception $e) {
        Log::error('[VoicenSMS] Exception', ['error' => $e->getMessage()]);
        return false;
    }
}
}