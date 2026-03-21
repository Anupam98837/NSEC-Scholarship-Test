<?php

namespace App\Services;

use App\Contracts\SmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoicenSmsService implements SmsService
{
   public function send(string $phone, string $message, ?string $otp = null): void
{
    $payload = [
    'ukey'       => config('services.voicensms.api_key'),
    'senderid'   => config('services.voicensms.sender_id'),
    'msisdn'     => [$phone],
    'message'    => $message,
    'filetype'   => 2,
    'language'   => 0,
    'credittype' => 2,
    'templateid' => 0,        
    'isrefno'    => true,
];

    Log::channel('daily')->info('[VoicenSMS] Sending', [
        'url'         => config('services.voicensms.endpoint'),
        'phone'       => $phone,
        'template_id' => config('services.voicensms.template_id'),
    ]);

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ])->timeout(10)->post(config('services.voicensms.endpoint'), $payload);

    Log::channel('daily')->info('[VoicenSMS] Response', [
        'status' => $response->status(),
        'body'   => $response->body(),
    ]);

    if ($response->failed()) {
        throw new \RuntimeException(
            'VoicenSMS HTTP error: ' . $response->status() . ' — ' . $response->body()
        );
    }

    $json = $response->json();

    if (!isset($json['status']) || strtolower($json['status']) !== 'success') {
        throw new \RuntimeException(
            'VoicenSMS API failure: ' . ($json['value'] ?? $response->body())
        );
    }
}
}