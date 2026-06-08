<?php

namespace App\Services\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\TipoAnexo;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Events\PosObra\ExecucaoIniciada;
use App\Events\PosObra\FinalizacaoSolicitada;
use App\Events\PosObra\PendenciaAprovada;
use App\Events\PosObra\PendenciaRegistrada;
use App\Events\PosObra\PendenciaRejeitada;
use App\Events\PosObra\PrazoInformado;
use App\Models\Obras;
use App\Models\PosObra\AnexoPendencia;
use App\Models\PosObra\AprovacaoFinalizacao;
use App\Models\PosObra\ConversaWhatsapp;
use App\Models\PosObra\Pendencia;
use App\Models\PosObra\WhatsappBotMensagem;
use App\Models\User;

class WhatsAppBotService
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private PendenciaService $pendenciaService,
    ) {}

    public function processar(string $telefone, string $texto, ?string $midiaUrl = null, string $tipoMidia = 'TEXTO', ?string $buttonId = null): void
    {
        $conversa = ConversaWhatsapp::firstOrCreate(
            ['telefone' => $telefone],
            ['perfil' => $this->detectarPerfil($telefone), 'fase' => 'INICIO', 'contexto' => []]
        );

        $conversa->ultima_mensagem_at = now();

        match ($conversa->perfil) {
            'LIDER' => $this->fluxoLider($conversa, $texto, $midiaUrl, $tipoMidia, $buttonId),
            'CONSTRUTORA' => $this->fluxoConstrutora($conversa, $texto, $midiaUrl, $tipoMidia, $buttonId),
            'GESTOR' => $this->fluxoGestor($conversa, $texto),
            default => $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('geral.desconhecido')),
        };

        $conversa->save();
    }

    // ─── Fluxo Líder ────────────────────────────────────────────────────────────

    private function fluxoLider(ConversaWhatsapp $conversa, string $texto, ?string $midiaUrl, string $tipo, ?string $buttonId): void
    {
        $fase = $conversa->fase;
        $ctx = $conversa->contexto ?? [];

        // Comando "menu" reinicia
        if (in_array(mb_strtolower(trim($texto)), ['menu', 'oi', 'olá', 'ola'])) {
            $this->liderInicio($conversa);

            return;
        }

        match ($fase) {
            'INICIO' => $this->liderInicio($conversa),
            'AGUARDA_OPCAO' => $this->liderOpcao($conversa, $texto, $buttonId),
            'AGUARDA_OBRA' => $this->liderObra($conversa, $texto, $ctx, $buttonId),
            'AGUARDA_DESCRICAO' => $this->liderDescricao($conversa, $texto, $ctx),
            'AGUARDA_LOCAL' => $this->liderLocal($conversa, $texto, $ctx),
            'AGUARDA_URGENCIA' => $this->liderUrgencia($conversa, $texto, $ctx, $buttonId),
            'AGUARDA_IMPACTO' => $this->liderImpacto($conversa, $texto, $ctx, $buttonId),
            'AGUARDA_FOTO_INICIAL' => $this->liderFotoInicial($conversa, $midiaUrl, $tipo, $ctx),
            'AGUARDA_APROVACAO' => $this->liderAprovacao($conversa, $texto, $ctx, $buttonId),
            'AGUARDA_MOTIVO_REJEICAO' => $this->liderMotivoRejeicao($conversa, $texto, $ctx),
            default => $this->liderInicio($conversa),
        };
    }

    private function liderInicio(ConversaWhatsapp $conversa): void
    {
        $this->whatsApp->enviarBotoes(
            $conversa->telefone,
            WhatsappBotMensagem::get('lider.inicio'),
            [
                ['id' => 'lider_nova', 'titulo' => 'Nova Pendência'],
                ['id' => 'lider_listar', 'titulo' => 'Listar Abertas'],
            ],
        );

        $conversa->fase = 'AGUARDA_OPCAO';
    }

    private function liderOpcao(ConversaWhatsapp $conversa, string $texto, ?string $buttonId): void
    {
        $opcao = $buttonId ?? trim($texto);

        if ($opcao === 'lider_nova' || $opcao === '1') {
            $obras = Obras::orderBy('sigla')
                ->get(['id', 'sigla', 'unidade']);

            if ($obras->isEmpty()) {
                $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.sem_obras'));
                $conversa->fase = 'INICIO';

                return;
            }

            $itens = $obras->map(fn ($o) => [
                'id' => 'obra_'.$o->id,
                'titulo' => $o->sigla,
                'descricao' => $o->unidade ?? '',
            ])->toArray();

            $this->enviarListaPaginada($conversa, $itens, 'obra', 0);

            $conversa->fase = 'AGUARDA_OBRA';
            $conversa->contexto = array_merge($conversa->contexto ?? [], [
                '_obras' => $obras->pluck('id')->toArray(),
                '_obras_itens' => $itens,
                '_obras_page' => 0,
            ]);
        } elseif ($opcao === 'lider_listar' || $opcao === '2') {
            $user = User::where('phone', $conversa->telefone)->first();
            $pendencias = Pendencia::query()
                ->where('lider_obra_id', $user?->id)
                ->whereNotIn('status', collect(StatusPendencia::cases())
                    ->filter(fn ($s) => $s->isTerminal())
                    ->map(fn ($s) => $s->value)
                    ->toArray()
                )
                ->with('obra')
                ->limit(5)
                ->get();

            if ($pendencias->isEmpty()) {
                $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.sem_pendencias'));
            } else {
                $lista = $pendencias->map(fn ($p) => "• *{$p->codigo}* — {$p->status->label()} | {$p->obra?->sigla}")->implode("\n");
                $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::formatar('lider.lista_pendencias', ['lista' => $lista]));
            }

            $conversa->fase = 'INICIO';
        } else {
            $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.opcao_invalida'));
        }
    }

    private function liderObra(ConversaWhatsapp $conversa, string $texto, array $ctx, ?string $buttonId): void
    {
        // "Ver mais" da paginação — envia próxima página
        if ($buttonId && str_starts_with($buttonId, 'ver_mais_obra_')) {
            $pagina = (int) str_replace('ver_mais_obra_', '', $buttonId);
            $itens = $ctx['_obras_itens'] ?? [];

            $this->enviarListaPaginada($conversa, $itens, 'obra', $pagina);

            $ctx['_obras_page'] = $pagina;
            $conversa->contexto = $ctx;

            return;
        }

        $obraId = null;

        // Resposta via lista interativa: id = "obra_{uuid}"
        if ($buttonId && str_starts_with($buttonId, 'obra_')) {
            $obraId = str_replace('obra_', '', $buttonId);
        }

        // Fallback: número digitado
        if (! $obraId) {
            $indice = (int) trim($texto) - 1;
            $obras = $ctx['_obras'] ?? [];
            if (isset($obras[$indice])) {
                $obraId = $obras[$indice];
            }
        }

        if (! $obraId) {
            $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.obra_invalida'));

            return;
        }

        $ctx['obras_id'] = $obraId;
        unset($ctx['_obras'], $ctx['_obras_itens'], $ctx['_obras_page']);
        $conversa->contexto = $ctx;
        $conversa->fase = 'AGUARDA_DESCRICAO';
        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.descricao'));
    }

    private function liderDescricao(ConversaWhatsapp $conversa, string $texto, array $ctx): void
    {
        $ctx['descricao'] = $texto;
        $conversa->contexto = $ctx;
        $conversa->fase = 'AGUARDA_LOCAL';
        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.local'));
    }

    private function liderLocal(ConversaWhatsapp $conversa, string $texto, array $ctx): void
    {
        $ctx['local_especifico'] = $texto;
        $conversa->contexto = $ctx;
        $conversa->fase = 'AGUARDA_URGENCIA';

        $itens = array_map(fn (UrgenciaPendencia $u) => [
            'id' => 'urgencia_'.$u->value,
            'titulo' => $u->label(),
            'descricao' => 'SLA: '.$u->slaHoras().'h',
        ], UrgenciaPendencia::cases());

        $this->whatsApp->enviarLista(
            $conversa->telefone,
            WhatsappBotMensagem::get('lider.urgencia'),
            'Ver urgências',
            $itens,
            tituloSecao: 'Nível de urgência',
        );
    }

    private function liderUrgencia(ConversaWhatsapp $conversa, string $texto, array $ctx, ?string $buttonId): void
    {
        $urgencia = null;

        // Resposta via lista: id = "urgencia_P1"
        if ($buttonId && str_starts_with($buttonId, 'urgencia_')) {
            $valor = str_replace('urgencia_', '', $buttonId);
            $urgencia = UrgenciaPendencia::tryFrom($valor);
        }

        // Fallback: número digitado
        if (! $urgencia) {
            $urgencia = match (trim($texto)) {
                '1' => UrgenciaPendencia::P1,
                '2' => UrgenciaPendencia::P2,
                '3' => UrgenciaPendencia::P3,
                default => null,
            };
        }

        if (! $urgencia) {
            $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.urgencia_invalida'));

            return;
        }

        $ctx['urgencia'] = $urgencia->value;
        $conversa->contexto = $ctx;
        $conversa->fase = 'AGUARDA_IMPACTO';

        $this->whatsApp->enviarBotoes(
            $conversa->telefone,
            WhatsappBotMensagem::get('lider.impacto'),
            [
                ['id' => 'impacto_sim', 'titulo' => 'Sim'],
                ['id' => 'impacto_nao', 'titulo' => 'Não'],
            ],
        );
    }

    private function liderImpacto(ConversaWhatsapp $conversa, string $texto, array $ctx, ?string $buttonId): void
    {
        $resposta = $buttonId ?? trim($texto);

        $impacto = match ($resposta) {
            'impacto_sim', '1' => true,
            'impacto_nao', '2' => false,
            default => null,
        };

        if ($impacto === null) {
            $this->whatsApp->enviarBotoes(
                $conversa->telefone,
                WhatsappBotMensagem::get('lider.impacto'),
                [
                    ['id' => 'impacto_sim', 'titulo' => 'Sim'],
                    ['id' => 'impacto_nao', 'titulo' => 'Não'],
                ],
            );

            return;
        }

        $ctx['impacto_operacao'] = $impacto;
        $conversa->contexto = $ctx;
        $conversa->fase = 'AGUARDA_FOTO_INICIAL';
        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.foto'));
    }

    private function liderFotoInicial(ConversaWhatsapp $conversa, ?string $midiaUrl, string $tipo, array $ctx): void
    {
        $pendencia = $this->criarPendenciaDoContexto($ctx, $conversa->telefone);

        if ($midiaUrl && $tipo === 'IMAGEM') {
            AnexoPendencia::create([
                'pendencia_id' => $pendencia->id,
                'tipo' => TipoAnexo::FOTO_INICIAL->value,
                'url' => $midiaUrl,
                'uploaded_by' => $pendencia->lider_obra_id ?? $pendencia->gestor_id,
            ]);
        }

        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::formatar('lider.pendencia_registrada', ['codigo' => $pendencia->codigo]));

        $conversa->fase = 'INICIO';
        $conversa->contexto = [];
        $conversa->pendencia_id = $pendencia->id;

        event(new PendenciaRegistrada($pendencia));
    }

    private function liderAprovacao(ConversaWhatsapp $conversa, string $texto, array $ctx, ?string $buttonId): void
    {
        $pendencia = Pendencia::find($ctx['pendencia_id'] ?? null);
        if (! $pendencia) {
            $conversa->fase = 'INICIO';

            return;
        }

        $aprovacao = $pendencia->aprovacaoFinalizacao;
        if (! $aprovacao) {
            $conversa->fase = 'INICIO';

            return;
        }

        $resposta = $buttonId ?? trim($texto);

        if ($resposta === 'aprovar' || $resposta === '1') {
            $aprovacao->update(['status' => 'APROVADA', 'aprovado_por' => $pendencia->lider_obra_id]);
            $this->pendenciaService->registrarAtualizacaoStatus($pendencia, StatusPendencia::CONCLUIDA, 'Bot WhatsApp (Líder)');
            $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::formatar('lider.pendencia_aprovada', ['codigo' => $pendencia->codigo]));
            event(new PendenciaAprovada($pendencia));
        } elseif ($resposta === 'rejeitar' || $resposta === '2') {
            $conversa->fase = 'AGUARDA_MOTIVO_REJEICAO';
            $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('lider.pedir_motivo_rejeicao'));

            return;
        } else {
            $this->whatsApp->enviarBotoes(
                $conversa->telefone,
                'Deseja aprovar ou rejeitar a finalização?',
                [
                    ['id' => 'aprovar', 'titulo' => '✅ Aprovar'],
                    ['id' => 'rejeitar', 'titulo' => '❌ Rejeitar'],
                ],
            );

            return;
        }

        $conversa->fase = 'INICIO';
        $conversa->contexto = [];
    }

    private function liderMotivoRejeicao(ConversaWhatsapp $conversa, string $texto, array $ctx): void
    {
        $pendencia = Pendencia::find($ctx['pendencia_id'] ?? null);
        if (! $pendencia) {
            $conversa->fase = 'INICIO';

            return;
        }

        $aprovacao = $pendencia->aprovacaoFinalizacao;
        if ($aprovacao) {
            $aprovacao->update(['status' => 'REJEITADA', 'aprovado_por' => $pendencia->lider_obra_id]);
        }

        $this->pendenciaService->registrarAtualizacaoStatus(
            $pendencia,
            StatusPendencia::EM_EXECUCAO,
            'Bot WhatsApp (Líder)',
            'Rejeitado pelo líder: '.trim($texto),
        );

        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::formatar('lider.pendencia_rejeitada', ['codigo' => $pendencia->codigo]));
        event(new PendenciaRejeitada($pendencia));

        $conversa->fase = 'INICIO';
        $conversa->contexto = [];
    }

    // ─── Fluxo Fornecedor ──────────────────────────────────────────────────────

    private function fluxoConstrutora(ConversaWhatsapp $conversa, string $texto, ?string $midiaUrl, string $tipo, ?string $buttonId): void
    {
        $fase = $conversa->fase;
        $ctx = $conversa->contexto ?? [];

        match ($fase) {
            'AGUARDA_PRAZO' => $this->construtoraInformaPrazo($conversa, $texto, $ctx),
            'AGUARDA_CONFIRMACAO_INICIO' => $this->construtoraIniciaExecucao($conversa, $texto, $ctx, $buttonId),
            'AGUARDA_EVIDENCIAS' => $this->construtoraEnviaEvidencias($conversa, $texto, $midiaUrl, $tipo, $ctx, $buttonId),
            default => $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('fornecedor.aguardando_sistema')),
        };
    }

    private function construtoraInformaPrazo(ConversaWhatsapp $conversa, string $texto, array $ctx): void
    {
        $pendencia = Pendencia::find($ctx['pendencia_id'] ?? null);
        if (! $pendencia) {
            return;
        }

        $pendencia->data_termino = $texto;
        $pendencia->save();

        $this->pendenciaService->registrarAtualizacaoStatus($pendencia, StatusPendencia::PENDENTE_COM_PRAZO, 'Bot WhatsApp (Construtora)', "Prazo informado: {$texto}");

        $this->whatsApp->enviarBotoes(
            $conversa->telefone,
            WhatsappBotMensagem::get('fornecedor.prazo_registrado')."\n\nQuando iniciar a execução, clique abaixo:",
            [
                ['id' => 'iniciar_execucao', 'titulo' => '🔧 Iniciar Execução'],
            ],
        );

        $conversa->fase = 'AGUARDA_CONFIRMACAO_INICIO';
        event(new PrazoInformado($pendencia));
    }

    private function construtoraIniciaExecucao(ConversaWhatsapp $conversa, string $texto, array $ctx, ?string $buttonId): void
    {
        $resposta = $buttonId ?? strtoupper(trim($texto));

        if ($resposta !== 'iniciar_execucao' && $resposta !== 'INICIAR') {
            $this->whatsApp->enviarBotoes(
                $conversa->telefone,
                WhatsappBotMensagem::get('fornecedor.aguarda_iniciar'),
                [
                    ['id' => 'iniciar_execucao', 'titulo' => '🔧 Iniciar Execução'],
                ],
            );

            return;
        }

        $pendencia = Pendencia::find($ctx['pendencia_id'] ?? null);
        if (! $pendencia) {
            return;
        }

        $this->pendenciaService->registrarAtualizacaoStatus($pendencia, StatusPendencia::EM_EXECUCAO, 'Bot WhatsApp (Construtora)');

        $this->whatsApp->enviarBotoes(
            $conversa->telefone,
            WhatsappBotMensagem::get('fornecedor.execucao_iniciada')."\n\nEnvie fotos das evidências. Ao finalizar, clique abaixo:",
            [
                ['id' => 'concluir_execucao', 'titulo' => '✅ Concluir'],
            ],
        );

        $conversa->fase = 'AGUARDA_EVIDENCIAS';
        event(new ExecucaoIniciada($pendencia));
    }

    private function construtoraEnviaEvidencias(ConversaWhatsapp $conversa, string $texto, ?string $midiaUrl, string $tipo, array $ctx, ?string $buttonId): void
    {
        $pendencia = Pendencia::find($ctx['pendencia_id'] ?? null);
        if (! $pendencia) {
            return;
        }

        if ($midiaUrl && $tipo === 'IMAGEM') {
            AnexoPendencia::create([
                'pendencia_id' => $pendencia->id,
                'tipo' => TipoAnexo::EVIDENCIA->value,
                'url' => $midiaUrl,
                'uploaded_by' => $pendencia->gestor_id,
            ]);

            $this->whatsApp->enviarBotoes(
                $conversa->telefone,
                WhatsappBotMensagem::get('fornecedor.foto_recebida'),
                [
                    ['id' => 'concluir_execucao', 'titulo' => '✅ Concluir'],
                ],
            );
        } elseif ($buttonId === 'concluir_execucao' || strtoupper(trim($texto)) === 'CONCLUIR') {
            $this->pedirAprovacao($pendencia, $conversa);
        } else {
            $this->whatsApp->enviarBotoes(
                $conversa->telefone,
                WhatsappBotMensagem::get('fornecedor.orientacao_evidencias'),
                [
                    ['id' => 'concluir_execucao', 'titulo' => '✅ Concluir'],
                ],
            );
        }
    }

    private function pedirAprovacao(Pendencia $pendencia, ConversaWhatsapp $conversa): void
    {
        AprovacaoFinalizacao::create([
            'pendencia_id' => $pendencia->id,
            'solicitado_por' => $pendencia->gestor_id,
            'status' => 'PENDENTE',
        ]);

        $this->pendenciaService->registrarAtualizacaoStatus($pendencia, StatusPendencia::AGUARDANDO_APROVACAO, 'Bot WhatsApp (Construtora)');
        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::get('fornecedor.aprovacao_solicitada'));

        $conversa->fase = 'AGUARDA_RESOLUCAO';
        event(new FinalizacaoSolicitada($pendencia));
    }

    // ─── Fluxo Gestor ───────────────────────────────────────────────────────────

    private function fluxoGestor(ConversaWhatsapp $conversa, string $texto): void
    {
        $this->whatsApp->enviar($conversa->telefone, WhatsappBotMensagem::formatar('gestor.redirect', ['url' => config('app.url')]));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Envia lista paginada — 9 itens + "Ver mais" quando há continuação.
     */
    private function enviarListaPaginada(ConversaWhatsapp $conversa, array $itens, string $prefixo, int $pagina): void
    {
        $labels = [
            'obra' => ['corpo' => WhatsappBotMensagem::get('lider.lista_obras_header') ?? 'Selecione a obra:', 'botao' => 'Ver obras', 'secao' => 'Obras disponíveis'],
        ];

        $cfg = $labels[$prefixo] ?? ['corpo' => 'Selecione:', 'botao' => 'Ver opções', 'secao' => 'Opções'];

        $this->whatsApp->enviarLista(
            $conversa->telefone,
            $cfg['corpo'],
            $cfg['botao'],
            $itens,
            tituloSecao: $cfg['secao'],
            pagina: $pagina,
            prefixoPaginacao: $prefixo,
        );
    }

    private function detectarPerfil(string $telefone): string
    {
        $user = User::where('phone', $telefone)
            ->orWhereHas('construtora', fn ($q) => $q->where('telefone_whatsapp', $telefone))
            ->first();

        if (! $user) {
            return 'DESCONHECIDO';
        }
        if ($user->is_lider_obra) {
            return 'LIDER';
        }
        if ($user->hasRole('gestor_obra') || $user->hasRole('super_admin')) {
            return 'GESTOR';
        }

        return 'CONSTRUTORA';
    }

    private function criarPendenciaDoContexto(array $ctx, string $telefone): Pendencia
    {
        $user = User::where('phone', $telefone)->first();

        return Pendencia::create([
            'codigo' => $this->pendenciaService->gerarCodigo(),
            'obras_id' => $ctx['obras_id'] ?? null,
            'gestor_id' => $ctx['gestor_id'] ?? 1,
            'lider_obra_id' => $user?->id,
            'descricao' => $ctx['descricao'] ?? '',
            'local_especifico' => $ctx['local_especifico'] ?? null,
            'urgencia' => $ctx['urgencia'] ?? UrgenciaPendencia::P1->value,
            'impacto_operacao' => $ctx['impacto_operacao'] ?? false,
            'status' => StatusPendencia::REGISTRADA->value,
        ]);
    }
}
