<?php

namespace App\Filament\Resources\Obras\Tables;

use App\Models\ColunaPersonalizada;
use App\Models\Obras;
use App\Models\Projeto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ObrasColumnFilters
{
    /**
     * Memo por processo. Evita custo extra do CACHE_STORE=database a cada render
     * (20+ leituras redundantes pelo closure ->options() de cada filtro).
     */
    protected static ?array $memoSelectFilters = null;

    protected static ?Collection $memoPontosAtencaoDefs = null;

    protected const SELECT_LABEL_MAPS = [
        'relatorio_fotografico' => [
            'enviado' => 'Enviado',
            'pendencias' => 'Enviado com Pendências',
            'nao_enviado' => 'Não Enviado',
        ],
        'termo_de_posse' => [
            'sim' => 'Sim',
            'nao' => 'Não',
        ],
        'cronograma_implantacao' => [
            'enviado' => 'Enviado',
            'nao_enviado' => 'Não Enviado',
        ],
        'cronograma_visi' => [
            'enviado' => 'Enviado',
            'nao_enviado' => 'Não Enviado',
        ],
        'camera_unidade' => [
            'sim' => 'Sim',
            'nao' => 'Não',
        ],
        'email_solicitacao_cl' => [
            'enviado' => 'Enviado',
            'nao_enviado' => 'Não Enviado',
        ],
        'envio_qrcod' => [
            'enviado' => 'Enviado',
            'nao_enviado' => 'Não Enviado',
        ],
        'checklist_manutencao' => [
            'concluido' => 'Concluído',
            'em_andamento' => 'Em andamento',
            'em_atraso' => 'Em atraso',
            'nao_iniciado' => 'Não iniciado',
        ],
        'homologados_em_atraso' => [
            'sim' => 'Sim',
            'nao' => 'Não',
            'na' => 'N/A',
            'n/a' => 'N/A',
        ],
    ];

    public static function getSelectFilters(): array
    {
        if (static::$memoSelectFilters !== null) {
            return static::$memoSelectFilters;
        }

        return static::$memoSelectFilters = cache()->remember('obras_select_filters', 3600, function () {
            return [
                'status' => static::distinctValues('status'),
                'marca' => static::distinctValuesFromProjeto('marca'),
                'pipe_land' => static::distinctValues('pipe_land'),
                'status_visita' => static::distinctValues('status_visita'),
                'status_proj_exec' => static::distinctValues('status_proj_exec'),
                'relatorio_fotografico' => static::distinctValues('relatorio_fotografico'),
                'termo_de_posse' => static::distinctValues('termo_de_posse'),
                'cronograma_implantacao' => static::distinctValues('cronograma_implantacao'),
                'mes' => [
                    '1' => 'Janeiro',
                    '2' => 'Fevereiro',
                    '3' => 'Março',
                    '4' => 'Abril',
                    '5' => 'Maio',
                    '6' => 'Junho',
                    '7' => 'Julho',
                    '8' => 'Agosto',
                    '9' => 'Setembro',
                    '10' => 'Outubro',
                    '11' => 'Novembro',
                    '12' => 'Dezembro',
                ],
                'ano' => Obras::query()
                    ->whereNotNull('ano')
                    ->distinct()
                    ->orderByDesc('ano')
                    ->pluck('ano', 'ano')
                    ->toArray(),
                'tipo_imovel' => static::distinctValuesFromProjeto('tipo_imovel'),
                'uf' => static::distinctValues('uf'),
                'locacao' => static::distinctValuesFromProjeto('locacao'),
                'cronograma_visi' => static::distinctValues('cronograma_visi'),
                'camera_unidade' => static::distinctValues('camera_unidade'),
                'homologados_em_atraso' => static::distinctValues('homologados_em_atraso'),
                'energia' => static::distinctValues('energia'),
                'agua' => static::distinctValues('agua'),
                'gas' => static::distinctValues('gas'),
                'email_solicitacao_cl' => static::distinctValues('email_solicitacao_cl'),
                'envio_qrcod' => static::distinctValues('envio_qrcod'),
                'checklist_manutencao' => static::distinctValues('checklist_manutencao'),
                ...static::getPontosAtencaoSelectFilters(),
            ];
        });
    }

    public static function getDateRangeColumns(): array
    {
        return array_merge([
            'entrada_ponto',
            'data_assinatura_contrato',
            'status_data_posse',
            'data_envio_relatorio_fotografico',
            'data_atualizacao_comentario',
            'inicio',
            'inicio_real',
            'fim',
            'inicio_imp',
            'fim_imp',
            'previsao_ligacao_energia',
            'data_check_list',
            'inicio_prev_pendencias',
            'termino_prev_pendencias',
            'created_at',
        ], static::getPontosAtencaoDateRangeColumns());
    }

    protected static function getPontosAtencaoSelectFilters(): array
    {
        $filters = [];

        foreach (static::getPontosAtencaoDefinitions() as $nome => $definicao) {
            if ((string) ($definicao->tipo ?? 'texto') !== 'select') {
                continue;
            }

            $key = 'ponto_atencao_' . Str::slug((string) $nome, '_');

            $filters[$key] = collect($definicao->opcoes ?? [])
                ->map(fn($item) => trim((string) $item))
                ->filter(fn($item) => $item !== '')
                ->values()
                ->mapWithKeys(fn($item) => [$item => $item])
                ->all();
        }

        return $filters;
    }

    protected static function getPontosAtencaoDateRangeColumns(): array
    {
        return static::getPontosAtencaoDefinitions()
            ->filter(fn($definicao) => (string) ($definicao->tipo ?? 'texto') === 'data')
            ->keys()
            ->map(fn($nome) => 'ponto_atencao_' . Str::slug((string) $nome, '_'))
            ->values()
            ->all();
    }

    protected static function getPontosAtencaoDefinitions(): Collection
    {
        if (static::$memoPontosAtencaoDefs !== null) {
            return static::$memoPontosAtencaoDefs;
        }

        return static::$memoPontosAtencaoDefs = Cache::remember('obras_pontos_atencao_definitions', 600, function (): Collection {
            return ColunaPersonalizada::query()
                ->select('nome', 'tipo', 'opcoes')
                ->whereNotNull('nome')
                ->orderBy('nome')
                ->get()
                ->groupBy('nome')
                ->map(fn ($items) => $items->first());
        });
    }

    public static function getFilterType(string $columnName): ?string
    {
        if (array_key_exists($columnName, static::getSelectFilters())) {
            return 'select';
        }

        if (in_array($columnName, static::getDateRangeColumns())) {
            return 'date_range';
        }

        if (str_starts_with($columnName, 'ponto_atencao_')) {
            return 'text';
        }

        return null;
    }

    public static function getSelectOptions(string $columnName): array
    {
        $filters = static::getSelectFilters();

        return $filters[$columnName] ?? [];
    }

    protected static function distinctValues(string $column): array
    {
        return Obras::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->mapWithKeys(fn($value) => [$value => static::resolveOptionLabel($column, $value)])
            ->toArray();
    }

    protected static function distinctValuesFromProjeto(string $column): array
    {
        return Projeto::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->mapWithKeys(fn($value) => [$value => static::resolveOptionLabel($column, $value)])
            ->toArray();
    }

    protected static function resolveOptionLabel(string $column, mixed $value): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return '';
        }

        $normalized = Str::lower($raw);
        $map = self::SELECT_LABEL_MAPS[$column] ?? null;

        if (is_array($map) && array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return $raw;
    }
}
