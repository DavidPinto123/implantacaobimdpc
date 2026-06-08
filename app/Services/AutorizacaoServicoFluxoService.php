<?php

namespace App\Services;

use App\Enums\AsStatus;
use App\Enums\ModoSaldoFiscal;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Mail\AutorizacaoServicoMail;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscalItem;
use App\Models\User;
use App\Services\ControleNotaFiscal\ControleNotaFiscalVinculoResolver;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AutorizacaoServicoFluxoService
{
    public function __construct(
        protected AutorizacaoServicoService $autorizacaoServicoService,
        protected AutorizacaoServicoPdfService $pdfService,
        protected ControleNotaFiscalVinculoResolver $vinculoResolver,
    ) {}

    public function criarParaItem(ControleNotaFiscalItem $item, User $user, bool $permitirValoresZerados = false): AutorizacaoServico
    {
        return DB::transaction(function () use ($item, $permitirValoresZerados, $user): AutorizacaoServico {
            $item->loadMissing(['autorizacaoServico', 'controleNotaFiscal.obra', 'asEscopo']);

            if ($item->autorizacaoServico) {
                return $item->autorizacaoServico;
            }

            $controleNotaFiscal = $item->controleNotaFiscal;
            $obra = $controleNotaFiscal?->obra;
            $asEscopo = $item->asEscopo;
            $construtora = Construtora::query()
                ->where('nome', $item->empresa)
                ->first();
            $construtora ??= $controleNotaFiscal?->construtora_id
                ? Construtora::query()->find($controleNotaFiscal->construtora_id)
                : null;
            $construtoraId = $construtora?->id ?? $controleNotaFiscal?->construtora_id;
            $valorEstimado = (float) ($item->valor_estimado_as ?? 0);
            $valorFechado = (float) ($item->valor_global_a ?? 0);
            $valorFechado = $valorFechado > 0 ? $valorFechado : $valorEstimado;
            $obraId = (int) $controleNotaFiscal?->obra_id;
            $tipoUnidade = (string) $controleNotaFiscal?->tipo_unidade;

            if (! $asEscopo || ! $construtoraId) {
                throw new DomainException('Preencha escopo e fornecedor antes de criar a AS.');
            }

            if (! $controleNotaFiscal || $obraId <= 0 || $tipoUnidade === '') {
                throw new DomainException($this->mensagemBloqueioFiscal('controle_nao_encontrado'));
            }

            if ($controleNotaFiscal->status === $controleNotaFiscal::STATUS_ENCERRADO) {
                throw new DomainException($this->mensagemBloqueioFiscal('controle_encerrado'));
            }

            if (! $permitirValoresZerados && (float) $valorFechado <= 0) {
                throw new DomainException('Preencha o valor fechado antes de criar a AS.');
            }

            $complementoService = app(AutorizacaoServicoComplementoService::class);
            $numeroComplemento = filled($item->numero_complemento)
                ? (string) $item->numero_complemento
                : ($complementoService->complementosExistentes($obraId, (int) $item->as_escopo_id, $item->id) === []
                    ? ''
                    : ($this->autorizacaoServicoService
                        ->gerarProximoComplementoParaEscopo($obraId, (int) $item->as_escopo_id, $item->id) ?? ''));
            $numeroAs = $this->autorizacaoServicoService->gerarNumeroAsEstruturado(
                $obra,
                $asEscopo,
                $construtora,
            );

            $destino = $this->vinculoResolver->resolveAs(
                obraId: $obraId,
                tipoUnidade: $tipoUnidade,
                autorizacaoServicoId: null,
                construtoraId: $construtoraId,
                modoSaldo: ModoSaldoFiscal::Realizado,
                itemPrincipal: $item,
            );

            if ($destino->bloqueado()) {
                throw new DomainException($this->mensagemBloqueioFiscal($destino->motivoBloqueio));
            }

            $autorizacaoServico = AutorizacaoServico::query()->create([
                'obra_id' => $controleNotaFiscal?->obra_id,
                'as_escopo_id' => $item->as_escopo_id,
                'construtora_id' => $construtoraId,
                'status' => AsStatus::CRIADA,
                'numero_as' => $numeroAs,
                'numero_complemento' => $numeroComplemento,
                'valor' => $valorFechado,
                'valor_estimado' => $valorEstimado,
                'valor_inicial' => $valorEstimado,
                'controle_nota_fiscal_item_id' => $item->id,
                'created_by_id' => $user->id,
            ]);

            $item->update([
                'numero_complemento' => $numeroComplemento,
            ]);

            return $autorizacaoServico->refresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $parcelamento
     * @param  array{data_inicio_servico?: ?string, data_termino_servico?: ?string, data_entrega_material?: ?string, desconto_autorizacao_servico?: mixed, descricao_servico_pdf?: ?string, itens_descricao_servico_pdf?: array<int, array<string, mixed>>|null, anexos_autorizacao_servico?: array<int, string>|null}|null  $datas
     */
    public function criarEGerarParaItem(
        ControleNotaFiscalItem $item,
        User $user,
        bool $permitirValoresZerados = false,
        ?array $parcelamento = null,
        ?array $datas = null,
    ): AutorizacaoServico {
        return DB::transaction(function () use ($datas, $item, $parcelamento, $permitirValoresZerados, $user): AutorizacaoServico {
            $autorizacaoServico = $this->criarParaItem($item, $user, $permitirValoresZerados);

            return $this->gerar($autorizacaoServico, $permitirValoresZerados, $parcelamento, $datas);
        });
    }

    public function enviar(
        AutorizacaoServico $autorizacaoServico,
        User $user,
        bool $permitirValoresZerados = false,
        ?array $destinatarios = null,
        ?array $copias = null,
        ?array $copiasOcultas = null,
    ): AutorizacaoServico {
        $autorizacaoServico->loadMissing(['construtora', 'obra', 'asEscopo', 'itens.controleNotaFiscal.obra']);
        $this->preencherFornecedorPelaLinha($autorizacaoServico);

        $destinatarios = $this->normalizarEmails($destinatarios ?? []);
        $copias = $this->normalizarEmails($copias ?? []);
        $copiasOcultas = $this->normalizarEmails($copiasOcultas ?? []);

        $this->validarEnvio($autorizacaoServico, $permitirValoresZerados, $destinatarios, $copias, $copiasOcultas);

        $pdfPath = $this->garantirPdf($autorizacaoServico);
        $disk = Storage::disk($this->pdfService->diskName());
        $pdfBinary = (string) $disk->get($pdfPath);
        $anexos = $this->conteudoAnexos($autorizacaoServico);

        Log::info('Enviando AS por e-mail.', [
            'autorizacao_servico_id' => $autorizacaoServico->id,
            'numero_as' => $autorizacaoServico->numero_as,
            'destinatarios' => $destinatarios,
            'copias' => $copias,
            'copias_ocultas' => $copiasOcultas,
            'pdf_path' => $pdfPath,
        ]);

        Mail::to($destinatarios)->cc($copias)->bcc($copiasOcultas)->send(new AutorizacaoServicoMail(
            autorizacaoServico: $autorizacaoServico,
            pdfBinary: $pdfBinary,
            nomeArquivo: $this->pdfService->nomeArquivo($autorizacaoServico),
            remetente: $user,
            anexos: $anexos,
        ));

        return DB::transaction(function () use ($autorizacaoServico, $user, $destinatarios): AutorizacaoServico {
            $enviadoEm = now();
            $itens = $autorizacaoServico->itens()
                ->with(['autorizacaoServico.construtora', 'controleNotaFiscal.obra'])
                ->get();

            foreach ($itens as $item) {
                $this->liberarItemParaFornecedor($item, $enviadoEm, $destinatarios);
            }

            $dados = [
                'status' => AsStatus::ENVIADA,
                'enviado_em' => $enviadoEm,
                'enviado_por_id' => $user->id,
            ];

            $autorizacaoServico->update($dados);

            return $autorizacaoServico->refresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $parcelamento
     * @param  array{data_inicio_servico?: ?string, data_termino_servico?: ?string, data_entrega_material?: ?string, desconto_autorizacao_servico?: mixed, descricao_servico_pdf?: ?string, itens_descricao_servico_pdf?: array<int, array<string, mixed>>|null, anexos_autorizacao_servico?: array<int, string>|null}|null  $datas
     */
    public function gerar(
        AutorizacaoServico $autorizacaoServico,
        bool $permitirValoresZerados = false,
        ?array $parcelamento = null,
        ?array $datas = null,
    ): AutorizacaoServico {
        $autorizacaoServico->loadMissing(['construtora', 'obra', 'asEscopo', 'itens']);
        $this->validarGeracao($autorizacaoServico, $permitirValoresZerados);

        $desconto = $datas !== null
            ? $this->parseNumeroBr($datas['desconto_autorizacao_servico'] ?? null)
            : null;
        $desconto ??= (float) ($autorizacaoServico->desconto_autorizacao_servico ?? 0);
        $desconto = max(round($desconto, 2), 0.0);
        $valorTotal = round((float) $autorizacaoServico->valor, 2);
        $valorFechado = max($valorTotal, 0.0);

        if ($datas !== null) {
            $autorizacaoServico->forceFill([
                'valor' => $valorFechado,
                'data_inicio_servico' => $datas['data_inicio_servico'] ?? null,
                'data_termino_servico' => $datas['data_termino_servico'] ?? null,
                'data_entrega_material' => $datas['data_entrega_material'] ?? null,
                'desconto_autorizacao_servico' => $desconto,
                'descricao_servico_pdf' => filled($datas['descricao_servico_pdf'] ?? null)
                    ? trim((string) $datas['descricao_servico_pdf'])
                    : null,
                'itens_descricao_servico_pdf' => $this->normalizarItensDescricaoServico((array) ($datas['itens_descricao_servico_pdf'] ?? [])),
                'anexos_autorizacao_servico' => array_values(array_filter((array) ($datas['anexos_autorizacao_servico'] ?? []))),
            ])->saveQuietly();
        }

        if ($parcelamento !== null) {
            $autorizacaoServico->forceFill([
                'parcelamento_autorizacao_servico' => $this->normalizarParcelamento($parcelamento, $valorFechado),
            ])->saveQuietly();
        }

        $path = $this->pdfService->generateAndStorePdf($autorizacaoServico);

        Log::info('PDF da AS gerado.', [
            'autorizacao_servico_id' => $autorizacaoServico->id,
            'numero_as' => $autorizacaoServico->numero_as,
            'pdf_path' => $path,
        ]);

        return $autorizacaoServico->refresh();
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}>
     */
    public function normalizarItensDescricaoServico(array $itens): array
    {
        return collect($itens)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->take(1)
            ->map(function (array $item): array {
                $arquivos = array_values(array_filter((array) ($item['descricao_arquivo'] ?? [])));
                $descricao = filled($item['descricao'] ?? null) ? trim((string) $item['descricao']) : null;
                $tipoDescricao = (string) ($item['descricao_tipo'] ?? '');
                $tipoDescricao = in_array($tipoDescricao, ['texto', 'arquivo'], true)
                    ? $tipoDescricao
                    : ($arquivos === [] ? 'texto' : 'arquivo');

                return [
                    'descricao_tipo' => $tipoDescricao,
                    'descricao' => $descricao,
                    'descricao_arquivo' => $arquivos,
                ];
            })
            ->filter(fn (array $item): bool => filled($item['descricao']) || $item['descricao_arquivo'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $parcelamento
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    public function normalizarParcelamento(array $parcelamento, float $total): array
    {
        $parcelas = collect($parcelamento)
            ->filter(fn (mixed $parcela): bool => is_array($parcela))
            ->map(function (array $parcela, int $indice): array {
                return [
                    'parcela' => filled($parcela['parcela'] ?? null)
                        ? (string) $parcela['parcela']
                        : 'Parcela '.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                    'percentual' => round((float) ($parcela['percentual'] ?? 0), 2),
                    'valor' => round((float) ($parcela['valor'] ?? 0), 2),
                    'observacao' => (string) ($parcela['observacao'] ?? ''),
                ];
            })
            ->filter(fn (array $parcela): bool => $parcela['valor'] > 0 || $parcela['percentual'] > 0)
            ->values();

        if ($parcelas->isEmpty()) {
            if (round($total, 2) === 0.0) {
                return [[
                    'parcela' => 'Parcela 01',
                    'percentual' => 0.0,
                    'valor' => 0.0,
                    'observacao' => '',
                ]];
            }

            throw new DomainException('Informe ao menos uma parcela para gerar o PDF.');
        }

        $somaPercentual = round((float) $parcelas->sum('percentual'), 2);

        if (abs($somaPercentual - 100.0) > 0.01) {
            throw new DomainException('A soma dos percentuais do parcelamento deve ser 100%.');
        }

        $soma = round((float) $parcelas->sum('valor'), 2);

        if (abs($soma - round($total, 2)) > 0.01) {
            throw new DomainException('A soma das parcelas deve ser igual ao Valor fechado da AS.');
        }

        return $parcelas->all();
    }

    public function abrirOrcamento(AutorizacaoServico $autorizacaoServico): AutorizacaoServico
    {
        return DB::transaction(function () use ($autorizacaoServico): AutorizacaoServico {
            if ($autorizacaoServico->status === AsStatus::CANCELADA) {
                throw new DomainException('AS cancelada não pode ser enviada.');
            }

            if ($autorizacaoServico->status !== AsStatus::CRIADA) {
                throw new DomainException('A AS só pode ser enviada depois de criada.');
            }

            return $autorizacaoServico->refresh();
        });
    }

    public function cancelar(AutorizacaoServico $autorizacaoServico, string $motivo, User $user): AutorizacaoServico
    {
        return DB::transaction(function () use ($autorizacaoServico, $motivo, $user): AutorizacaoServico {
            if ($autorizacaoServico->status === AsStatus::CANCELADA) {
                return $autorizacaoServico->refresh();
            }

            $temNotaAprovada = $autorizacaoServico->itens()
                ->whereHas('notas', function ($query): void {
                    $query->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value);
                })
                ->exists();

            if ($temNotaAprovada && ! $this->podeCancelarComNotaAprovada($user)) {
                throw new DomainException('Não é possível cancelar uma AS com nota fiscal aprovada.');
            }

            $autorizacaoServico->update([
                'status' => AsStatus::CANCELADA,
                'cancelado_em' => now(),
                'cancelado_por_id' => $user->id,
                'motivo_cancelamento' => $motivo,
            ]);

            return $autorizacaoServico->refresh();
        });
    }

    public function podeCancelarComNotaAprovada(User $user): bool
    {
        return $user->can('CancelApproved:AutorizacaoServico');
    }

    /**
     * @param  array<int, string>  $emailsParaNotificacao
     */
    protected function liberarItemParaFornecedor(ControleNotaFiscalItem $item, Carbon $liberadoEm, array $emailsParaNotificacao): void
    {
        if ($item->liberado_para_fornecedor_at !== null) {
            return;
        }

        $construtora = $item->autorizacaoServico?->construtora;
        $empresaNome = trim((string) ($item->empresa ?: $construtora?->nome));

        if ($empresaNome === '') {
            throw new DomainException('Defina a Empresa na linha antes de liberar para fornecedor.');
        }

        if (! $construtora) {
            $construtora = Construtora::query()
                ->where('nome', $empresaNome)
                ->first();
        }

        if (! $construtora) {
            throw new DomainException('A empresa "'.$empresaNome.'" não foi localizada no cadastro de fornecedores.');
        }

        $item->forceFill([
            'empresa' => $empresaNome,
            'liberado_para_fornecedor_at' => $liberadoEm,
        ])->save();

        $destinatarios = $this->usuariosAtivosPorEmails($emailsParaNotificacao);

        if ($destinatarios->isEmpty()) {
            return;
        }

        $unidade = $item->controleNotaFiscal?->obra?->unidade ?? '-';
        $escopo = (string) ($item->escopo ?? '');
        $numeroAs = (string) ($item->numero_as ?? '');

        Notification::make()
            ->title('Item liberado para fornecedor')
            ->body(
                'Foi liberado o item '.($numeroAs !== '' ? $numeroAs.' - ' : '').$escopo.
                ' da unidade '.$unidade.'. Fica autorizado a emissão da Nota Fiscal.'
            )
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->actions([
                Action::make('abrir')
                    ->label('Importar nota fiscal')
                    ->url(ConstrutoraControlesNotaFiscalPage::getUrl()),
            ])
            ->sendToDatabase($destinatarios);
    }

    /**
     * @param  array<int, mixed>  $emails
     * @return Collection<int, User>
     */
    protected function usuariosAtivosPorEmails(array $emails): Collection
    {
        $emails = $this->normalizarEmails($emails);

        if ($emails === []) {
            return User::query()->whereRaw('1 = 0')->get();
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('email', $emails)
            ->get();
    }

    protected function garantirPdf(AutorizacaoServico $autorizacaoServico): string
    {
        $path = (string) $autorizacaoServico->anexo_autorizacao_servico;

        if ($path !== '' && Storage::disk($this->pdfService->diskName())->exists($path)) {
            return $path;
        }

        return $this->pdfService->generateAndStorePdf($autorizacaoServico);
    }

    /**
     * @return array<int, array{conteudo: string, nome: string, mime: string}>
     */
    protected function conteudoAnexos(AutorizacaoServico $autorizacaoServico): array
    {
        $paths = array_values(array_filter((array) $autorizacaoServico->anexos_autorizacao_servico));

        if ($paths === []) {
            return [];
        }

        $disk = Storage::disk($this->pdfService->diskName());

        return collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && $disk->exists($path))
            ->map(fn (string $path): array => [
                'conteudo' => (string) $disk->get($path),
                'nome' => basename($path),
                'mime' => $disk->mimeType($path) ?: 'application/octet-stream',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $destinatarios
     * @param  array<int, string>  $copias
     * @param  array<int, string>  $copiasOcultas
     */
    protected function validarEnvio(
        AutorizacaoServico $autorizacaoServico,
        bool $permitirValoresZerados = false,
        array $destinatarios = [],
        array $copias = [],
        array $copiasOcultas = [],
    ): void {
        if ($autorizacaoServico->status === AsStatus::CANCELADA) {
            throw new DomainException('Não é possível enviar uma AS cancelada.');
        }

        if (! in_array($autorizacaoServico->status, [
            AsStatus::CRIADA,
            AsStatus::EM_ORCAMENTO,
        ], true)) {
            throw new DomainException('A AS só pode ser enviada depois de criada.');
        }

        $this->validarDadosDaAs($autorizacaoServico, $permitirValoresZerados);

        if ([...$destinatarios, ...$copias, ...$copiasOcultas] === []) {
            throw new DomainException('Informe ao menos um e-mail válido para enviar a AS.');
        }
    }

    protected function validarGeracao(AutorizacaoServico $autorizacaoServico, bool $permitirValoresZerados = false): void
    {
        if ($autorizacaoServico->status === AsStatus::CANCELADA) {
            throw new DomainException('Não é possível gerar PDF de uma AS cancelada.');
        }

        if (blank($autorizacaoServico->numero_as)) {
            throw new DomainException('Informe o número da AS antes de gerar o PDF.');
        }

        if (! $autorizacaoServico->obra || blank($autorizacaoServico->obra->unidade)) {
            throw new DomainException('Informe a unidade antes de gerar a AS.');
        }

        if (! $autorizacaoServico->construtora) {
            throw new DomainException('Informe o fornecedor antes de gerar a AS.');
        }

        if (! $permitirValoresZerados && (float) $autorizacaoServico->valor <= 0) {
            throw new DomainException('Informe o valor total antes de gerar a AS.');
        }

        if (! $autorizacaoServico->asEscopo && $autorizacaoServico->itens->isEmpty()) {
            throw new DomainException('Informe os itens ou a descrição da contratação antes de gerar a AS.');
        }
    }

    protected function parseNumeroBr(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(['.', ','], ['', '.'], (string) $valor);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    protected function validarDadosDaAs(AutorizacaoServico $autorizacaoServico, bool $permitirValoresZerados = false): void
    {
        if (blank($autorizacaoServico->numero_as)) {
            throw new DomainException('Informe o número da AS antes de enviar.');
        }

        if (! $autorizacaoServico->obra || blank($autorizacaoServico->obra->unidade)) {
            throw new DomainException('Informe a unidade antes de gerar a AS.');
        }

        if (! $autorizacaoServico->construtora) {
            throw new DomainException('Informe o fornecedor antes de gerar a AS.');
        }

        if (! $permitirValoresZerados && (float) $autorizacaoServico->valor <= 0) {
            throw new DomainException('Informe o valor total antes de enviar a AS.');
        }

        if (! $autorizacaoServico->asEscopo && $autorizacaoServico->itens->isEmpty()) {
            throw new DomainException('Informe os itens ou a descrição da contratação antes de gerar a AS.');
        }

        foreach ($autorizacaoServico->itens as $item) {
            if (blank($item->empresa) && ! $autorizacaoServico->construtora) {
                throw new DomainException('Defina a Empresa na linha antes de liberar para fornecedor.');
            }
        }
    }

    protected function preencherFornecedorPelaLinha(AutorizacaoServico $autorizacaoServico): void
    {
        if ($autorizacaoServico->construtora) {
            return;
        }

        $item = $autorizacaoServico->itens
            ->first(fn (ControleNotaFiscalItem $item): bool => filled($item->empresa));
        $empresaNome = trim((string) $item?->empresa);

        if ($empresaNome === '') {
            return;
        }

        $construtora = Construtora::query()
            ->where('nome', $empresaNome)
            ->first();

        if (! $construtora) {
            return;
        }

        $autorizacaoServico->forceFill([
            'construtora_id' => $construtora->id,
        ])->save();
        $autorizacaoServico->setRelation('construtora', $construtora);
    }

    /**
     * @return array<int, string>
     */
    public function destinatariosFornecedor(AutorizacaoServico $autorizacaoServico): array
    {
        $autorizacaoServico->loadMissing('construtora.users');
        $construtora = $autorizacaoServico->construtora;

        if (! $construtora instanceof Construtora) {
            return [];
        }

        $emailsContatos = $construtora->users
            ->pluck('email')
            ->all();

        return $this->normalizarEmails($emailsContatos);
    }

    /**
     * @return array<int, string>
     */
    public function emailsGestorProjeto(AutorizacaoServico $autorizacaoServico): array
    {
        $autorizacaoServico->loadMissing([
            'obra.projeto.responsavelEng',
            'itens.controleNotaFiscal.obra.projeto.responsavelEng',
        ]);

        return $this->normalizarEmails([
            $autorizacaoServico->obra?->projeto?->responsavelEng?->email
                ?? $autorizacaoServico->itens->first()?->controleNotaFiscal?->obra?->projeto?->responsavelEng?->email,
        ]);
    }

    /**
     * @param  array<int, mixed>  $emails
     * @return array<int, string>
     */
    public function normalizarEmails(array $emails): array
    {
        return collect($emails)
            ->flatMap(fn (mixed $email): array => preg_split('/[;,\s]+/', (string) $email) ?: [])
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    protected function mensagemBloqueioFiscal(?string $motivo): string
    {
        return match ($motivo) {
            'controle_nao_encontrado' => 'Controle fiscal de expansão não encontrado para a unidade.',
            'controle_encerrado' => 'Controle fiscal encerrado para a unidade.',
            'destino_nao_encontrado' => 'Item fiscal da AS não encontrado no controle da unidade.',
            'fornecedor_divergente' => 'Fornecedor da AS diverge do item fiscal.',
            'item_nao_liberado' => 'Item fiscal ainda não liberado para fornecedor.',
            default => 'AS bloqueada pelo controle fiscal.',
        };
    }
}
