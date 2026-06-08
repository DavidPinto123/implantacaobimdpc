<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('redireciona visitantes para fora do dashboard admin', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('renderiza a tela de login admin', function () {
    $this->get('/admin/login')
        ->assertOk();
});

it('autentica usuário ativo verificado pela página de login do Filament', function () {
    $password = 'Password@123';
    $user = User::factory()->active()->create([
        'password' => $password,
    ]);

    $user->assignRole(Role::findOrCreate('super_admin', 'web'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => $password,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);

    $response = $this->get('/admin');

    $response->assertStatus(302)
        ->assertRedirectContains('/admin');

    expect($response->headers->get('Location'))->not->toContain('/admin/login');
});

it('falha no login com senha inválida e mantém usuário como visitante', function () {
    $user = User::factory()->active()->create([
        'password' => 'Password@123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    $this->assertGuest();
});

it('rejeita usuários inativos durante login por credenciais', function () {
    $user = User::factory()->inactive()->create([
        'password' => 'Password@123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'Password@123',
        ])
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    $this->assertGuest();
});

it('autentica usuários ativos não verificados e redireciona para verificação de e-mail', function () {
    $password = 'Password@123';
    $user = User::factory()->active()->unverified()->create([
        'password' => $password,
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => $password,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertRedirect('/admin/email-verification/prompt');
});

it('bloqueia usuários inativos de acessar o painel admin', function () {
    $user = User::factory()->active()->create();
    $user->is_active = false;
    $user->save();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('redireciona usuários ativos não verificados para o fluxo de verificação de e-mail', function () {
    $user = User::factory()->active()->unverified()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect('/admin/email-verification/prompt');
});

it('renderiza o aviso de verificação de e-mail para usuários ativos não verificados', function () {
    Notification::fake();

    $user = User::factory()->active()->unverified()->create();

    $this->actingAs($user)
        ->get('/admin/email-verification/prompt')
        ->assertOk();

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});
