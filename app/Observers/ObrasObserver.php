<?php

namespace App\Observers;

use App\Enums\CategoriaAtualizacaoObra;
use App\Models\AtualizacaoObra;
use App\Models\ControleNotaFiscal;
use App\Models\Obras;
use App\Services\ConstructinService;
use App\Services\ControleNotaFiscal\CriaControleNotaFiscalExpansao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ObrasObserver
{
    private const CAMPO_CATEGORIA = [
        'status' => CategoriaAtualizacaoObra::STATUS,
        'percentual_obra' => CategoriaAtualizacaoObra::PERCENTUAL,
        'percentual_obra_executado' => CategoriaAtualizacaoObra::PERCENTUAL,
        'civil' => CategoriaAtualizacaoObra::CIVIL,
        'eletrica' => CategoriaAtualizacaoObra::ELETRICA,
        'hidraulica' => CategoriaAtualizacaoObra::HIDRAULICA,
        'instalacao_ar_condicionado' => CategoriaAtualizacaoObra::CLIMATIZACAO,
        'maquinas_ar_condicionado' => CategoriaAtualizacaoObra::CLIMATIZACAO,
        'incendio' => CategoriaAtualizacaoObra::INCENDIO,
        'cronograma_implantacao' => CategoriaAtualizacaoObra::CRONOGRAMA,
        'cronograma_visi' => CategoriaAtualizacaoObra::CRONOGRAMA,
        'energia' => CategoriaAtualizacaoObra::ENERGIA,
        'previsao_ligacao_energia' => CategoriaAtualizacaoObra::ENERGIA,
        'agua' => CategoriaAtualizacaoObra::AGUA,
        'gas' => CategoriaAtualizacaoObra::GAS,
        'inauguracao' => CategoriaAtualizacaoObra::INAUGURACAO,
        'inicio_imp' => CategoriaAtualizacaoObra::IMPLANTACAO,
        'fim_imp' => CategoriaAtualizacaoObra::IMPLANTACAO,
        'imp_prazo_planej' => CategoriaAtualizacaoObra::IMPLANTACAO,
        'imp_prazo_realiz' => CategoriaAtualizacaoObra::IMPLANTACAO,
        'termo_de_posse' => CategoriaAtualizacaoObra::POSSE,
        'status_data_posse' => CategoriaAtualizacaoObra::POSSE,
    ];

    private const CAMPOS_IGNORADOS = [
        'updated_at',
        'created_at',
        'fotos',
        'desvio',
        'dias_para_inauguracao',
        'entrada_ponto_ate_inauguracao',
        'assinatura_ate_inauguracao',
        'dias_obra_inicio_pmo',
        'prazo_planejado',
        'prazo_realizado',
        'imp_prazo_planej',
        'imp_prazo_realiz',
    ];

    private const LABELS_CAMPOS = [
        'status' => 'Status',
        'percentual_obra' => 'Percentual da Obra',
        'percentual_obra_executado' => 'Percentual Executado',
        'civil' => 'Civil',
        'eletrica' => 'Elétrica',
        'hidraulica' => 'Hidráulica',
        'instalacao_ar_condicionado' => 'Instalação Ar Condicionado',
        'maquinas_ar_condicionado' => 'Máquinas Ar Condicionado',
        'incendio' => 'Incêndio',
        'cronograma_implantacao' => 'Cronograma Implantação',
        'cronograma_visi' => 'Cronograma VISI',
        'energia' => 'Energia',
        'previsao_ligacao_energia' => 'Previsão Ligação Energia',
        'agua' => 'Água',
        'gas' => 'Gás',
        'inauguracao' => 'Inauguração',
        'inicio_imp' => 'Início Implantação',
        'fim_imp' => 'Fim Implantação',
        'imp_prazo_planej' => 'Prazo Planejado Implantação',
        'imp_prazo_realiz' => 'Prazo Realizado Implantação',
        'termo_de_posse' => 'Termo de Posse',
        'status_data_posse' => 'Status Data Posse',
        'foto_perfil' => 'Foto de Perfil',
        'foto_capa' => 'Foto de Capa',
    ];

    private const CAMPOS_IMAGEM = [
        'foto_perfil',
        'foto_capa',
    ];

    private const CAMPOS_CALCULADOS = [
        'desvio',
        'dias_para_inauguracao',
        'entrada_ponto_ate_inauguracao',
        'assinatura_ate_inauguracao',
        'dias_obra_inicio_pmo',
        'prazo_planejado',
        'prazo_realizado',
        'imp_prazo_planej',
        'imp_prazo_realiz',
    ];

    public function saving(Obras $obra): void
    {
        if ($obra->projeto_id && ($obra->isDirty('projeto_id') || ! $obra->exists)) {
            $projeto = $obra->projeto()->with(['cidade', 'estado'])->first();
            if ($projeto) {
                if (empty($obra->endereco)) {
                    $obra->endereco = $projeto->endereco;
                }
                if (empty($obra->cidade)) {
                    $obra->cidade = $projeto->cidade?->nome;
                }
                if (empty($obra->uf)) {
                    $obra->uf = $projeto->estado?->sigla ?? $projeto->estado?->nome;
                }
            }
        }

        if (empty($obra->constructin_project_id) && $obra->projeto_id) {
            $novaSigla = $obra->projeto?->nova_sigla;
            if (filled($novaSigla)) {
                try {
                    $id = (new ConstructinService)->findProjectByNovaSigla($novaSigla);
                    if ($id) {
                        $obra->constructin_project_id = $id;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Auto-mapping Constructin falhou', [
                        'obra_id' => $obra->id ?? null,
                        'nova_sigla' => $novaSigla,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->sincronizarPercentuaisConstructin($obra);
        static::calcularCamposDerivados($obra);
    }

    public function updated(Obras $obra): void
    {
        $usuarioId = auth()->id();

        if (! $usuarioId) {
            return;
        }

        $changed = array_keys($obra->getChanges());
        $relevantChanges = array_diff($changed, self::CAMPOS_IGNORADOS);

        foreach ($relevantChanges as $campo) {
            $valorAnterior = $obra->getOriginal($campo);
            $valorNovo = $obra->getAttribute($campo);

            if ($valorAnterior === $valorNovo) {
                continue;
            }

            $categoria = self::CAMPO_CATEGORIA[$campo] ?? CategoriaAtualizacaoObra::GERAL;
            $label = self::LABELS_CAMPOS[$campo] ?? ucfirst(str_replace('_', ' ', $campo));

            $anteriorFormatado = $this->formatarValor($valorAnterior);
            $novoFormatado = $this->formatarValor($valorNovo);
            $valorAnteriorSerializado = $this->serializarValor($valorAnterior);
            $valorNovoSerializado = $this->serializarValor($valorNovo);

            $titulo = "{$label} alterado de '{$anteriorFormatado}' para '{$novoFormatado}'";

            AtualizacaoObra::create([
                'obra_id' => $obra->id,
                'usuario_id' => $usuarioId,
                'categoria' => $categoria,
                'titulo' => $titulo,
                'campo_alterado' => $campo,
                'valor_anterior' => $valorAnteriorSerializado,
                'valor_novo' => $valorNovoSerializado,
                'automatico' => true,
            ]);
        }
    }

    public function created(Obras $obra): void
    {
        app(CriaControleNotaFiscalExpansao::class)->handle($obra);
    }

    public function deleting(Obras $obra): void
    {
        if (! $obra->isForceDeleting()) {
            return;
        }

        if ($this->possuiControleNotaFiscalComVinculoFiscal($obra)) {
            throw ValidationException::withMessages([
                'obra' => 'Não é possível excluir definitivamente a obra porque há controle de notas fiscais com AS, ASA ou nota fiscal importada vinculada.',
            ]);
        }

        ControleNotaFiscal::withoutEvents(function () use ($obra): void {
            $obra->controlesNotaFiscal()
                ->with(['itens', 'auxiliares'])
                ->get()
                ->each(function ($controle): void {
                    $controle->itens->each->delete();
                    $controle->auxiliares->each->delete();
                    $controle->delete();
                });
        });
    }

    private function formatarValor(mixed $valor): string
    {
        if ($valor === null) {
            return '(vazio)';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d/m/Y');
        }

        if (is_bool($valor)) {
            return $valor ? 'Sim' : 'Não';
        }

        if (is_array($valor)) {
            return $this->formatarArray($valor);
        }

        return (string) $valor;
    }

    private function possuiControleNotaFiscalComVinculoFiscal(Obras $obra): bool
    {
        return $obra->controlesNotaFiscal()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('autorizacao_servico_adicional_id')
                    ->orWhereHas('itens.autorizacaoServico')
                    ->orWhereHas('itens.notasFiscais')
                    ->orWhereHas('auxiliares.asas')
                    ->orWhereHas('auxiliares.notasFiscais');
            })
            ->exists();
    }

    private function serializarValor(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_array($valor)) {
            return $this->formatarArray($valor);
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }

    private function formatarArray(array $valor): string
    {
        if (array_is_list($valor)) {
            $itens = array_map(function ($item) {
                if (is_array($item)) {
                    return $this->formatarArray($item);
                }

                return $this->formatarValor($item);
            }, $valor);

            return implode(', ', array_filter($itens, static fn ($item) => $item !== ''));
        }

        return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '(vazio)';
    }

    private function sincronizarPercentuaisConstructin(Obras $obra): void
    {
        if (! $obra->constructin_project_id) {
            return;
        }

        try {
            $progress = (new ConstructinService)->getProgressPercentages((int) $obra->constructin_project_id);

            if ($progress['percentual_obra'] !== null) {
                $obra->percentual_obra = $progress['percentual_obra'];
            }

            if ($progress['percentual_obra_executado'] !== null) {
                $obra->percentual_obra_executado = $progress['percentual_obra_executado'];
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao sincronizar percentuais do Constructin', [
                'obra_id' => $obra->id ?? null,
                'constructin_project_id' => $obra->constructin_project_id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    public static function calcularCamposDerivados(Obras $obra): void
    {
        // desvio = executado - previsto
        $obra->desvio = ($obra->percentual_obra_executado !== null && $obra->percentual_obra !== null)
            ? round($obra->percentual_obra_executado - $obra->percentual_obra, 2)
            : null;

        // dias_para_inauguracao (contagem regressiva até inauguração)
        $dataRef = $obra->inauguracao
            ?? ($obra->fim_imp ? Carbon::parse($obra->fim_imp)->addDay() : null);
        $obra->dias_para_inauguracao = $dataRef
            ? (int) now()->startOfDay()->diffInDays(Carbon::parse($dataRef)->startOfDay(), false)
            : null;

        // entrada_ponto_ate_inauguracao
        $obra->entrada_ponto_ate_inauguracao = ($obra->entrada_ponto && $obra->inauguracao)
            ? (int) Carbon::parse($obra->entrada_ponto)->startOfDay()->diffInDays(Carbon::parse($obra->inauguracao)->startOfDay())
            : null;

        // assinatura_ate_inauguracao
        $obra->assinatura_ate_inauguracao = ($obra->data_assinatura_contrato && $obra->inauguracao)
            ? (int) Carbon::parse($obra->data_assinatura_contrato)->startOfDay()->diffInDays(Carbon::parse($obra->inauguracao)->startOfDay())
            : null;

        // dias_obra_inicio_pmo
        $obra->dias_obra_inicio_pmo = ($obra->inicio_imp && $obra->fim_imp)
            ? (int) Carbon::parse($obra->inicio_imp)->startOfDay()->diffInDays(Carbon::parse($obra->fim_imp)->startOfDay())
            : null;

        // prazo_planejado (execução)
        $obra->prazo_planejado = ($obra->inicio && $obra->fim)
            ? (int) Carbon::parse($obra->inicio)->startOfDay()->diffInDays(Carbon::parse($obra->fim)->startOfDay())
            : null;

        // prazo_realizado (execução)
        $inicioReal = $obra->inicio_real ?? $obra->inicio;
        $obra->prazo_realizado = ($inicioReal && $obra->fim)
            ? (int) Carbon::parse($inicioReal)->startOfDay()->diffInDays(Carbon::parse($obra->fim)->startOfDay())
            : null;

        // imp_prazo_planej (implantação planejado)
        $obra->imp_prazo_planej = ($obra->inicio_imp && $obra->fim_imp)
            ? (int) Carbon::parse($obra->inicio_imp)->startOfDay()->diffInDays(Carbon::parse($obra->fim_imp)->startOfDay())
            : null;

        // imp_prazo_realiz (implantação realizado)
        $fimRef = $obra->inauguracao ?? $obra->fim_imp;
        $obra->imp_prazo_realiz = ($obra->inicio_imp && $fimRef)
            ? (int) Carbon::parse($obra->inicio_imp)->startOfDay()->diffInDays(Carbon::parse($fimRef)->startOfDay())
            : null;
    }

    public static function getCamposCalculados(): array
    {
        return self::CAMPOS_CALCULADOS;
    }
}
