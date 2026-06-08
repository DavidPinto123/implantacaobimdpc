<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    public function mount(): void
    {
        if ((! Filament::auth()->check()) || $this->getVerifiable()->hasVerifiedEmail()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        $rateLimitingKey = 'filament-auto-email-verification:' . Filament::auth()->id();

        if (! RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 1)) {
            RateLimiter::hit($rateLimitingKey, decaySeconds: 300);
            $this->sendEmailVerificationNotification($this->getVerifiable());
        }
    }
}
