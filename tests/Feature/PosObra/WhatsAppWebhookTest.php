<?php

use App\Services\PosObra\WhatsAppBotService;
use App\Services\PosObra\WhatsAppService;
use Database\Factories\WhatsappConfigFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('valida o webhook com token correto', function () {
    $config = WhatsappConfigFactory::new()->create([
        'verify_token' => 'token-correto',
    ]);

    $response = $this->get(route('whatsapp.webhook.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => $config->verify_token,
        'hub_challenge' => 'challenge-123',
    ]));

    $response->assertOk();
    expect($response->getContent())->toBe('challenge-123');
});

it('nega a validacao do webhook com token invalido', function () {
    WhatsappConfigFactory::new()->create([
        'verify_token' => 'token-correto',
    ]);

    $response = $this->get(route('whatsapp.webhook.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'token-invalido',
        'hub_challenge' => 'challenge-123',
    ]));

    $response->assertForbidden();
});

it('processa status e chama marcarEntregue no WhatsAppService', function () {
    $whatsAppService = Mockery::mock(WhatsAppService::class);
    $botService = Mockery::mock(WhatsAppBotService::class);

    $whatsAppService
        ->shouldReceive('marcarEntregue')
        ->once()
        ->with('wamid.status.1', 'DELIVERED');

    $botService->shouldReceive('processar')->never();

    $this->instance(WhatsAppService::class, $whatsAppService);
    $this->instance(WhatsAppBotService::class, $botService);

    $payload = [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'statuses' => [[
                        'id' => 'wamid.status.1',
                        'status' => 'delivered',
                    ]],
                ],
            ]],
        ]],
    ];

    $response = $this->postJson(route('whatsapp.webhook.receive'), $payload);

    $response->assertOk();
    expect($response->getContent())->toBe('OK');
});

it('processa mensagem de texto e chama processar no WhatsAppBotService', function () {
    $whatsAppService = Mockery::mock(WhatsAppService::class);
    $botService = Mockery::mock(WhatsAppBotService::class);

    $whatsAppService->shouldReceive('marcarEntregue')->never();
    $whatsAppService
        ->shouldReceive('registrar')
        ->once()
        ->withAnyArgs();

    $botService
        ->shouldReceive('processar')
        ->once()
        ->with('5511999999999', 'Olá bot', null, 'TEXTO', null);

    $this->instance(WhatsAppService::class, $whatsAppService);
    $this->instance(WhatsAppBotService::class, $botService);

    $payload = [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'messages' => [[
                        'id' => 'wamid.msg.1',
                        'from' => '5511999999999',
                        'type' => 'text',
                        'text' => ['body' => 'Olá bot'],
                    ]],
                ],
            ]],
        ]],
    ];

    $response = $this->postJson(route('whatsapp.webhook.receive'), $payload);

    $response->assertOk();
    expect($response->getContent())->toBe('OK');
});
