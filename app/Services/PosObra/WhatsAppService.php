<?php

namespace App\Services\PosObra;

use App\Models\PosObra\MensagemWhatsapp;
use App\Models\PosObra\Pendencia;
use App\Models\PosObra\WhatsappConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?WhatsappConfig $config;

    public function __construct()
    {
        $this->config = WhatsappConfig::instancia();
    }

    public function enviar(string $telefone, string $mensagem, ?Pendencia $pendencia = null): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefone,
            'type' => 'text',
            'text' => ['body' => $mensagem],
        ];

        return $this->enviarPayload($telefone, $payload, $mensagem, $pendencia);
    }

    /**
     * Envia mensagem com botões de resposta rápida (máx. 3).
     *
     * @param  array<array{id: string, titulo: string}>  $botoes
     */
    public function enviarBotoes(string $telefone, string $corpo, array $botoes, ?Pendencia $pendencia = null, ?string $cabecalho = null, ?string $rodape = null): bool
    {
        $buttons = array_map(fn (array $b) => [
            'type' => 'reply',
            'reply' => [
                'id' => $b['id'],
                'title' => mb_substr($b['titulo'], 0, 20),
            ],
        ], array_slice($botoes, 0, 3));

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $corpo],
            'action' => ['buttons' => $buttons],
        ];

        if ($cabecalho) {
            $interactive['header'] = ['type' => 'text', 'text' => $cabecalho];
        }
        if ($rodape) {
            $interactive['footer'] = ['text' => $rodape];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefone,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->enviarPayload($telefone, $payload, $corpo, $pendencia);
    }

    /**
     * Envia mensagem com lista interativa.
     *
     * Se houver mais de 10 itens, envia 9 + item "Ver mais" para paginação.
     *
     * @param  string  $textoBotao  Texto do botão que abre a lista (máx. 20 chars)
     * @param  array<array{id: string, titulo: string, descricao?: string}>  $itens  Todos os itens (sem limite)
     * @param  int  $pagina  Página atual (0-indexed)
     * @param  string  $prefixoPaginacao  Prefixo para o id do botão "ver mais" (ex: "obra" gera "ver_mais_obra_1")
     */
    public function enviarLista(
        string $telefone,
        string $corpo,
        string $textoBotao,
        array $itens,
        ?Pendencia $pendencia = null,
        ?string $cabecalho = null,
        ?string $tituloSecao = null,
        int $pagina = 0,
        string $prefixoPaginacao = 'lista',
    ): bool {
        $totalItens = count($itens);

        // Sem paginação: cabe tudo em 10
        if ($totalItens <= 10) {
            $offset = 0;
            $itensPagina = $itens;
            $temMais = false;
        } else {
            // Com paginação: cada página mostra 9 + "Ver mais", exceto a última (até 10)
            $offset = $pagina * 9;
            $restantes = $totalItens - $offset;
            $temMais = $restantes > 10;
            $porPagina = $temMais ? 9 : $restantes;
            $itensPagina = array_slice($itens, $offset, $porPagina);
        }

        $rows = array_map(fn (array $item) => array_filter([
            'id' => $item['id'],
            'title' => mb_substr($item['titulo'], 0, 24),
            'description' => isset($item['descricao']) ? mb_substr($item['descricao'], 0, 72) : null,
        ]), $itensPagina);

        if ($temMais) {
            $proximaPagina = $pagina + 1;
            $rows[] = [
                'id' => "ver_mais_{$prefixoPaginacao}_{$proximaPagina}",
                'title' => 'Ver mais →',
                'description' => 'Mostrar próximos itens',
            ];
        }

        $corpoComPaginacao = $corpo;
        if ($pagina > 0) {
            $inicio = $offset + 1;
            $fim = $offset + count($itensPagina);
            $corpoComPaginacao = "{$corpo}\n\nExibindo {$inicio}-{$fim} de {$totalItens}";
        }

        $interactive = [
            'type' => 'list',
            'body' => ['text' => $corpoComPaginacao],
            'action' => [
                'button' => mb_substr($textoBotao, 0, 20),
                'sections' => [
                    [
                        'title' => $tituloSecao ?? 'Opções',
                        'rows' => $rows,
                    ],
                ],
            ],
        ];

        if ($cabecalho) {
            $interactive['header'] = ['type' => 'text', 'text' => $cabecalho];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefone,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->enviarPayload($telefone, $payload, $corpoComPaginacao, $pendencia);
    }

    private function enviarPayload(string $telefone, array $payload, string $mensagemLog, ?Pendencia $pendencia = null): bool
    {
        if (! $this->config || ! $this->config->ativo) {
            Log::warning('WhatsApp não configurado ou inativo.');

            return false;
        }

        $response = Http::withToken($this->config->token)
            ->post("https://graph.facebook.com/v19.0/{$this->config->phone_number_id}/messages", $payload);

        $wamid = $response->json('messages.0.id');
        $sucesso = $response->successful() && $wamid;

        $this->registrar(
            telefone: $telefone,
            direcao: 'ENVIADA',
            mensagem: $mensagemLog,
            tipo: 'TEXTO',
            wamid: $wamid ?? null,
            statusEntrega: $sucesso ? 'ENVIADA' : 'FALHA',
            pendencia: $pendencia,
        );

        if (! $sucesso) {
            Log::error('Falha ao enviar WhatsApp', ['response' => $response->json()]);
        }

        return $sucesso;
    }

    public function registrar(
        string $telefone,
        string $direcao,
        ?string $mensagem,
        string $tipo = 'TEXTO',
        ?string $midiaUrl = null,
        ?string $statusEntrega = null,
        ?string $wamid = null,
        ?Pendencia $pendencia = null,
    ): MensagemWhatsapp {
        return MensagemWhatsapp::create([
            'pendencia_id' => $pendencia?->id,
            'telefone' => $telefone,
            'direcao' => $direcao,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'midia_url' => $midiaUrl,
            'status_entrega' => $statusEntrega,
            'wamid' => $wamid,
        ]);
    }

    public function marcarEntregue(string $wamid, string $status): void
    {
        MensagemWhatsapp::where('wamid', $wamid)->update(['status_entrega' => $status]);
    }

    /**
     * Envia mensagem via template aprovado pela Meta.
     * Obrigatório para mensagens iniciadas pelo sistema (fora da janela de 24h do usuário).
     *
     * @param  array<int, string|int>  $parametros  Valores para {{1}}, {{2}}, etc. no corpo do template
     */
    public function enviarTemplate(
        string $telefone,
        string $nomeTemplate,
        array $parametros = [],
        string $idioma = 'pt_BR',
    ): bool {
        $template = [
            'name'     => $nomeTemplate,
            'language' => ['code' => $idioma],
        ];

        if (! empty($parametros)) {
            $template['components'] = [
                [
                    'type'       => 'body',
                    'parameters' => array_map(
                        fn ($p) => ['type' => 'text', 'text' => (string) $p],
                        $parametros
                    ),
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $telefone,
            'type'              => 'template',
            'template'          => $template,
        ];

        return $this->enviarPayload($telefone, $payload, "[Template:{$nomeTemplate}]");
    }

    /**
     * Normaliza número para formato E.164 com DDI do Brasil.
     * Exemplo: "11999999999" → "5511999999999"
     */
    public static function formatarTelefone(string $telefone): ?string
    {
        $digits = preg_replace('/\D/', '', $telefone);

        if (empty($digits)) {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    public function getConfig(): ?WhatsappConfig
    {
        return $this->config;
    }
}
