<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('envia notificação de redefinição para usuários ativos na página de esqueci a senha do Filament', function () {
    Notification::fake();

    $user = User::factory()->active()->create();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm([
            'email' => $user->email,
        ])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertSentTo($user, FilamentResetPasswordNotification::class);
});

it('não envia notificação de redefinição para usuários inativos', function () {
    Notification::fake();

    $user = User::factory()->inactive()->create();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm([
            'email' => $user->email,
        ])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertNotSentTo($user, FilamentResetPasswordNotification::class);
});

it('redefine senha com token válido do broker e permite login com a nova senha', function () {
    $oldPassword = 'OldPassword@123';
    $newPassword = 'NewPassword@123';

    $user = User::factory()->active()->create([
        'password' => $oldPassword,
    ]);

    $token = app('auth.password.broker')->createToken($user);

    Livewire::withQueryParams([
        'email' => $user->email,
    ])->test(ResetPassword::class, [
        'token' => $token,
    ])
        ->fillForm([
            'email' => $user->email,
            'password' => $newPassword,
            'passwordConfirmation' => $newPassword,
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    Auth::guard('web')->logout();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => $oldPassword,
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    $this->assertGuest();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => $newPassword,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user->fresh());
});
