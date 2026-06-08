<?php

use App\Filament\Auth\Login;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use App\Models\Setor;
use App\Models\User;
use App\Notifications\UserAccessInvitationNotification;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cria usuário no fluxo admin, envia convite e conclui onboarding', function () {
    Notification::fake();

    $admin = User::factory()->active()->create();
    Permission::findOrCreate('Create:User', 'web');
    Permission::findOrCreate('ViewAny:User', 'web');
    $admin->givePermissionTo('Create:User');
    $admin->givePermissionTo('ViewAny:User');

    $pais = Pais::create(['nome' => 'Brasil']);
    $estado = Estado::create(['nome' => 'São Paulo', 'uf' => 'SP', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'São Paulo', 'estado_id' => $estado->id]);
    $setor = Setor::create(['setor' => 'TI']);
    $role = Role::findOrCreate('Gestor', 'web');

    $this->actingAs($admin);

    $newUserEmail = 'invited.user@example.com';

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Invited User',
            'email' => $newUserEmail,
            'phone' => '5511999999999',
            'password' => '',
            'is_active' => true,
            'is_fornecedor' => false,
            'is_lider_obra' => false,
            'pais_id' => $pais->id,
            'estado_id' => $estado->id,
            'cidade_id' => $cidade->id,
            'roles' => [$role->id],
            'setores' => [$setor->id],
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    $createdUser = User::query()->where('email', $newUserEmail)->firstOrFail();

    expect($createdUser->setores->pluck('id')->all())->toContain($setor->id)
        ->and($createdUser->roles->pluck('id')->all())->toContain($role->id)
        ->and($createdUser->password)->not->toBe('')
        ->and($createdUser->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($createdUser, UserAccessInvitationNotification::class);

    $invitationUrl = null;

    Notification::assertSentTo(
        $createdUser,
        UserAccessInvitationNotification::class,
        function (UserAccessInvitationNotification $notification) use ($createdUser, &$invitationUrl): bool {
            $mail = $notification->toMail($createdUser);
            $invitationUrl = $mail->viewData['invitationUrl'] ?? null;

            return filled($invitationUrl);
        }
    );

    expect($invitationUrl)->not->toBeNull();

    $token = basename((string) parse_url((string) $invitationUrl, PHP_URL_PATH));

    $this->get((string) $invitationUrl)
        ->assertRedirect(Filament::getResetPasswordUrl($token, $createdUser));

    expect($createdUser->fresh()->hasVerifiedEmail())->toBeTrue();

    $newPassword = 'OnboardedPassword@123';

    Auth::guard('web')->logout();

    Livewire::withQueryParams([
        'email' => $createdUser->email,
    ])->test(ResetPassword::class, [
        'token' => $token,
    ])
        ->fillForm([
            'email' => $createdUser->email,
            'password' => $newPassword,
            'passwordConfirmation' => $newPassword,
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $createdUser->email,
            'password' => $newPassword,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($createdUser->fresh());
});
