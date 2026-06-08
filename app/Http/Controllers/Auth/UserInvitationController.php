<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserInvitationController
{
    public function __invoke(Request $request, User $user, string $hash, string $token): RedirectResponse
    {
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->to(Filament::getResetPasswordUrl($token, $user));
    }
}
