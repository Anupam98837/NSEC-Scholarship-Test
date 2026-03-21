<?php

namespace App\Contracts;

interface PasswordResetMailer
{
    public function sendOtp(?string $email, string $otp, ?string $phone = null): void;

}