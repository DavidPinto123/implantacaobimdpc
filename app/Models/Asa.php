<?php

namespace App\Models;

use App\Enums\AsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asa extends Model
{
    protected $table = 'autorizacao_servico_adicionais';

    protected $fillable = [
        'numero_asa',
        'projeto_id',
        'sigla',
        'endereco',
        'contrato',
        'controle_nota_fiscal_destino',
        'shell_cabe_como_negociacao',
        'shell_justificativa_negociacao',
        'subgrupo',
        'status',
        'codigo_as_emitida',
        'data_solicitacao',
        'data_aprovacao',
        'objeto',
        'justificativa',
        'altera_prazo',
        'dias_prazo',
        'valor_bruto',
        'desconto',
        'valor_total',
        'evidencias',
        'observacoes',
        'gestor_id',
        'solicitante',
        'planilha_apresentada',
        'foto_antes',
        'foto_depois',
        'projeto_orcado',
        'projeto_revisado',
        'escopo_contratado',
        'escopo_real',
        'descricao',
        'elaboracao_aditivo_id',
        'controle_nota_fiscal_auxiliar_id',
        'as_data_inicio',
        'as_data_termino',
        'as_data_entrega',
        'as_desconto',
        'as_parcelamento',
        'as_descricao_pdf',
        'as_itens_descricao_pdf',
        'as_anexos',
        'as_pdf',
        'as_criada_por_id',
        'as_criada_em',
        'as_enviada_por_id',
        'as_enviada_em',
        'as_cancelada_por_id',
        'as_cancelada_em',
        'as_motivo_cancelamento',
    ];

    protected $casts = [
        'status' => AsStatus::class,
        'data_solicitacao' => 'date',
        'data_aprovacao' => 'date',
        'as_data_inicio' => 'date',
        'as_data_termino' => 'date',
        'as_data_entrega' => 'date',
        'as_criada_em' => 'datetime',
        'as_enviada_em' => 'datetime',
        'as_cancelada_em' => 'datetime',
        'evidencias' => 'array',
        'shell_cabe_como_negociacao' => 'boolean',
        'valor_bruto' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'as_desconto' => 'decimal:2',
        'as_parcelamento' => 'array',
        'as_itens_descricao_pdf' => 'array',
        'as_anexos' => 'array',
        'foto_antes' => 'array',
        'foto_depois' => 'array',
        'projeto_orcado' => 'array',
        'projeto_revisado' => 'array',
        'escopo_contratado' => 'array',
        'escopo_real' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Asa $asa) {
            $numeroAsa = (string) ($asa->numero_asa ?? '');
            $asa->numero_asa_hash = $numeroAsa !== '' ? hash('sha256', $numeroAsa) : null;
        });

        static::retrieved(function (Asa $asa): void {
            $raw = $asa->getRawOriginal('status');

            if (is_string($raw) && AsStatus::tryFrom($raw) === null) {
                $normalizado = self::normalizarStatusLegado($raw);

                if ($normalizado !== null) {
                    $asa->attributes['status'] = $normalizado;
                    $asa->syncOriginalAttribute('status');
                }
            }
        });
    }

    private static function normalizarStatusLegado(string $valor): ?string
    {
        $mapa = [
            'Solicitado' => AsStatus::SOLICITADO->value,
            'solicitado' => AsStatus::SOLICITADO->value,
            'Aprovado' => AsStatus::APROVADO->value,
            'aprovado' => AsStatus::APROVADO->value,
            'Em aprovação do orçamento' => AsStatus::EM_APROVACAO_ORCAMENTO->value,
            'em aprovação do orçamento' => AsStatus::EM_APROVACAO_ORCAMENTO->value,
            'Reprovado' => AsStatus::REPROVADO_ORCAMENTO->value,
            'reprovado' => AsStatus::REPROVADO_ORCAMENTO->value,
        ];

        return $mapa[$valor] ?? null;
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(AsaItem::class, 'autorizacao_servico_adicional_id');
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function asCriadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'as_criada_por_id');
    }

    public function asEnviadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'as_enviada_por_id');
    }

    public function asCanceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'as_cancelada_por_id');
    }

    public function elaboracaoAditivo(): BelongsTo
    {
        return $this->belongsTo(ElaboracaoAditivo::class, 'elaboracao_aditivo_id');
    }

    public function controleNotaFiscalAuxiliar(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscalAuxiliar::class, 'controle_nota_fiscal_auxiliar_id');
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalNota::class, 'autorizacao_servico_adicional_id');
    }

    public function controlesNotaFiscal(): HasMany
    {
        return $this->hasMany(ControleNotaFiscal::class, 'autorizacao_servico_adicional_id');
    }
}
