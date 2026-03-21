<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $otp,
        public ?string $userEmail = null
    ) {}

    public function build()
    {
        $fromAddr = config('mail.from.address');
        $fromName = config('mail.from.name');

        $m = $this->subject('Your Password Reset OTP')
            ->from($fromAddr, $fromName)
            ->view('emails.passwordResetOtp')
            ->with([
                'otp'       => $this->otp,
                'userEmail' => $this->userEmail,
            ]);

        // Same deliverability block from your original mailable — untouched
        $m->withSymfonyMessage(function (SymfonyEmail $message) use ($fromAddr, $fromName) {
            if ($fromAddr) {
                $message->sender(new Address($fromAddr, $fromName ?: ''));
                $message->returnPath($fromAddr);
            }
        });

        return $m;
    }
}