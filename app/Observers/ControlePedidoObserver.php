<?php

namespace App\Observers;

use App\Enums\CategoriaAtualizacaoObra;
use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\AtualizacaoObra;
use App\Models\Construtora;
use App\Models\ControlePedido;
use App\Models\ObraDocumento;
use App\Models\Obras;

class ControlePedidoObserver
{
    private const DOCUMENTOS_AUTOMATICOS_CIVIL_RECHEIO = [
        'Seguro de obra',
        'Manual da Obra',
    ];

    private const CAMPOS_RASTREADOS = [
        'status' => 'Status CNPJ',
        'situacao' => 'Situação',
        'contratacao' => 'Data Contratação',
        'elaboracao_contrato' => 'Elaboração Contrato',
        'valor_oi' => 'Valor OI',
        'valor_realizado' => 'Valor Realizado',
        'construtora_id' => 'Fornecedor',
    ];

    public function created(ControlePedido $controlePedido): void
    {
        $usuarioId = auth()->id();

        if (! $usuarioId) {
            return;
        }

        foreach ($this->obrasDoprojeto($controlePedido) as $obra) {
            AtualizacaoObra::create([
                'obra_id' => $obra->id,
                'usuario_id' => $usuarioId,
                'categoria' => CategoriaAtualizacaoObra::CONTRATACAO,
                'titulo' => 'Controle de Contratações criado',
                'automatico' => true,
            ]);
        }

        if ($this->pedidoAtivado($controlePedido->pedidos, '1.1')) {
            $this->garantirDocumentosCivilRecheio($controlePedido, $usuarioId);
        }
    }

    public function updated(ControlePedido $controlePedido): void
    {
        $usuarioId = auth()->id();

        if (! $usuarioId) {
            return;
        }

        $obras = $this->obrasDoprojeto($controlePedido);

        if ($obras->isEmpty()) {
            return;
        }

        if ($controlePedido->wasChanged('pedidos')) {
            $oldRaw = $controlePedido->getOriginal('pedidos');
            $oldPedidos = is_array($oldRaw) ? $oldRaw : (json_decode($oldRaw, true) ?? []);
            $newPedidos = $controlePedido->pedidos ?? [];

            $linhas = [];
            foreach (ControlePedidoResource::pedidosMap() as $nome => $codigos) {
                $key = str_replace('.', '_', $codigos[0]);
                $antes = (bool) data_get($oldPedidos, $key, false);
                $depois = (bool) data_get($newPedidos, $key, false);

                if ($antes !== $depois) {
                    $linhas[] = $nome.': '.($antes ? 'Sim' : 'Não').' → '.($depois ? 'Sim' : 'Não');
                }
            }

            $conteudo = ! empty($linhas) ? implode("\n", $linhas) : null;

            foreach ($obras as $obra) {
                AtualizacaoObra::create([
                    'obra_id' => $obra->id,
                    'usuario_id' => $usuarioId,
                    'categoria' => CategoriaAtualizacaoObra::CONTRATACAO,
                    'titulo' => 'Pedidos contratados atualizados',
                    'conteudo' => $conteudo,
                    'automatico' => true,
                ]);
            }

            if (
                ! $this->pedidoAtivado($oldPedidos, '1.1')
                && $this->pedidoAtivado($newPedidos, '1.1')
            ) {
                $this->garantirDocumentosCivilRecheio($controlePedido, $usuarioId);
            }
        }

        foreach (self::CAMPOS_RASTREADOS as $campo => $label) {
            if (! $controlePedido->wasChanged($campo)) {
                continue;
            }

            $valorAnteriorRaw = $controlePedido->getOriginal($campo);
            $valorNovoRaw = $controlePedido->getAttribute($campo);

            if ($valorAnteriorRaw === $valorNovoRaw) {
                continue;
            }

            if ($campo === 'construtora_id') {
                $anteriorFormatado = $valorAnteriorRaw
                    ? (Construtora::find($valorAnteriorRaw)?->nome ?? $valorAnteriorRaw)
                    : '(vazio)';
                $novoFormatado = $controlePedido->construtora?->nome ?? '(vazio)';
            } else {
                $anteriorFormatado = $this->formatarValor($valorAnteriorRaw);
                $novoFormatado = $this->formatarValor($valorNovoRaw);
            }

            foreach ($obras as $obra) {
                AtualizacaoObra::create([
                    'obra_id' => $obra->id,
                    'usuario_id' => $usuarioId,
                    'categoria' => CategoriaAtualizacaoObra::CONTRATACAO,
                    'titulo' => "{$label} alterado de '{$anteriorFormatado}' para '{$novoFormatado}'",
                    'campo_alterado' => $campo,
                    'valor_anterior' => (string) $valorAnteriorRaw,
                    'valor_novo' => (string) $valorNovoRaw,
                    'automatico' => true,
                ]);
            }
        }
    }

    private function obrasDoprojeto(ControlePedido $controlePedido)
    {
        return Obras::where('projeto_id', $controlePedido->projeto_id)->get();
    }

    private function pedidoAtivado(mixed $pedidos, string $codigo): bool
    {
        $arrayPedidos = is_array($pedidos) ? $pedidos : (json_decode((string) $pedidos, true) ?? []);

        return (bool) data_get($arrayPedidos, str_replace('.', '_', $codigo), false);
    }

    private function garantirDocumentosCivilRecheio(ControlePedido $controlePedido, int $usuarioId): void
    {
        foreach ($this->obrasDoprojeto($controlePedido) as $obra) {
            foreach (self::DOCUMENTOS_AUTOMATICOS_CIVIL_RECHEIO as $nomeDocumento) {
                $jaExiste = ObraDocumento::query()
                    ->where('obra_id', $obra->id)
                    ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nomeDocumento)])
                    ->exists();

                if ($jaExiste) {
                    continue;
                }

                ObraDocumento::create([
                    'obra_id' => $obra->id,
                    'nome' => $nomeDocumento,
                    'status' => 'pendente',
                    'usuario_id' => $usuarioId,
                ]);
            }
        }
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

        if (is_numeric($valor) && str_contains((string) $valor, '.')) {
            return 'R$ '.number_format((float) $valor, 2, ',', '.');
        }

        return (string) $valor;
    }
}
