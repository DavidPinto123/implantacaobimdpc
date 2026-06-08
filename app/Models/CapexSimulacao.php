<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CapexSimulacao extends Model
{
    use SoftDeletes;

    protected $table = 'capex_simulacoes';

    /*
    private const ESCOPOS_MANUAIS_PADRAO = [
        // Digite aqui os escopos manuais que devem ser importados ao criar a simulação.
        ['nome_escopo' => 'TI E SONORIZAÇÃO', 'valor_base_m2' => 200000],
        ['nome_escopo' => 'SERV. SEGURANÇA', 'valor_base_m2' => 0],
        ['nome_escopo' => 'PLATAFORMA PNE', 'valor_base_m2' => 55000],
        ['nome_escopo' => 'FORN. E INSTAL. – ELEVADOR', 'valor_base_m2' => 145000],
        ['nome_escopo' => 'ESTRUTURA METÁLICA (ELEVADOR/ PLATAFORMA)', 'valor_base_m2' => 70400],
        ['nome_escopo' => 'FORN. E INSTAL. – AR CONDICIONADO', 'valor_base_m2' => 120000],
        ['nome_escopo' => 'FORN. E INSTAL. – ENTRADA DE ENERGIA', 'valor_base_m2' => 225000],
        ['nome_escopo' => 'FORN. E INSTAL. – ACÚSTICA', 'valor_base_m2' => 0],
        ['nome_escopo' => 'LOCAÇÃO DE GERADOR', 'valor_base_m2' => 0],
        ['nome_escopo' => 'ADITIVO ADEQUAÇÃO SHELL', 'valor_base_m2' => 0],
    ];
    */

    protected $fillable = [
        'projeto_id',
        'nome',
        'sigla',
        'endereco',
        'uf',
        'area_unidade',
        'fator_correcao',
        'as_faixa_area_id',
        'faixa_nome',
        'custo_total_estimado',
        'custo_por_m2',
        'status',
        'comentario',
        'revisao',
    ];

    protected $casts = [
        'status' => 'integer',
        'revisao' => 'integer',
    ];

    public function getRevisaoLabelAttribute(): string
    {
        return 'REV'.str_pad((string) ($this->revisao ?? 0), 2, '0', STR_PAD_LEFT);
    }

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function faixaArea()
    {
        return $this->belongsTo(AsFaixaArea::class, 'as_faixa_area_id');
    }

    public function itens()
    {
        return $this->hasMany(CapexSimulacaoItem::class);
    }

    public function shellItem()
    {
        return $this->hasOne(CapexSimulacaoItem::class)
            ->where('nome_escopo', 'SHELL (OBRA CIVIL)');
    }

    public function getNomeExibicaoAttribute(): ?string
    {
        return $this->projeto?->nome ?: $this->nome;
    }

    public function getSiglaExibicaoAttribute(): ?string
    {
        return $this->projeto?->sigla ?: $this->sigla;
    }

    public function getEnderecoExibicaoAttribute(): ?string
    {
        return $this->projeto?->endereco ?: $this->endereco;
    }

    public function getUfExibicaoAttribute(): ?string
    {
        return $this->projeto?->estado?->uf ?: $this->uf;
    }

    public function recalcularItensAutomaticosETotais(): void
    {
        $this->load('itens');

        foreach ($this->itens as $item) {
            if ($item->tipo === 'auto') {
                $valorBase = (float) $item->valor_base_m2;

                if (! $item->valor_base_m2_editado) {
                    $valorBase = 0;

                    if ($item->escopo) {
                        $todasFaixasDoEscopo = $item->escopo->faixasArea;

                        // So recalcula pelo escopo/faixa quando o valor nao foi sobrescrito manualmente.
                        if ($todasFaixasDoEscopo->isNotEmpty()) {
                            $faixaDaSimulacao = $todasFaixasDoEscopo
                                ->firstWhere('id', $this->as_faixa_area_id);

                            $valorBase = (float) ($faixaDaSimulacao?->pivot?->valor_m2 ?? 0);
                        }
                    }
                }

                $custoEstimado = $item->incluir
                    ? ($valorBase * (float) $this->area_unidade * (float) $this->fator_correcao)
                    : 0;

                $item->update([
                    'valor_base_m2' => $valorBase,
                    'area' => $this->area_unidade,
                    'fator_correcao' => $this->fator_correcao,
                    'custo_estimado' => $custoEstimado,
                ]);
            }

            if ($item->tipo === 'manual') {
                $custoEstimado = $item->incluir
                    ? ((float) $item->valor_base_m2 * (float) $this->fator_correcao)
                    : 0;

                $item->update([
                    'area' => null,
                    'fator_correcao' => $this->fator_correcao,
                    'custo_estimado' => $custoEstimado,
                ]);
            }
        }

        $this->refresh();
        $this->load('itens');

        $total = $this->itens
            ->where('incluir', true)
            ->sum('custo_estimado');

        foreach ($this->itens as $item) {
            $percentual = ($total > 0 && $item->incluir)
                ? (($item->custo_estimado / $total) * 100)
                : 0;

            $item->update([
                'percentual' => $percentual,
            ]);
        }

        $this->update([
            'custo_total_estimado' => $total,
            'custo_por_m2' => ((float) $this->area_unidade > 0)
                ? ($total / (float) $this->area_unidade)
                : 0,
        ]);
    }

    public function importarEscoposAutomaticos(): void
    {
        $escopos = AsEscopo::query()
            ->globais()
            ->where('is_active', true)
            ->where('is_personalizado', 0)
            ->with('faixasArea')
            ->orderBy('escopo')
            ->get();

        foreach ($escopos as $escopo) {
            // tenta achar a faixa da simulação; se não existir, o valor fica 0
            $faixaDaSimulacao = $escopo->faixasArea
                ->firstWhere('id', $this->as_faixa_area_id);

            $valorBase = (float) ($faixaDaSimulacao?->pivot?->valor_m2 ?? 0);

            $itemExistente = $this->itens()
                ->where('tipo', 'auto')
                ->where('as_escopo_id', $escopo->id)
                ->first();

            $dados = [
                'as_escopo_id' => $escopo->id,
                'grupo_oi_id' => $escopo->grupo_oi_id,
                'tipo' => 'auto',
                'incluir' => true,
                'nome_escopo' => $escopo->escopo,
                'valor_base_m2' => $valorBase,
                'area' => $this->area_unidade,
                'fator_correcao' => $this->fator_correcao,
                'custo_estimado' => $valorBase * (float) $this->area_unidade * (float) $this->fator_correcao,
                'percentual' => 0,
            ];

            if ($itemExistente) {
                $itemExistente->update($dados);
            } else {
                $this->itens()->create($dados);
            }
        }

        $this->recalcularItensAutomaticosETotais();
    }
    /*
    public function importarEscoposManuais(array|string|null $escopos = null): void
    {
        $escopos ??= self::ESCOPOS_MANUAIS_PADRAO;

        $escopos = is_string($escopos)
            ? preg_split('/\r\n|\r|\n/', $escopos)
            : $escopos;

        foreach ($escopos as $escopo) {
            $dadosEscopo = $this->normalizarEscopoManual($escopo);

            if (! $dadosEscopo) {
                continue;
            }

            $valorBase = $dadosEscopo['valor_base_m2'];
            $incluir = $dadosEscopo['incluir'];

            $dados = [
                'as_escopo_id'   => null,
                'tipo'           => 'manual',
                'incluir'        => $incluir,
                'nome_escopo'    => $dadosEscopo['nome_escopo'],
                'valor_base_m2'  => $valorBase,
                'area'           => null,
                'fator_correcao' => $this->fator_correcao,
                'custo_estimado' => $incluir
                    ? ($valorBase * (float) $this->fator_correcao)
                    : 0,
                'percentual'     => 0,
            ];

            $itemExistente = $this->itens()
                ->where('tipo', 'manual')
                ->where('nome_escopo', $dadosEscopo['nome_escopo'])
                ->first();

            if ($itemExistente) {
                $itemExistente->update($dados);
            } else {
                $this->itens()->create($dados);
            }
        }

        $this->recalcularItensAutomaticosETotais();
    }
    */

    public function ordenarItensPorCustoEstimado(): void
    {
        $this->itens()
            ->orderByRaw("case when tipo = 'auto' then 0 when tipo = 'manual' then 1 else 2 end")
            ->orderByDesc('custo_estimado')
            ->orderBy('id')
            ->get()
            ->each(function ($item, $index) {
                $item->update([
                    'ordem' => $index + 1,
                ]);
            });
    }

    protected function normalizarEscopoManual(array|string $escopo): ?array
    {
        if (is_string($escopo)) {
            $escopo = trim($escopo);

            if ($escopo === '') {
                return null;
            }

            $partes = preg_split('/[;|]/', $escopo, 2);

            return [
                'nome_escopo' => trim($partes[0]),
                'valor_base_m2' => $this->normalizarValorManual($partes[1] ?? 0),
                'incluir' => true,
            ];
        }

        $nomeEscopo = trim((string) ($escopo['nome_escopo'] ?? $escopo['nome'] ?? $escopo['escopo'] ?? ''));

        if ($nomeEscopo === '') {
            return null;
        }

        return [
            'nome_escopo' => $nomeEscopo,
            'valor_base_m2' => $this->normalizarValorManual($escopo['valor_base_m2'] ?? $escopo['valor'] ?? 0),
            'incluir' => (bool) ($escopo['incluir'] ?? true),
        ];
    }

    protected function normalizarValorManual(mixed $valor): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = preg_replace('/[^\d,.\-]/', '', (string) $valor);

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace('.', '', $valor);
        }

        $valor = str_replace(',', '.', $valor);

        return (float) $valor;
    }
}
