<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faturamento extends Model
{
    use HasFactory;

    protected $table = 'faturamentos'; // garante que é essa a tabela

    protected $fillable = [
        'nota_fiscal_id',
        'tipo', // mao_obra ou material
        'empresa',
        'numero_nf',
        'cnpj_faturamento_smart',
        'valor_acumulado_medido_nf',
        'emissao',
        'recebimento',
        'envio',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'valor_acumulado_medido_nf' => 'decimal:2',
        'emissao' => 'date',
        'recebimento' => 'date',
        'envio' => 'date',
    ];

    public function notaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal_id');
    }
}
