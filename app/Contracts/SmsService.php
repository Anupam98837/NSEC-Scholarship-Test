<?php

namespace App\Contracts;

interface SmsService
{
    public function send(string $phone, string $message, ?string $otp = null): void;
}