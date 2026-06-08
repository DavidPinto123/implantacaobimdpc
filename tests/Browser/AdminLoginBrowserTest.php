<?php

use App\Models\User;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->withoutMiddleware(ValidateSignature::class);
});

it('renderiza o formulário de login admin sem interface de 2fa ou otp', function () {
    visit('/admin/login')
        ->assertPathIs('/admin/login')
        ->assertSee('Faça login')
        ->assertSee('E-mail')
        ->assertSee('Senha')
        ->assertPresent('button[type="submit"]')
        ->assertDontSee('2FA')
        ->assertDontSee('OTP')
        ->assertDontSee('one-time')
        ->assertDontSee('authenticator');
});

it('conclui o fluxo de onboarding por convite e consegue logar no admin no browser', function () {
    $user = User::factory()->active()->unverified()->create();
    $token = Password::createToken($user);

    $invitationUrl = route(
        'users.invitation.complete',
        [
            'user' => $user,
            'hash' => sha1($user->getEmailForVerification()),
            'token' => $token,
        ],
        false,
    );

    $newPassword = 'OnboardingBrowser@123';

    $page = visit($invitationUrl)
        ->assertPathBeginsWith('/admin/password-reset/reset');

    Password::reset(
        [
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'token' => $token,
        ],
        function (User $user, string $password): void {
            $user->forceFill([
                'password' => bcrypt($password),
            ])->save();
        },
    );

    $page = visit('/admin/login');

    $page
        ->type('[type="email"]', $user->email)
        ->type('[type="password"]', $newPassword)
        ->submit();

    $page
        ->assertPathBeginsWith('/admin')
        ->assertPathIsNot('/admin/login');

    $this->assertTrue($user->fresh()->hasVerifiedEmail());
});

it('redireciona usuários ativos não verificados do dashboard admin para o aviso de verificação de e-mail', function () {
    $user = User::factory()->active()->unverified()->create();

    $this->actingAs($user);

    visit('/admin')
        ->assertPathIs('/admin/email-verification/prompt');
});

it('renderiza o aviso de verificação de e-mail para usuários autenticados não verificados', function () {
    $user = User::factory()->active()->unverified()->create();

    $this->actingAs($user);

    visit('/admin/email-verification/prompt')
        ->assertPathIs('/admin/email-verification/prompt')
        ->assertSee('Verifique');
});

it('conclui o fluxo completo de esqueci a senha do admin pela interface do browser', function () {
    $oldPassword = 'OldPassword@123';
    $newPassword = 'NewPassword@456';

    $user = User::factory()->active()->create([
        'password' => bcrypt($oldPassword),
    ]);

    $page = visit('/admin/password-reset/request')
        ->assertPathIs('/admin/password-reset/request')
        ->assertSee('E-mail')
        ->type('#form\.email', $user->email)
        ->submit()
        ->assertSee('Enviamos seu link de redefinição de senha por e-mail!');

    $token = Password::createToken($user);

    $resetUrl = route(
        'filament.admin.auth.password-reset.reset',
        [
            'email' => $user->email,
            'token' => $token,
        ],
        false,
    );

    $page = visit($resetUrl)
        ->assertPathIs('/admin/password-reset/reset')
        ->assertSee('Senha')
        ->type('#form\.password', $newPassword)
        ->type('#form\.passwordConfirmation', $newPassword)
        ->submit()
        ->assertPathIs('/admin/login')
        ->assertSee('Sua senha foi redefinida!');

    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();

    $page = visit('/admin/login');

    $page
        ->type('[type="email"]', $user->email)
        ->type('[type="password"]', $newPassword)
        ->submit()
        ->assertPathBeginsWith('/admin')
        ->assertPathIsNot('/admin/login');

    expect(Hash::check($oldPassword, $user->fresh()->password))->toBeFalse();
});
