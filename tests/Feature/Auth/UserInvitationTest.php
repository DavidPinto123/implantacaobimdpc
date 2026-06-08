<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;

uses(DatabaseTransactions::class);

it('verifica o e-mail do usuário convidado e redireciona para redefinição de senha', function () {
    $user = User::factory()->inactive()->unverified()->create();
    $token = 'invite-reset-token';
    $hash = sha1($user->getEmailForVerification());

    $url = URL::signedRoute('users.invitation.complete', [
        'user' => $user,
        'hash' => $hash,
        'token' => $token,
    ]);

    $this->get($url)
        ->assertRedirect(Filament::getResetPasswordUrl($token, $user))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejeita links de convite com hash inválido', function () {
    $user = User::factory()->inactive()->unverified()->create();
    $token = 'invite-reset-token';

    $url = URL::signedRoute('users.invitation.complete', [
        'user' => $user,
        'hash' => 'invalid-hash',
        'token' => $token,
    ]);

    $this->get($url)->assertForbidden();
});
