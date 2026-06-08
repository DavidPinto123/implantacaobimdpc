<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Schemas;

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Filament\Components\Forms\MoneyInput;
use App\Forms\Components\CnpjInput;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\Banco;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\User;
use App\Support\Cnpj;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ImportacaoNotaFiscalForm
{
    private const CNPJ_DESTINATARIO_CONFERENCIA_MESSAGE = 'O CNPJ do destinatário/remetente informado não corresponde a essa unidade, só serão aceitas as notas conforme o CNPJ definitivo da unidade %s.';

    private const CNPJ_NAO_CADASTRADO_MESSAGE = 'Esta unidade não possui CNPJ cadastrado. Solicite ao gestor o cadastro do CNPJ para prosseguir com a importação da nota fiscal.';

    protected static function normalizeCnpj(?string $value): string
    {
        return Cnpj::normalize($value);
    }

    protected static function formatCnpj(?string $value): string
    {
        return Cnpj::format($value) ?? '';
    }

    protected static function sanitizeNumeroNotaFiscal(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return ltrim($digits, '0');
    }

    protected static function isTransferenciaPagamento(mixed $value): bool
    {
        return in_array($value, ['transferencia', 'dados_bancarios'], true);
    }

    /**
     * @return array<string, string>
     */
    protected static function getBancoOptions(): array
    {
        return Banco::query()
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->orderBy('codigo')
            ->get(['codigo', 'nome_reduzido'])
            ->mapWithKeys(fn (Banco $banco): array => [
                (string) $banco->codigo => trim($banco->codigo.' - '.$banco->nome_reduzido),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function getNumeroNotaFiscalInputAttributes(): array
    {
        return [
            'x-on:input' => <<<'JS'
                const sanitized = $el.value.replace(/\D/g, '').replace(/^0+/, '');
                if ($el.value !== sanitized) {
                    $el.value = sanitized;
                    $el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            JS,
        ];
    }

    protected static function normalizedCnpjSqlExpression(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(UPPER({$column}), '.', ''), '/', ''), '-', ''), ' ', '')";
    }

    protected static function applyProjetoCnpjMatchConstraint(Builder $query, string $normalizedCnpj): Builder
    {
        return $query
            ->whereRaw(static::normalizedCnpjSqlExpression('cnpj').' = ?', [$normalizedCnpj])
            ->orWhereRaw(static::normalizedCnpjSqlExpression('cnpj_provisorio').' = ?', [$normalizedCnpj]);
    }

    protected static function projetoCnpjMatchOrderBySql(): string
    {
        return sprintf(
            <<<'SQL'
                CASE
                    WHEN %s = ? THEN 0
                    WHEN %s = ? THEN 1
                    WHEN status_cnpj = 'definitivo' THEN 2
                    WHEN status_cnpj = 'provisorio' THEN 3
                    ELSE 4
                END
            SQL,
            static::normalizedCnpjSqlExpression('cnpj'),
            static::normalizedCnpjSqlExpression('cnpj_provisorio'),
        );
    }

    /**
     * @return array{definitivo: string, provisorio: string}
     */
    protected static function getProjetoCnpjs(?Projeto $projeto): array
    {
        if (! $projeto instanceof Projeto) {
            return [
                'definitivo' => '',
                'provisorio' => '',
            ];
        }

        return [
            'definitivo' => static::normalizeCnpj($projeto->cnpj),
            'provisorio' => static::normalizeCnpj($projeto->cnpj_provisorio),
        ];
    }

    protected static function hasProjetoCnpjCadastrado(?Projeto $projeto): bool
    {
        $cnpjs = static::getProjetoCnpjs($projeto);

        return $cnpjs['definitivo'] !== '' || $cnpjs['provisorio'] !== '';
    }

    protected static function getProjetoDaUnidade(mixed $obraId): ?Projeto
    {
        if (! filled($obraId)) {
            return null;
        }

        return Obras::query()->with('projeto')->find($obraId)?->projeto;
    }

    protected static function getProjetoDaUnidadeViaControle(mixed $controleNotaFiscalId): ?Projeto
    {
        if (! filled($controleNotaFiscalId)) {
            return null;
        }

        return ControleNotaFiscal::query()
            ->with('obra.projeto')
            ->find($controleNotaFiscalId)?->obra?->projeto;
    }

    protected static function resolveProjetoDaUnidade(Get $get): ?Projeto
    {
        $projetoDaObra = static::getProjetoDaUnidade($get('obra_id_lookup'));

        if ($projetoDaObra instanceof Projeto) {
            return $projetoDaObra;
        }

        return static::getProjetoDaUnidadeViaControle($get('controle_nota_fiscal_id'));
    }

    protected static function cnpjPertenceAoProjeto(?Projeto $projeto, ?string $cnpj): bool
    {
        $cnpjDigitado = static::normalizeCnpj($cnpj);
        $cnpjsDoProjeto = static::getProjetoCnpjs($projeto);

        return $cnpjDigitado !== ''
            && in_array($cnpjDigitado, $cnpjsDoProjeto, true);
    }

    protected static function getProjetoCnpjEmUso(Projeto $projeto): string
    {
        $cnpjDefinitivo = static::normalizeCnpj($projeto->cnpj);
        $cnpjProvisorio = static::normalizeCnpj($projeto->cnpj_provisorio);

        return match ($projeto->status_cnpj) {
            'provisorio' => $cnpjProvisorio !== '' ? $cnpjProvisorio : $cnpjDefinitivo,
            'definitivo' => $cnpjDefinitivo !== '' ? $cnpjDefinitivo : $cnpjProvisorio,
            default => $cnpjDefinitivo !== '' ? $cnpjDefinitivo : $cnpjProvisorio,
        };
    }

    protected static function getCnpjDestinatarioConferenciaMessage(?Projeto $projetoDaUnidade): string
    {
        $cnpjDaUnidade = static::formatCnpj(
            $projetoDaUnidade instanceof Projeto
                ? static::getProjetoCnpjEmUso($projetoDaUnidade)
                : '',
        );

        return sprintf(self::CNPJ_DESTINATARIO_CONFERENCIA_MESSAGE, $cnpjDaUnidade);
    }

    protected static function getCnpjNaoCadastradoMessage(): string
    {
        return self::CNPJ_NAO_CADASTRADO_MESSAGE;
    }

    protected static function findMatchingProjetoByCnpj(?string $cnpj): ?Projeto
    {
        $normalizedCnpj = static::normalizeCnpj($cnpj);

        if ($normalizedCnpj === '') {
            return null;
        }

        return Projeto::query()
            ->where(function (Builder $query) use ($normalizedCnpj): void {
                static::applyProjetoCnpjMatchConstraint($query, $normalizedCnpj);
            })
            ->orderByRaw(
                static::projetoCnpjMatchOrderBySql(),
                [$normalizedCnpj, $normalizedCnpj],
            )
            ->orderBy('nome')
            ->first();
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getObraOptions(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            $construtora = Construtora::query()->find($user->construtoras_id);

            if (! $construtora instanceof Construtora) {
                return [];
            }

            $obraIdsFromItens = ControleNotaFiscalItem::query()
                ->select('controle_nota_fiscals.obra_id')
                ->join('controle_nota_fiscals', 'controle_nota_fiscals.id', '=', 'controle_nota_fiscal_items.controle_nota_fiscal_id')
                ->where('controle_nota_fiscal_items.empresa', $construtora->nome)
                ->where('controle_nota_fiscals.tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->where('controle_nota_fiscals.status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)
                ->whereNotNull('controle_nota_fiscal_items.liberado_para_fornecedor_at')
                ->whereExists(function ($subQuery): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('autorizacao_servicos')
                        ->whereColumn('autorizacao_servicos.controle_nota_fiscal_item_id', 'controle_nota_fiscal_items.id')
                        ->where('autorizacao_servicos.status', AsStatus::ENVIADA->value);
                })
                ->pluck('controle_nota_fiscals.obra_id')
                ->filter()
                ->unique()
                ->values();

            $obraIdsFromAuxiliares = ControleNotaFiscalAuxiliar::query()
                ->select('controle_nota_fiscals.obra_id')
                ->join('controle_nota_fiscals', 'controle_nota_fiscals.id', '=', 'controle_nota_fiscal_auxiliares.controle_nota_fiscal_id')
                ->where('controle_nota_fiscal_auxiliares.empresa', $construtora->nome)
                ->where('controle_nota_fiscals.tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->where('controle_nota_fiscals.status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)
                ->whereNotNull('controle_nota_fiscal_auxiliares.liberado_para_fornecedor_at')
                ->whereExists(function ($subQuery): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('autorizacao_servico_adicionais')
                        ->whereColumn('autorizacao_servico_adicionais.controle_nota_fiscal_auxiliar_id', 'controle_nota_fiscal_auxiliares.id')
                        ->whereIn('autorizacao_servico_adicionais.status', ['aprovado', 'Aprovado']);
                })
                ->pluck('controle_nota_fiscals.obra_id')
                ->filter()
                ->unique()
                ->values();

            $obraIds = $obraIdsFromItens
                ->merge($obraIdsFromAuxiliares)
                ->unique()
                ->values();

            if ($obraIds->isEmpty()) {
                return [];
            }

            return Obras::query()
                ->whereIn('id', $obraIds)
                ->orderBy('codigo')
                ->orderBy('unidade')
                ->get(['id', 'codigo', 'unidade'])
                ->mapWithKeys(function (Obras $obra): array {
                    $codigo = trim((string) ($obra->codigo ?? ''));
                    $unidade = trim((string) ($obra->unidade ?? ''));
                    $label = trim(($codigo !== '' ? ($codigo.' - ') : '').$unidade);

                    return [
                        $obra->id => ($label !== '' ? $label : ('Obra #'.$obra->id)),
                    ];
                })
                ->all();
        }

        $obraIdsFromAutorizacoes = AutorizacaoServico::query()
            ->whereNotNull('obra_id')
            ->where('status', AsStatus::ENVIADA->value)
            ->whereHas('controleNotaFiscalItem.controleNotaFiscal', fn (Builder $query): Builder => $query
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO))
            ->when(
                $user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id),
                fn ($query) => $query->where('construtora_id', $user->construtoras_id),
            )
            ->pluck('obra_id');

        $obraIdsFromAsas = Asa::query()
            ->whereHas('controleNotaFiscalAuxiliar.controleNotaFiscal', fn (Builder $query): Builder => $query
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO))
            ->when(
                $user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id),
                function (Builder $query) use ($user): Builder {
                    $construtora = Construtora::query()->find($user->construtoras_id);

                    return $query->whereHas('controleNotaFiscalAuxiliar', fn (Builder $auxiliarQuery): Builder => $auxiliarQuery
                        ->where('empresa', $construtora?->nome));
                },
            )
            ->with('controleNotaFiscalAuxiliar.controleNotaFiscal:id,obra_id')
            ->get()
            ->pluck('controleNotaFiscalAuxiliar.controleNotaFiscal.obra_id');

        $obraIds = $obraIdsFromAutorizacoes
            ->merge($obraIdsFromAsas)
            ->filter()
            ->unique()
            ->values();

        if ($obraIds->isEmpty()) {
            return [];
        }

        return Obras::query()
            ->whereIn('id', $obraIds)
            ->orderBy('codigo')
            ->orderBy('unidade')
            ->get(['id', 'codigo', 'unidade'])
            ->mapWithKeys(function (Obras $obra): array {
                $codigo = trim((string) ($obra->codigo ?? ''));
                $unidade = trim((string) ($obra->unidade ?? ''));
                $label = trim(($codigo !== '' ? ($codigo.' - ') : '').$unidade);

                return [
                    $obra->id => ($label !== '' ? $label : ('Obra #'.$obra->id)),
                ];
            })
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getAsaOptionsForObra(mixed $obraId): array
    {
        if (! filled($obraId)) {
            return [];
        }

        $user = Auth::user();

        // Para Construtora, o "Número da AS/ASA" é o número estruturado (autorizacao_servicos.numero_as)
        // gerado automaticamente na criação da Autorização de Serviço.
        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            $construtora = Construtora::query()->find($user->construtoras_id);

            if (! $construtora instanceof Construtora) {
                return [];
            }

            $autorizacoes = AutorizacaoServico::query()
                ->with(['asEscopo', 'controleNotaFiscalItem'])
                ->where('obra_id', $obraId)
                ->where('construtora_id', $user->construtoras_id)
                ->where('status', AsStatus::ENVIADA->value)
                ->whereHas('controleNotaFiscalItem', fn (Builder $query): Builder => $query
                    ->where('empresa', $construtora->nome)
                    ->whereNotNull('liberado_para_fornecedor_at')
                    ->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery
                        ->where('obra_id', $obraId)
                        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                        ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)))
                ->orderByDesc('id')
                ->get()
                ->mapWithKeys(fn (AutorizacaoServico $as): array => [
                    $as->id => static::buildAutorizacaoServicoOptionLabel($as, (int) $obraId, $construtora),
                ])
                ->all();

            $asas = Asa::query()
                ->with('controleNotaFiscalAuxiliar')
                ->whereHas('controleNotaFiscalAuxiliar', fn (Builder $query): Builder => $query
                    ->where('empresa', $construtora->nome)
                    ->whereNotNull('liberado_para_fornecedor_at')
                    ->whereHas('controleNotaFiscal', fn (Builder $controleQuery): Builder => $controleQuery
                        ->where('obra_id', $obraId)
                        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                        ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO)))
                ->orderByDesc('id')
                ->get()
                ->mapWithKeys(fn (Asa $asa): array => [
                    'asa:'.$asa->id => static::buildAsaOptionLabel($asa),
                ])
                ->all();

            return $autorizacoes + $asas;
        }

        // Para admin (não-Construtora), primeiro tentar buscar AutorizacaoServico criadas via CMED
        $autorizacoes = AutorizacaoServico::query()
            ->with('asEscopo')
            ->where('obra_id', $obraId)
            ->where('status', AsStatus::ENVIADA->value)
            ->where(function (Builder $query): void {
                $query
                    ->where('numero_as', 'like', '%-SF-EXP-%')
                    ->orWhere('numero_as', 'like', '%-SF-RET-%');
            })
            ->when(
                $user instanceof User && filled($user->construtoras_id),
                fn ($query) => $query->where('construtora_id', $user->construtoras_id),
            )
            ->orderByDesc('id')
            ->get();

        if ($autorizacoes->isNotEmpty()) {
            $construtora = $user instanceof User && filled($user->construtoras_id)
                ? Construtora::query()->find($user->construtoras_id)
                : null;

            return $autorizacoes
                ->mapWithKeys(fn (AutorizacaoServico $as): array => [
                    $as->id => static::buildAutorizacaoServicoOptionLabel($as, (int) $obraId, $construtora),
                ])
                ->all();
        }

        return Asa::query()
            ->whereHas('controleNotaFiscalAuxiliar.controleNotaFiscal', fn (Builder $query): Builder => $query
                ->where('obra_id', $obraId)
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                ->where('status', '!=', ControleNotaFiscal::STATUS_ENCERRADO))
            ->when(
                $user instanceof User && filled($user->construtoras_id),
                fn ($query) => $query->whereHas('controleNotaFiscalAuxiliar', function ($auxiliarQuery) use ($user): void {
                    $construtora = Construtora::query()->find($user->construtoras_id);

                    $auxiliarQuery->where('empresa', $construtora?->nome);
                })
            )
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (Asa $asa): array => [
                'asa:'.$asa->id => static::buildAsaOptionLabel($asa),
            ])
            ->all();
    }

    protected static function buildAutorizacaoServicoOptionLabel(AutorizacaoServico $autorizacao, int $obraId, ?Construtora $construtora = null): string
    {
        $numero = trim((string) ($autorizacao->numero_as ?? ''));

        $metadata = static::resolveAutorizacaoServicoMetadata($autorizacao, $obraId, $construtora);
        $grupo = trim((string) ($metadata['grupo'] ?? $autorizacao->asEscopo?->grupo ?? ''));
        $escopo = trim((string) ($metadata['escopo'] ?? $autorizacao->asEscopo?->escopo ?? ''));
        $ref = trim((string) ($autorizacao->asEscopo?->numero_as ?? ''));

        $suffix = trim(collect([
            ! str_contains($numero, '-SF-') && $ref !== '' && $ref !== $numero ? $ref : null,
            ! str_contains($numero, '-SF-') && $grupo !== '' ? $grupo : null,
            ! str_contains($numero, '-SF-') && $escopo !== '' ? $escopo : null,
        ])->filter()->implode(' - '));

        $headline = trim($numero.($suffix !== '' ? (' - '.$suffix) : ''));
        $badges = collect();

        if (($metadata['is_adicional'] ?? false) === true) {
            $badges->push('Adicional');
        }

        if (($metadata['is_complementar'] ?? false) === true) {
            $badges->push('Complemento: '.trim((string) ($metadata['complemento_label'] ?? '')));
        }

        if ($badges->isEmpty()) {
            return $headline !== '' ? $headline : ('AS #'.$autorizacao->id);
        }

        return sprintf(
            '<div><div>%s</div><div style="font-size:0.75rem;color:#6b7280;">%s</div></div>',
            e($headline !== '' ? $headline : ('AS #'.$autorizacao->id)),
            e($badges->implode(' - ')),
        );
    }

    protected static function buildAsaOptionLabel(Asa $asa): string
    {
        $headline = trim((string) ($asa->numero_asa ?: 'ASA #'.$asa->id));
        $badges = collect();

        if ($asa->controle_nota_fiscal_destino === 'adicional') {
            $badges->push('Adicional');
        }

        if ($badges->isEmpty()) {
            return $headline;
        }

        return sprintf(
            '<div><div>%s</div><div style="font-size:0.75rem;color:#6b7280;">%s</div></div>',
            e($headline),
            e($badges->implode(' - ')),
        );
    }

    /**
     * @return array{grupo:string,escopo:string,is_adicional:bool,is_complementar:bool,complemento_label:string}
     */
    protected static function resolveAutorizacaoServicoMetadata(AutorizacaoServico $autorizacao, int $obraId, ?Construtora $construtora = null): array
    {
        $empresa = trim((string) ($construtora?->nome ?? ''));
        $complementoAutorizacao = static::normalizarComplementoAutorizacaoServico($autorizacao);
        $itemQuery = ControleNotaFiscalItem::query()
            ->select('controle_nota_fiscal_items.*')
            ->join('controle_nota_fiscals', 'controle_nota_fiscals.id', '=', 'controle_nota_fiscal_items.controle_nota_fiscal_id')
            ->where('controle_nota_fiscals.obra_id', $obraId)
            ->where('controle_nota_fiscals.tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->where('controle_nota_fiscal_items.as_escopo_id', $autorizacao->as_escopo_id)
            ->whereNotNull('controle_nota_fiscal_items.liberado_para_fornecedor_at')
            ->when($empresa !== '', fn (Builder $query): Builder => $query->where('controle_nota_fiscal_items.empresa', $empresa))
            ->orderByDesc('controle_nota_fiscal_items.id')
            ->orderByDesc('controle_nota_fiscals.id');

        if (filled($complementoAutorizacao)) {
            $item = (clone $itemQuery)
                ->where('controle_nota_fiscal_items.numero_complemento', $complementoAutorizacao)
                ->first();
        } else {
            $item = (clone $itemQuery)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('controle_nota_fiscal_items.numero_complemento')
                        ->orWhere('controle_nota_fiscal_items.numero_complemento', '');
                })
                ->first();
        }

        $item ??= (clone $itemQuery)->first();

        if ($item instanceof ControleNotaFiscalItem) {
            $complementoLabel = $complementoAutorizacao !== ''
                ? $complementoAutorizacao
                : trim((string) ($item->numero_complemento ?? ''));

            return [
                'grupo' => trim((string) ($item->grupo ?? $autorizacao->asEscopo?->grupo ?? '')),
                'escopo' => trim((string) ($item->escopo ?? $autorizacao->asEscopo?->escopo ?? '')),
                'is_adicional' => false,
                'is_complementar' => $complementoLabel !== '',
                'complemento_label' => $complementoLabel,
            ];
        }

        $auxiliar = ControleNotaFiscalAuxiliar::query()
            ->select('controle_nota_fiscal_auxiliares.*')
            ->join('controle_nota_fiscals', 'controle_nota_fiscals.id', '=', 'controle_nota_fiscal_auxiliares.controle_nota_fiscal_id')
            ->where('controle_nota_fiscals.obra_id', $obraId)
            ->where('controle_nota_fiscals.tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->where('controle_nota_fiscal_auxiliares.numero_as', $autorizacao->numero_as)
            ->whereNotNull('controle_nota_fiscal_auxiliares.liberado_para_fornecedor_at')
            ->when($empresa !== '', fn (Builder $query): Builder => $query->where('controle_nota_fiscal_auxiliares.empresa', $empresa))
            ->orderByDesc('controle_nota_fiscal_auxiliares.id')
            ->first();

        $isComplementoAdicional = ($auxiliar instanceof ControleNotaFiscalAuxiliar)
            && (filled($complementoAutorizacao)
                || ControleNotaFiscalAuxiliar::query()
                    ->select('controle_nota_fiscal_auxiliares.id')
                    ->join('controle_nota_fiscals', 'controle_nota_fiscals.id', '=', 'controle_nota_fiscal_auxiliares.controle_nota_fiscal_id')
                    ->where('controle_nota_fiscals.obra_id', $obraId)
                    ->where('controle_nota_fiscals.tipo_unidade', TipoUnidade::EXPANSAO->value)
                    ->where('controle_nota_fiscal_auxiliares.numero_as', $autorizacao->numero_as)
                    ->whereNotNull('controle_nota_fiscal_auxiliares.liberado_para_fornecedor_at')
                    ->when($empresa !== '', fn (Builder $query): Builder => $query->where('controle_nota_fiscal_auxiliares.empresa', $empresa))
                    ->count() > 1);

        return [
            'grupo' => trim((string) ($auxiliar?->grupo ?? $autorizacao->asEscopo?->grupo ?? '')),
            'escopo' => trim((string) ($auxiliar?->escopo ?? $autorizacao->asEscopo?->escopo ?? '')),
            'is_adicional' => $auxiliar instanceof ControleNotaFiscalAuxiliar,
            'is_complementar' => $isComplementoAdicional,
            'complemento_label' => $complementoAutorizacao,
        ];
    }

    protected static function normalizarComplementoAutorizacaoServico(AutorizacaoServico $autorizacao): string
    {
        $complemento = trim((string) ($autorizacao->numero_complemento ?? ''));

        if ($complemento !== '') {
            return $complemento;
        }

        $numeroAs = trim((string) ($autorizacao->numero_as ?? ''));

        if (preg_match('/(?:^|[-\/])(C\d+)$/i', $numeroAs, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vinculação no controle')
                    ->schema([
                        TextInput::make('tipo_nota_fiscal_destino')
                            ->label('Tipo de nota fiscal')
                            ->hidden()
                            ->default('adicional')
                            ->dehydrated(false)
                            ->readOnly(),

                        Select::make('controle_nota_fiscal_id')
                            ->label('Controle de medição')
                            ->options(fn (): array => ControleNotaFiscal::query()
                                ->with(['obra'])
                                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (ControleNotaFiscal $controle): array => [
                                    $controle->id => trim(collect([
                                        $controle->obra?->unidade,
                                        optional($controle->data_base)->format('d/m/Y'),
                                    ])->filter()->implode(' • ')),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('linha_principal_id', null);
                                $set('linha_auxiliar_id', null);
                            })
                            ->required(fn (string $operation): bool => $operation !== 'create')
                            ->hidden(fn (string $operation): bool => $operation === 'create'),

                        Select::make('obra_id_lookup')
                            ->label('Obra')
                            ->options(fn (): array => static::getObraOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('asa_id_lookup', null);
                            })
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->hidden(fn (string $operation): bool => $operation !== 'create')
                            ->native(false),

                        Select::make('asa_id_lookup')
                            ->label('Número da AS/ASA')
                            ->options(fn (Get $get): array => static::getAsaOptionsForObra($get('obra_id_lookup')))
                            ->allowHtml()
                            ->searchable()
                            ->preload()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->hidden(fn (string $operation): bool => $operation !== 'create')
                            ->disabled()
                            ->dehydrated()
                            ->native(false),

                        Select::make('linha_principal_id')
                            ->label('Grupo - AS - Escopo')
                            ->options(fn (Get $get): array => ControleNotaFiscalItem::query()
                                ->where('controle_nota_fiscal_id', $get('controle_nota_fiscal_id'))
                                ->whereNotNull('as_escopo_id')
                                ->orderBy('sort_order')
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (ControleNotaFiscalItem $item): array => [
                                    $item->id => trim(collect([
                                        $item->grupo ?? $item->asEscopo?->grupo,
                                        filled($item->numero_as) ? 'AS '.$item->numero_as : null,
                                        $item->escopo ?? $item->asEscopo?->escopo,
                                    ])->filter()->implode(' - ')),
                                ])
                                ->all())
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (Get $get): bool => $get('tipo_nota_fiscal_destino') !== 'adicional')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get, string $operation): bool => $operation !== 'create' && $get('tipo_nota_fiscal_destino') !== 'adicional')
                            ->helperText('Selecione o Grupo - AS - Escopo que receberá a nota fiscal.'),

                        Select::make('linha_auxiliar_id')
                            ->label('Grupo - AS - Escopo')
                            ->options(fn (Get $get): array => ControleNotaFiscalAuxiliar::query()
                                ->where('controle_nota_fiscal_id', $get('controle_nota_fiscal_id'))
                                ->whereRelation('controleNotaFiscal', 'tipo_unidade', TipoUnidade::EXPANSAO->value)
                                ->orderBy('sort_order')
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (ControleNotaFiscalAuxiliar $auxiliar): array => [
                                    $auxiliar->id => trim(collect([
                                        $auxiliar->grupo,
                                        filled($auxiliar->numero_as) ? 'AS '.$auxiliar->numero_as : null,
                                        $auxiliar->escopo,
                                    ])->filter()->implode(' - ')),
                                ])
                                ->all())
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (Get $get): bool => $get('tipo_nota_fiscal_destino') === 'adicional')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get, string $operation): bool => $operation !== 'create' && $get('tipo_nota_fiscal_destino') === 'adicional')
                            ->helperText('Selecione o Grupo - AS - Escopo que receberá a nota fiscal.'),

                        Select::make('tipo_medicao')
                            ->label('Tipo de medição')
                            ->options([
                                'mao_obra' => 'Mão de Obra',
                                'material' => 'Material',
                                'transporte' => 'Transporte',
                            ])
                            ->required()
                            ->live()
                            ->partiallyRenderComponentsAfterStateUpdated(['empresa'])
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state === 'mao_obra') {
                                    $set('empresa', Auth::user()?->name);

                                    return;
                                }

                                $set('empresa', null);
                            })
                            ->native(false),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Dados da nota fiscal')
                    ->schema([
                        TextInput::make('empresa')
                            ->label('Razão Social do Emissor da Nota')
                            ->required(),

                        CnpjInput::make('cnpj_fornecedor')
                            ->label('CNPJ do Emissor da Nota')
                            ->required()
                            ->live(onBlur: true)
                            ->autocomplete(false)
                            ->maxLength(18),

                        TextInput::make('numero_nf')
                            ->label('Número da nota fiscal')
                            ->required()
                            ->inputMode('numeric')
                            ->formatStateUsing(fn (mixed $state): string => static::sanitizeNumeroNotaFiscal((string) $state))
                            ->dehydrateStateUsing(fn (mixed $state): string => static::sanitizeNumeroNotaFiscal((string) $state))
                            ->extraInputAttributes(static::getNumeroNotaFiscalInputAttributes())
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    unset($attribute);

                                    if (preg_match('/\D/', (string) $value) === 1 || static::sanitizeNumeroNotaFiscal((string) $value) === '') {
                                        $fail('Informe apenas números, sem ponto, sem traço e sem zero à esquerda.');
                                    }
                                },
                            ])
                            ->helperText('Informe apenas números, sem ponto, sem traço e sem zero à esquerda.'),

                        CnpjInput::make('cnpj_faturamento')
                            ->label('CNPJ do Destinatário/Remetente')
                            ->required()
                            ->maxLength(18)
                            ->autocomplete(false)
                            ->validateCnpj(false)
                            ->live(onBlur: true)
                            ->skipRenderAfterStateUpdated()
                            ->validationAttribute('CNPJ do destinatário/remetente')
                            ->allowHtmlValidationMessages()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    unset($attribute);

                                    if (blank($value)) {
                                        return;
                                    }

                                    if (static::normalizeCnpj($value) === static::normalizeCnpj($get('cnpj_fornecedor'))) {
                                        $fail('O CNPJ do destinatário/remetente não pode ser igual ao CNPJ do emissor da nota.');

                                        return;
                                    }

                                    $obraId = $get('obra_id_lookup');
                                    $projetoDaUnidade = static::resolveProjetoDaUnidade($get);

                                    if (filled($obraId) && ! $projetoDaUnidade instanceof Projeto) {
                                        $fail(static::getCnpjNaoCadastradoMessage());

                                        return;
                                    }

                                    if ($projetoDaUnidade instanceof Projeto && ! static::hasProjetoCnpjCadastrado($projetoDaUnidade)) {
                                        $fail(static::getCnpjNaoCadastradoMessage());

                                        return;
                                    }

                                    if (! static::findMatchingProjetoByCnpj((string) $value) instanceof Projeto) {
                                        $fail(static::getCnpjDestinatarioConferenciaMessage($projetoDaUnidade));

                                        return;
                                    }

                                    if (! $projetoDaUnidade instanceof Projeto) {
                                        return;
                                    }

                                    if (static::cnpjPertenceAoProjeto($projetoDaUnidade, (string) $value)) {
                                        return;
                                    }

                                    $fail(static::getCnpjDestinatarioConferenciaMessage($projetoDaUnidade));
                                },
                            ]),

                        MoneyInput::makeNonNull('valor_acumulado_medido_nf', 'Valor da nota fiscal')
                            ->required(),

                        DatePicker::make('emissao')
                            ->label('Emissão')
                            ->required()
                            ->maxDate(fn (): string => today()->toDateString())
                            ->native(true),

                        Select::make('status')
                            ->label('Status')
                            ->options(ControleNotaFiscalNota::getStatusOptions())
                            ->native(false)
                            ->hiddenOn('create')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('O status é atualizado na etapa de revisão/aprovação.'),
                        FileUpload::make('arquivo_path')
                            ->label('Arquivo da nota fiscal')->disk((string) config('filesystems.media_disk', 'r2'))
                            ->visibility('public')
                            ->directory(fn (Get $get): string => 'controle-nota-fiscal/notas/'.($get('linha_principal_id') ?: $get('linha_auxiliar_id') ?: 'temp'))
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'application/xml', 'text/xml'])
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->maxSize(10240)
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Envie o PDF ou XML da nota fiscal. Tamanho máximo: 10MB.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Informações de Pagamento')
                    ->schema([
                        Radio::make('instrucoes_pagamento')
                            ->label('Instruções de pagamento')
                            ->options([
                                'pix' => 'PIX',
                                'transferencia' => 'Transferência',
                                'boleto_bancario' => 'Boleto Bancário',
                            ])
                            ->required()
                            ->live()
                            ->inline(),

                        Text::make(new HtmlString(
                            '<div class="rounded-md border border-red-300 bg-red-50 p-3 text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300">'
                            .'<strong>Avisos</strong><br>'
                            .'1. Notas de dedução devem vir no mesmo arquivo da NF;<br>'
                            .'2. Boletos devem vir separadamente;<br>'
                            .'3. Transferências exigem banco, agência e conta corrente;'
                            .'</div>'
                        )),
                        Select::make('banco_codigo')
                            ->label('Banco')
                            ->options(fn (): array => static::getBancoOptions())
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento')))
                            ->visible(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento'))),

                        TextInput::make('agencia')
                            ->label('Agência')
                            ->required(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento')))
                            ->visible(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento')))
                            ->maxLength(30),

                        TextInput::make('conta_corrente')
                            ->label('Conta Corrente')
                            ->required(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento')))
                            ->visible(fn (Get $get): bool => static::isTransferenciaPagamento($get('instrucoes_pagamento')))
                            ->maxLength(50),

                        DatePicker::make('data_vencimento_boleto')
                            ->label('Data de vencimento do boleto')
                            ->native(true)
                            ->minDate(fn (): string => today()->toDateString())
                            ->required(fn (Get $get): bool => $get('instrucoes_pagamento') === 'boleto_bancario')
                            ->validationAttribute('data de vencimento do boleto')
                            ->validationMessages([
                                'after_or_equal' => fn (): string => 'O campo data de vencimento do boleto deve ser uma data posterior ou igual a '.now()->addDays(30)->format('d/m/Y').'.',
                            ])
                            ->rule(
                                fn (): string => 'after_or_equal:'.now()->addDays(30)->toDateString(),
                                fn (Get $get): bool => $get('instrucoes_pagamento') === 'boleto_bancario',
                            )
                            ->visible(fn (Get $get): bool => $get('instrucoes_pagamento') === 'boleto_bancario'),

                        FileUpload::make('boleto_path')
                            ->label('Boleto Bancário')->disk((string) config('filesystems.media_disk', 'r2'))
                            ->visibility('public')
                            ->directory(fn (Get $get): string => 'controle-nota-fiscal/boletos/'.($get('linha_principal_id') ?: $get('linha_auxiliar_id') ?: 'temp'))
                            ->visible(fn (Get $get): bool => $get('instrucoes_pagamento') === 'boleto_bancario')
                            ->required(fn (Get $get): bool => $get('instrucoes_pagamento') === 'boleto_bancario')
                            ->acceptedFileTypes(['application/pdf', 'application/xml', 'text/xml'])
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->maxSize(10240)
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Envie o PDF do Boleto Bancário. Tamanho máximo: 10MB.')
                            ->columnSpanFull(),

                        Textarea::make('observacoes')
                            ->label('Observações')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
