<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

it('retorna JSON do token APS para usuário autenticado com HTTP fake', function () {
    $this->actingAs(User::factory()->active()->create());

    Config::set('services.aps.client_id', 'test-client-id');
    Config::set('services.aps.client_secret', 'test-client-secret');

    Http::fake([
        'https://developer.api.autodesk.com/authentication/v2/token' => Http::response([
            'access_token' => 'fake-access-token',
            'expires_in' => 3599,
        ], 200),
    ]);

    $this->getJson(route('aps.token'))
        ->assertOk()
        ->assertExactJson([
            'access_token' => 'fake-access-token',
            'expires_in' => 3599,
        ]);
});

it('retorna 500 quando credenciais APS estão ausentes para usuário autenticado', function () {
    $this->actingAs(User::factory()->active()->create());

    Config::set('services.aps.client_id', null);
    Config::set('services.aps.client_secret', null);

    $this->getJson(route('aps.token'))
        ->assertStatus(500)
        ->assertJsonPath('error', 'APS_CLIENT_ID ou APS_CLIENT_SECRET não configurados. Verifique o .env e o config/services.php.');
});

it('retorna 500 com detalhes do erro upstream da Autodesk quando a requisição de token falha', function () {
    $this->actingAs(User::factory()->active()->create());

    Config::set('services.aps.client_id', 'test-client-id');
    Config::set('services.aps.client_secret', 'test-client-secret');

    Http::fake([
        'https://developer.api.autodesk.com/authentication/v2/token' => Http::response([
            'developerMessage' => 'invalid client',
        ], 401),
    ]);

    $this->getJson(route('aps.token'))
        ->assertStatus(500)
        ->assertJsonFragment(['error' => 'Falha ao obter token da Autodesk'])
        ->assertJsonFragment(['status' => 401])
        ->assertJsonFragment(['developerMessage' => 'invalid client']);
});
