<?php

namespace App\Http\Controllers\PosObra;

use App\Http\Controllers\Controller;
use App\Models\PosObra\WhatsappConfig;
use App\Services\PosObra\WhatsAppBotService;
use App\Services\PosObra\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsAppBotService $bot,
        private WhatsAppService $whatsApp,
    ) {}

    // Verificação do webhook pela Meta
    public function verify(Request $request): Response
    {
        $config = WhatsappConfig::instancia();
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $config?->verify_token) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // Recebe mensagens e status do Meta
    public function receive(Request $request): Response
    {
        $payload = $request->all();

        try {
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    // Atualização de status de entrega
                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->whatsApp->marcarEntregue(
                            wamid: $status['id'],
                            status: strtoupper($status['status']), // sent, delivered, read
                        );
                    }

                    // Mensagens recebidas
                    foreach ($value['messages'] ?? [] as $mensagem) {
                        $telefone = $mensagem['from'];
                        $tipo = strtoupper($mensagem['type'] ?? 'text');
                        $texto = $mensagem['text']['body'] ?? '';
                        $buttonId = null;
                        $midiaUrl = null;

                        // Extrair resposta de botão ou lista interativa
                        if ($tipo === 'INTERACTIVE') {
                            $interactiveType = $mensagem['interactive']['type'] ?? '';
                            if ($interactiveType === 'button_reply') {
                                $buttonId = $mensagem['interactive']['button_reply']['id'] ?? null;
                                $texto = $mensagem['interactive']['button_reply']['title'] ?? '';
                            } elseif ($interactiveType === 'list_reply') {
                                $buttonId = $mensagem['interactive']['list_reply']['id'] ?? null;
                                $texto = $mensagem['interactive']['list_reply']['title'] ?? '';
                            }
                        }

                        if (in_array($tipo, ['IMAGE', 'DOCUMENT', 'AUDIO', 'VIDEO'])) {
                            $midiaUrl = $mensagem[$mensagem['type']]['id'] ?? null;
                        }

                        // Registra mensagem recebida
                        $this->whatsApp->registrar(
                            telefone: $telefone,
                            direcao: 'RECEBIDA',
                            mensagem: $buttonId ? "[{$buttonId}] {$texto}" : ($texto ?: null),
                            tipo: match ($tipo) {
                                'IMAGE' => 'IMAGEM',
                                'DOCUMENT' => 'DOCUMENTO',
                                'AUDIO' => 'AUDIO',
                                'INTERACTIVE' => 'TEXTO',
                                default => 'TEXTO',
                            },
                            midiaUrl: $midiaUrl,
                            wamid: $mensagem['id'] ?? null,
                        );

                        // Processa no bot — passa buttonId para o fluxo
                        $this->bot->processar($telefone, $texto, $midiaUrl, $tipo === 'IMAGE' ? 'IMAGEM' : 'TEXTO', $buttonId);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erro no webhook WhatsApp', ['error' => $e->getMessage(), 'payload' => $payload]);
        }

        return response('OK', 200);
    }
}
