<?php

namespace App\Services;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\ModoAncoraCronograma;
use App\Enums\StatusCronograma;
use App\Enums\TipoDiasTemplate;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseHistorico;
use App\Models\CronogramaFaseItem;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseItem;
use App\Models\Projeto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Serviço responsável por aplicar templates híbridos de cronograma em obras
 * e por recalcular em cascata quando uma fase é alterada manualmente.
 *
 * O cálculo é feito topologicamente a partir do grafo de dependências,
 * ancorado numa fase marcada como is_ancora cuja data vem de um campo do projeto.
 * Fases alcançáveis "para frente" a partir da âncora (que dependem dela direta ou
 * transitivamente) são calculadas em ordem crescente. As demais são calculadas
 * "para trás" a partir da âncora, garantindo que terminem antes da data âncora.
 */
class CronogramaTemplateService
{
    /**
     * Status que congelam a data prevista de uma fase. Qualquer recálculo,
     * shift em cascata ou reaplicação de template deve PULAR fases com esse
     * status para preservar o histórico real e evitar reescrita de eventos
     * já consumados.
     *
     * Pública para reuso por outras camadas (page, modal de edição em lote,
     * exports) — fonte única de verdade do que conta como "fase travada".
     */
    public const STATUS_BLOQUEADOS_RECALCULO = [
        StatusCronograma::CONCLUIDO,
        StatusCronograma::REALIZADO,
        StatusCronograma::ASSINADO,
        StatusCronograma::FINALIZADO,
        StatusCronograma::PRONTO,
        StatusCronograma::BLOQUEADO,
    ];

    public static function bloqueadoRecalculo(?StatusCronograma $status): bool
    {
        return $status !== null && in_array($status, self::STATUS_BLOQUEADOS_RECALCULO, true);
    }

    /**
     * Verifica se a fase deve ser preservada de qualquer recálculo, levando
     * em conta status finalizador OU flag bloqueada_pos_contrato (cadeado
     * automático ativado quando ASSINATURA_CONTRATO vira ASSINADO).
     */
    public static function faseBloqueada(CronogramaFase $fase): bool
    {
        return self::bloqueadoRecalculo($fase->status) || ($fase->bloqueada_pos_contrato ?? false);
    }

    public function aplicar(CronogramaTemplate $template, Projeto $projeto, ?CarbonImmutable $dataAncoraOverride = null): void
    {
        $fasesTemplate = $template->fases()->with(['dependencias.dependeDeItem', 'itens.dependencias'])->get();

        $ancoraFase = $fasesTemplate->firstWhere('is_ancora', true);
        if (! $ancoraFase) {
            throw new RuntimeException(
                "Template '{$template->nome}' não possui fase âncora definida (is_ancora=true)."
            );
        }

        $dataAncora = $dataAncoraOverride ?? $this->resolverAncora($template, $projeto);
        if (! $dataAncora) {
            throw new RuntimeException(
                "Âncora '{$template->ancora_campo}' está vazia para o projeto #{$projeto->id}. Preencha a data antes de aplicar o template."
            );
        }

        $valuesTemplate = $fasesTemplate->pluck('fase')->map(fn (FaseCronograma $f) => $f->value)->all();

        $calculadas = $this->calcularDatas($fasesTemplate, $ancoraFase, $dataAncora);

        // Registra mudança de template no histórico do projeto
        $templateAnterior = CronogramaFase::where('projeto_id', $projeto->id)
            ->whereNotNull('cronograma_template_id')
            ->first()
            ?->template;

        $nomeAnterior = $templateAnterior?->nome;

        if ($nomeAnterior !== $template->nome) {
            CronogramaFaseHistorico::create([
                'projeto_id' => $projeto->id,
                'cronograma_fase_id' => null,
                'campo_alterado' => 'template',
                'valor_anterior' => $nomeAnterior,
                'valor_novo' => $template->nome,
                'motivo' => $nomeAnterior
                    ? "Template alterado de \"{$nomeAnterior}\" para \"{$template->nome}\""
                    : "Template \"{$template->nome}\" aplicado ao projeto",
                'usuario_id' => auth()->id(),
                'automatico' => false,
            ]);
        }

        CronogramaFase::where('projeto_id', $projeto->id)
            ->whereNotIn('fase', $valuesTemplate)
            ->forceDelete();

        $motivoTemplate = "Aplicação de template: {$template->nome}";

        foreach ($fasesTemplate as $tplFase) {
            $chave = $tplFase->fase->value;
            $datas = $calculadas[$chave] ?? null;
            if (! $datas) {
                continue;
            }

            $faseExistente = CronogramaFase::where('projeto_id', $projeto->id)
                ->where('fase', $chave)
                ->first();

            $inicioAntes = $faseExistente?->data_prevista_inicio?->toDateString();
            $fimAntes = $faseExistente?->data_prevista_fim?->toDateString();

            // Fases já concluídas/bloqueadas preservam datas previstas atuais.
            // Atualizamos só metadados (template_id, ordem, marco) sem mexer em datas.
            $bloqueada = $faseExistente && self::faseBloqueada($faseExistente);

            $atributos = [
                'ordem' => $tplFase->fase->ordem(),
                'marco' => $tplFase->fase->marco(),
                'cronograma_template_id' => $template->id,
                'cronograma_template_fase_id' => $tplFase->id,
                'titulo_personalizado' => $tplFase->titulo_personalizado ?: null,
                'valor' => $tplFase->valor,
                'descricao' => $tplFase->descricao,
                'regra_duracao_dias' => null,
                'regra_tipo_dias' => null,
                'regra_customizada' => false,
                'regra_elastica' => null,
                'visivel' => (bool) $tplFase->visivel,
            ];

            if (! $bloqueada) {
                $atributos['data_prevista_inicio'] = $datas['inicio']->toDateString();
                $atributos['data_prevista_fim'] = $datas['fim']->toDateString();
            }

            $faseObra = CronogramaFase::updateOrCreate(
                ['projeto_id' => $projeto->id, 'fase' => $chave],
                $atributos
            );

            if (! $bloqueada) {
                CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_inicio', $inicioAntes, $datas['inicio']->toDateString(), $motivoTemplate, auth()->id(), true);
                CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_fim', $fimAntes, $datas['fim']->toDateString(), $motivoTemplate, auth()->id(), true);
            }

            $faseObra->dependencias()->delete();
        }

        $this->sincronizarItensTemplate($projeto, $fasesTemplate);
        $this->sincronizarDatasComProjeto($projeto);

        // Após aplicar o template com sucesso, o projeto entra em "operação
        // corrente" — alterações futuras em data_posse não devem recalcular o
        // cronograma. O modo de âncora alterna automaticamente para OBRAS.
        if ($projeto->modo_ancora !== ModoAncoraCronograma::OBRAS) {
            $projeto->update(['modo_ancora' => ModoAncoraCronograma::OBRAS->value]);
        }
    }

    /**
     * @param  Collection<int, CronogramaTemplateFase>  $fasesTemplate
     */
    private function sincronizarItensTemplate(Projeto $projeto, Collection $fasesTemplate): void
    {
        $normalizarTitulo = fn (string $titulo): string => mb_strtolower(trim($titulo));
        $fasesObra = CronogramaFase::where('projeto_id', $projeto->id)
            ->whereIn('cronograma_template_fase_id', $fasesTemplate->pluck('id'))
            ->get()
            ->keyBy('cronograma_template_fase_id');

        $itensSincronizados = [];

        foreach ($fasesTemplate as $tplFase) {
            $faseObra = $fasesObra->get($tplFase->id);
            if (! $faseObra) {
                continue;
            }

            $itensAtuais = CronogramaFaseItem::where('cronograma_fase_id', $faseObra->id)
                ->where('origem', 'template')
                ->get();

            $itensPorTitulo = $itensAtuais->keyBy(fn (CronogramaFaseItem $item) => $normalizarTitulo($item->titulo));
            $titulosTemplate = $tplFase->itens
                ->pluck('titulo')
                ->map(fn (string $titulo) => $normalizarTitulo($titulo))
                ->filter()
                ->values();

            $ordemBase = (int) (CronogramaFaseItem::where('cronograma_fase_id', $faseObra->id)
                ->whereNull('parent_id')
                ->where(function ($query) {
                    $query->where('origem', '!=', 'template')
                        ->orWhereNull('origem');
                })
                ->max('ordem') ?? -1);

            // Raízes primeiro, filhos depois — garante que o pai já está em $itensSincronizados
            $itensOrdenados = $tplFase->itens
                ->sortBy(fn ($i) => [$i->parent_id ? 1 : 0, $i->ordem])
                ->values();

            foreach ($itensOrdenados as $tplItem) {
                $titulo = trim($tplItem->titulo);
                if ($titulo === '') {
                    continue;
                }

                $parentItemId = null;
                if ($tplItem->parent_id && isset($itensSincronizados[$tplItem->parent_id])) {
                    $parentItemId = $itensSincronizados[$tplItem->parent_id]->id;
                }

                $itemExistente = $itensPorTitulo->get($normalizarTitulo($titulo));

                $atributos = [
                    'parent_id' => $parentItemId,
                    'titulo' => $titulo,
                    'valor' => $tplItem->valor,
                    'descricao' => $tplItem->descricao,
                    'ordem' => $ordemBase + ((int) $tplItem->ordem) + 1,
                    'origem' => 'template',
                ];

                if ($itemExistente) {
                    $itemExistente->update($atributos);
                    $itensSincronizados[$tplItem->id] = $itemExistente;

                    continue;
                }

                $itensSincronizados[$tplItem->id] = CronogramaFaseItem::create($atributos + [
                    'cronograma_fase_id' => $faseObra->id,
                    'recebido' => false,
                ]);
            }

            $itensAtuais
                ->reject(fn (CronogramaFaseItem $item) => $titulosTemplate->contains($normalizarTitulo($item->titulo)))
                ->each(function (CronogramaFaseItem $item): void {
                    $item->children()->update(['parent_id' => null]);
                    $item->delete();
                });
        }

        foreach ($fasesTemplate as $tplFase) {
            foreach ($tplFase->itens as $tplItem) {
                $itemProjeto = $itensSincronizados[$tplItem->id] ?? null;
                if (! $itemProjeto) {
                    continue;
                }

                $itemProjeto->dependencias()->delete();

                foreach ($tplItem->dependencias as $depItem) {
                    $dependeDeItemId = null;
                    if ($depItem->depende_de_item_id && isset($itensSincronizados[$depItem->depende_de_item_id])) {
                        $dependeDeItemId = $itensSincronizados[$depItem->depende_de_item_id]->id;
                    }

                    $dependeDeFaseId = null;
                    if ($depItem->depende_de_template_fase_id && $fasesObra->has($depItem->depende_de_template_fase_id)) {
                        $dependeDeFaseId = $fasesObra->get($depItem->depende_de_template_fase_id)->id;
                    }

                    if ($dependeDeItemId || $dependeDeFaseId) {
                        $itemProjeto->dependencias()->create([
                            'depende_de_item_id' => $dependeDeItemId,
                            'depende_de_fase_id' => $dependeDeFaseId,
                            'gatilho' => $depItem->gatilho instanceof GatilhoTemplateFase
                                ? $depItem->gatilho->value
                                : (string) $depItem->gatilho,
                            'gap_dias' => (int) $depItem->gap_dias,
                        ]);
                    }
                }

                $dependenciaItemId = null;
                if ($tplItem->depende_de_item_id && isset($itensSincronizados[$tplItem->depende_de_item_id])) {
                    $dependenciaItemId = $itensSincronizados[$tplItem->depende_de_item_id]->id;
                }

                $dependenciaFaseId = null;
                if ($tplItem->depende_de_template_fase_id && $fasesObra->has($tplItem->depende_de_template_fase_id)) {
                    $dependenciaFaseId = $fasesObra->get($tplItem->depende_de_template_fase_id)->id;
                }

                if (
                    $itemProjeto->depende_de_item_id !== $dependenciaItemId
                    || $itemProjeto->depende_de_fase_id !== $dependenciaFaseId
                ) {
                    $itemProjeto->depende_de_item_id = $dependenciaItemId;
                    $itemProjeto->depende_de_fase_id = $dependenciaFaseId;
                    $itemProjeto->save();
                }
            }
        }
    }

    /**
     * Simula o cálculo sem persistir. Retorna array indexado pelo value da fase.
     *
     * @return array<string, array{inicio: CarbonImmutable, fim: CarbonImmutable}>
     */
    public function simular(CronogramaTemplate $template, ?CarbonImmutable $dataAncora = null): array
    {
        $fasesTemplate = $template->fases()->with('dependencias.dependeDeItem')->get();
        $ancoraFase = $fasesTemplate->firstWhere('is_ancora', true);
        if (! $ancoraFase) {
            throw new RuntimeException("Template '{$template->nome}' não possui fase âncora.");
        }

        return $this->calcularDatas($fasesTemplate, $ancoraFase, $dataAncora ?? CarbonImmutable::today());
    }

    public function resolverAncora(CronogramaTemplate $template, Projeto $projeto): ?CarbonImmutable
    {
        $campo = $template->ancora_campo;

        if (str_starts_with($campo, 'projeto.')) {
            $atributo = str_replace('projeto.', '', $campo);
            $valor = data_get($projeto, $atributo);

            if (! $valor && $atributo === 'data_posse') {
                $valor = data_get($projeto, 'data_entrega_shell');
            }
        } elseif (in_array($campo, ['inicio', 'fim'])) {
            $projeto->loadMissing('obras');
            $obra = $projeto->obras->first();
            $valor = $obra ? data_get($obra, $campo) : null;
        } else {
            $valor = data_get($projeto, $campo);
        }

        if (! $valor) {
            return null;
        }

        return $valor instanceof CarbonImmutable
            ? $valor
            : CarbonImmutable::parse((string) $valor);
    }

    /**
     * Recalcula o cronograma inteiro da obra a partir das regras efetivas
     * (override local OU template), rodando o mesmo algoritmo híbrido (forward
     * a partir da âncora + backward para o subgrafo ascendente) que o aplicar().
     *
     * Chamado quando o usuário altera duração, tipo de dias ou visibilidade de
     * uma fase no modal de edição — a mudança se propaga para todas as fases,
     * respeitando o grafo de dependências em ambos os sentidos.
     */
    /**
     * Recalcula a fase e seus dependentes a partir do template aplicado.
     *
     * @param  bool  $apenasElasticas  quando true, aplica os novos valores
     *                                 somente em fases elásticas — usado quando a Posse foi antecipada e o
     *                                 prazo total ficou menor: fases com duração fixa mantêm o planejamento,
     *                                 operador decide manualmente onde "comer gordura".
     */
    public function recalcularFaseEDependentes(CronogramaFase $fase, bool $apenasElasticas = false): void
    {
        $projeto = $fase->projeto;
        if (! $projeto || ! $fase->cronograma_template_id) {
            return;
        }

        $template = $fase->template()->with('fases.dependencias')->first();
        if (! $template) {
            return;
        }

        $ancoraFase = $template->fases->firstWhere('is_ancora', true);
        if (! $ancoraFase) {
            return;
        }

        $dataAncora = $this->resolverAncora($template, $projeto);
        if (! $dataAncora) {
            return;
        }

        $fasesObra = CronogramaFase::visiveis()
            ->with(['dependencias.dependeDeItem.fase', 'templateFase.dependencias.dependeDeItem.templateFase'])
            ->where('projeto_id', $projeto->id)
            ->get()
            ->keyBy(fn (CronogramaFase $f) => $f->fase->value);

        if ($fasesObra->isEmpty()) {
            return;
        }

        // Extrai mapas de regras efetivas (override local > template) para a obra.
        [$duracoes, $tipoDias, $deps, $elasticas] = $this->extrairRegrasEfetivas($fasesObra);

        $ancoraValue = $ancoraFase->fase->value;
        if (! isset($duracoes[$ancoraValue])) {
            return;
        }

        try {
            $resolvidas = $this->calcularDatasFromMaps(
                $ancoraValue,
                $dataAncora,
                $duracoes,
                $tipoDias,
                $deps,
                null,
                $elasticas,
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($resolvidas as $chave => $datas) {
            $faseObra = $fasesObra[$chave] ?? null;
            if (! $faseObra) {
                continue;
            }

            if (self::faseBloqueada($faseObra)) {
                continue;
            }

            // Recálculo assimétrico: quando o prazo encurtou (ex.: posse
            // antecipada), só atualiza fases elásticas — as demais mantêm a
            // duração planejada e o operador decide manualmente o ajuste.
            if ($apenasElasticas && empty($elasticas[$chave])) {
                continue;
            }

            $inicioAntes = $faseObra->data_prevista_inicio?->toDateString();
            $fimAntes = $faseObra->data_prevista_fim?->toDateString();

            $faseObra->data_prevista_inicio = $datas['inicio']->toDateString();
            $faseObra->data_prevista_fim = $datas['fim']->toDateString();
            $faseObra->saveQuietly();

            CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_inicio', $inicioAntes, $datas['inicio']->toDateString(), 'Recálculo em cascata', auth()->id(), true);
            CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_fim', $fimAntes, $datas['fim']->toDateString(), 'Recálculo em cascata', auth()->id(), true);
        }

        $this->sincronizarDatasComProjeto($projeto);
    }

    /**
     * Desloca uniformemente toda a componente conectada (dependentes +
     * antecessoras transitivas) de uma fase editada, em $deltaDias dias corridos.
     *
     * A própria fase editada NÃO é alterada aqui — ela já deve ter sido salva
     * com os novos valores antes de chamar este método. O serviço só atualiza
     * as demais fases da componente, preservando seus gaps e durações.
     */
    public function shiftComponent(CronogramaFase $fase, int $deltaDias): void
    {
        if ($deltaDias === 0) {
            return;
        }

        $fasesObra = CronogramaFase::visiveis()
            ->with(['dependencias.dependeDeItem.fase', 'templateFase.dependencias.dependeDeItem.templateFase'])
            ->where('projeto_id', $fase->projeto_id)
            ->get()
            ->keyBy(fn (CronogramaFase $f) => $f->fase->value);

        if ($fasesObra->isEmpty()) {
            return;
        }

        [, , $deps] = $this->extrairRegrasEfetivas($fasesObra);

        $componente = $this->bfsBidirectional($fase->fase->value, $deps);

        $motivoShift = "Deslocamento em cascata (shift de {$deltaDias} dias)";

        foreach ($componente as $v => $_) {
            if ($v === $fase->fase->value) {
                continue;
            }
            $faseObra = $fasesObra[$v] ?? null;
            if (! $faseObra || ! $faseObra->data_prevista_inicio || ! $faseObra->data_prevista_fim) {
                continue;
            }

            if (self::faseBloqueada($faseObra)) {
                continue;
            }

            $inicioAntes = $faseObra->data_prevista_inicio->toDateString();
            $fimAntes = $faseObra->data_prevista_fim->toDateString();

            $faseObra->data_prevista_inicio = $faseObra->data_prevista_inicio->copy()->addDays($deltaDias);
            $faseObra->data_prevista_fim = $faseObra->data_prevista_fim->copy()->addDays($deltaDias);
            $faseObra->saveQuietly();

            CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_inicio', $inicioAntes, $faseObra->data_prevista_inicio->toDateString(), $motivoShift, auth()->id(), true);
            CronogramaService::registrarHistoricoDatas($faseObra, 'data_prevista_fim', $fimAntes, $faseObra->data_prevista_fim->toDateString(), $motivoShift, auth()->id(), true);
        }
    }

    /**
     * BFS bidirecional a partir de $raiz no grafo de dependências.
     * Encontra todas as fases alcançáveis seguindo arestas tanto no sentido
     * X → Y (Y depende de X, "sucessor") quanto Y ← X (X é dep de Y, "antecessor").
     *
     * @param  array<string, array<int, object>>  $deps
     * @return array<string, true>
     */
    private function bfsBidirectional(string $raiz, array $deps): array
    {
        $visitados = [$raiz => true];
        $fila = [$raiz];

        while (! empty($fila)) {
            $atual = array_shift($fila);

            // Sucessores: fases que têm $atual na lista de deps.
            foreach ($deps as $v => $listaDeps) {
                if (isset($visitados[$v])) {
                    continue;
                }
                foreach ($listaDeps as $d) {
                    if ($d->de === $atual) {
                        $visitados[$v] = true;
                        $fila[] = $v;
                        break;
                    }
                }
            }

            // Antecessores: deps da própria $atual.
            foreach ($deps[$atual] ?? [] as $d) {
                if (! isset($visitados[$d->de])) {
                    $visitados[$d->de] = true;
                    $fila[] = $d->de;
                }
            }
        }

        return $visitados;
    }

    /**
     * Propaga em cascata apenas para a frente a partir de uma fase cujas datas
     * previstas foram editadas manualmente pelo usuário. A fase alterada é tratada
     * como ponto fixo (pin) e as sucessoras dela no grafo são recalculadas
     * usando as datas novas como base. Fases que não dependem (direta ou
     * transitivamente) da alterada ficam intactas.
     *
     * @deprecated Use shiftComponent() para propagação bidirecional uniforme.
     */
    public function propagarCascataDesdeData(CronogramaFase $fase): void
    {
        if (! $fase->data_prevista_inicio || ! $fase->data_prevista_fim) {
            return;
        }

        $fasesObra = CronogramaFase::visiveis()
            ->with(['dependencias.dependeDeItem.fase', 'templateFase.dependencias.dependeDeItem.templateFase'])
            ->where('projeto_id', $fase->projeto_id)
            ->get()
            ->keyBy(fn (CronogramaFase $f) => $f->fase->value);

        if ($fasesObra->isEmpty()) {
            return;
        }

        [$duracoes, $tipoDias, $deps, $elasticas] = $this->extrairRegrasEfetivas($fasesObra);

        $chaveAlterada = $fase->fase->value;
        $forwardSet = $this->bfsForward($chaveAlterada, $deps);

        if (count($forwardSet) <= 1) {
            return;
        }

        $resolvidas = [
            $chaveAlterada => [
                'inicio' => CarbonImmutable::parse($fase->data_prevista_inicio->toDateString()),
                'fim' => CarbonImmutable::parse($fase->data_prevista_fim->toDateString()),
            ],
        ];

        $ordemTop = $this->ordenacaoTopologica(array_keys($forwardSet), $deps);
        foreach ($ordemTop as $v) {
            if ($v === $chaveAlterada) {
                continue;
            }
            [$inicio, $fim] = $this->calcularFaseForward(
                $v,
                $deps[$v] ?? [],
                $duracoes[$v],
                $tipoDias[$v],
                $resolvidas,
                (bool) ($elasticas[$v] ?? false),
            );
            if (! $inicio) {
                continue;
            }
            $resolvidas[$v] = ['inicio' => $inicio, 'fim' => $fim];

            $faseObra = $fasesObra[$v] ?? null;
            if ($faseObra) {
                $faseObra->data_prevista_inicio = $inicio->toDateString();
                $faseObra->data_prevista_fim = $fim->toDateString();
                $faseObra->saveQuietly();
            }
        }
    }

    /**
     * Lista as fases visíveis da obra que dependem diretamente de $alvo.
     * Usado pela UI para bloquear ocultação de uma fase com dependentes ativos.
     */
    public function dependentesVisiveis(FaseCronograma $alvo, int $projetoId): Collection
    {
        $fasesObra = CronogramaFase::visiveis()
            ->with(['dependencias.dependeDeItem.fase', 'templateFase.dependencias.dependeDeItem.templateFase'])
            ->where('projeto_id', $projetoId)
            ->get();

        return $fasesObra->filter(function (CronogramaFase $f) use ($alvo) {
            $regra = $f->regraEfetiva();
            foreach ($regra->dependencias as $dep) {
                $value = $dep->depende_de_fase instanceof FaseCronograma
                    ? $dep->depende_de_fase->value
                    : (string) $dep->depende_de_fase;
                if ($value === $alvo->value) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * Cascata bidirecional a partir de uma fase alterada manualmente.
     *
     * 1. Calcula a fase alterada (pin).
     * 2. Forward: propaga para todas as fases que dependem dela (transitivamente).
     * 3. Reverse: sobe pela cadeia de dependências da fase alterada,
     *    recalculando cada ancestral por engenharia reversa da fórmula forward.
     *    Para cada ancestral recalculado, faz forward dos SEUS dependentes
     *    (capturando branches como obras → implantação → inauguração).
     *
     * @param  array<string,int>  $duracoes
     * @param  array<string,TipoDiasTemplate>  $tipoDias
     * @param  array<string, array<int, object>>  $deps
     * @return array<string, array{inicio: CarbonImmutable, fim: CarbonImmutable}>
     */
    public function calcularCascataBidirecional(
        string $faseAlteradaValue,
        CarbonImmutable $novaDataInicio,
        array $duracoes,
        array $tipoDias,
        array $deps,
        ?CarbonImmutable $novaDataFim = null,
        array $elasticas = [],
    ): array {
        $dur = $duracoes[$faseAlteradaValue] ?? 0;
        $tipo = $tipoDias[$faseAlteradaValue] ?? TipoDiasTemplate::CORRIDOS;
        $fim = $novaDataFim ?? (($elasticas[$faseAlteradaValue] ?? false)
            ? $novaDataInicio
            : ($dur > 0
                ? $this->adicionarDias($novaDataInicio, $dur - 1, $tipo)
                : $novaDataInicio));

        $resolvidas = [$faseAlteradaValue => ['inicio' => $novaDataInicio, 'fim' => $fim]];

        $this->cascataForwardInterno($faseAlteradaValue, $duracoes, $tipoDias, $deps, $resolvidas, $elasticas);

        $this->cascataReversaInterno($faseAlteradaValue, $duracoes, $tipoDias, $deps, $resolvidas);

        return $resolvidas;
    }

    private function cascataForwardInterno(
        string $raiz,
        array $duracoes,
        array $tipoDias,
        array $deps,
        array &$resolvidas,
        array $elasticas = [],
    ): void {
        $forwardSet = $this->bfsForward($raiz, $deps);
        $ordem = $this->ordenacaoTopologica(array_keys($forwardSet), $deps);

        foreach ($ordem as $v) {
            if (isset($resolvidas[$v])) {
                continue;
            }
            if (! isset($duracoes[$v])) {
                continue;
            }
            [$inicio, $fim] = $this->calcularFaseForward(
                $v,
                $deps[$v] ?? [],
                $duracoes[$v],
                $tipoDias[$v],
                $resolvidas,
                (bool) ($elasticas[$v] ?? false),
            );
            if ($inicio) {
                $resolvidas[$v] = ['inicio' => $inicio, 'fim' => $fim];
            }
        }
    }

    private function cascataReversaInterno(
        string $raiz,
        array $duracoes,
        array $tipoDias,
        array $deps,
        array &$resolvidas,
    ): void {
        $fila = [$raiz];
        $visitados = [$raiz => true];

        while (! empty($fila)) {
            $current = array_shift($fila);
            $currentDatas = $resolvidas[$current] ?? null;
            if (! $currentDatas) {
                continue;
            }

            foreach ($deps[$current] ?? [] as $d) {
                $ancestor = $d->de;
                if (isset($visitados[$ancestor])) {
                    continue;
                }
                $visitados[$ancestor] = true;

                if (isset($resolvidas[$ancestor])) {
                    $fila[] = $ancestor;

                    continue;
                }

                if (! isset($duracoes[$ancestor])) {
                    continue;
                }

                $tipoAnc = $tipoDias[$ancestor] ?? TipoDiasTemplate::CORRIDOS;
                $durAnc = $duracoes[$ancestor] ?? 0;

                $deriveInicioDoFim = function (CarbonImmutable $ancFim) use ($durAnc, $tipoAnc): CarbonImmutable {
                    return $durAnc > 0
                        ? $this->adicionarDias($ancFim, -($durAnc - 1), $tipoAnc)
                        : $ancFim;
                };

                $ancInicio = match ($d->gatilho) {
                    GatilhoTemplateFase::INICIO_ANTERIOR
                        => $this->adicionarDias($currentDatas['inicio'], -$d->gap, $tipoAnc),
                    GatilhoTemplateFase::FIM_ANTERIOR
                        => $deriveInicioDoFim($this->adicionarDias($currentDatas['inicio'], -($d->gap + 1), $tipoAnc)),
                    GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA
                        => $deriveInicioDoFim($this->adicionarDias($currentDatas['inicio'], -$d->gap, $tipoAnc)),
                    GatilhoTemplateFase::FIM_JUNTO
                        => $deriveInicioDoFim($this->adicionarDias($currentDatas['fim'], -$d->gap, $tipoAnc)),
                    GatilhoTemplateFase::FIM_ANTES_INICIO
                        => $this->adicionarDias($currentDatas['fim'], -$d->gap, $tipoAnc),
                };

                $ancFimCalc = $durAnc > 0
                    ? $this->adicionarDias($ancInicio, $durAnc - 1, $tipoAnc)
                    : $ancInicio;

                $resolvidas[$ancestor] = ['inicio' => $ancInicio, 'fim' => $ancFimCalc];

                $this->cascataForwardInterno($ancestor, $duracoes, $tipoDias, $deps, $resolvidas);

                $fila[] = $ancestor;
            }
        }
    }

    // =====================================================================
    // Núcleo do cálculo
    // =====================================================================

    /**
     * Calcula as datas previstas para todas as fases visíveis do template.
     *
     * @param  Collection<int, CronogramaTemplateFase>  $fasesTemplate
     * @return array<string, array{inicio: CarbonImmutable, fim: CarbonImmutable}>
     */
    private function calcularDatas(Collection $fasesTemplate, CronogramaTemplateFase $ancoraFase, CarbonImmutable $dataAncora): array
    {
        $fasesPorValue = [];
        $duracoes = [];
        $tipoDias = [];
        $deps = [];

        $elasticas = [];

        foreach ($fasesTemplate as $tf) {
            $v = $tf->fase->value;
            $fasesPorValue[$v] = $tf;
            $duracoes[$v] = (int) $tf->duracao_dias;
            $tipoDias[$v] = $tf->tipo_dias ?? TipoDiasTemplate::CORRIDOS;
            $elasticas[$v] = (bool) $tf->regra_elastica;
            $deps[$v] = [];
            foreach ($tf->dependencias as $d) {
                if ($d->depende_de_item_id && $d->dependeDeItem) {
                    $itemFase = $fasesTemplate->firstWhere('id', $d->dependeDeItem->cronograma_template_fase_id);
                    if (! $itemFase) {
                        continue;
                    }
                    $depValue = $itemFase->fase->value;
                } else {
                    $depValue = $d->depende_de_fase instanceof FaseCronograma
                        ? $d->depende_de_fase->value
                        : (string) $d->depende_de_fase;
                }
                $deps[$v][] = (object) [
                    'de' => $depValue,
                    'gatilho' => $this->toGatilhoEnum($d->gatilho),
                    'gap' => (int) $d->gap_dias,
                ];
            }
        }

        // Filtra deps apontando para fases ocultas (não presentes em $fasesPorValue).
        foreach ($deps as $v => $lista) {
            $deps[$v] = array_values(array_filter($lista, fn ($d) => isset($fasesPorValue[$d->de])));
        }

        return $this->calcularDatasFromMaps($ancoraFase->fase->value, $dataAncora, $duracoes, $tipoDias, $deps, null, $elasticas);
    }

    /**
     * Núcleo do cálculo híbrido, reutilizável tanto pelo aplicar() do template
     * quanto pelo recalcularFaseEDependentes() da obra. Recebe os mapas já extraídos
     * (duracoes, tipoDias, deps) indexados por fase-value.
     *
     * @param  array<string,int>  $duracoes
     * @param  array<string,TipoDiasTemplate>  $tipoDias
     * @param  array<string, array<int, object>>  $deps
     * @return array<string, array{inicio: CarbonImmutable, fim: CarbonImmutable}>
     */
    public function calcularDatasFromMaps(
        string $ancoraValue,
        CarbonImmutable $dataAncora,
        array $duracoes,
        array $tipoDias,
        array $deps,
        ?CarbonImmutable $dataAncoraFim = null,
        ?array $elasticas = null,
    ): array {
        $elasticas = $elasticas ?? [];
        $this->detectarCiclos($deps);

        $fasesPorValue = $duracoes; // só precisamos das chaves como "existentes"

        // Conjunto FORWARD: fases alcançáveis seguindo arestas X → Y (Y depende de X), a partir da âncora.
        $forwardSet = $this->bfsForward($ancoraValue, $deps);
        $backwardSet = [];
        foreach ($fasesPorValue as $v => $_) {
            if (! isset($forwardSet[$v])) {
                $backwardSet[$v] = true;
            }
        }

        $resolvidas = [];
        $resolvidas[$ancoraValue] = ['inicio' => $dataAncora, 'fim' => $dataAncoraFim ?? $dataAncora];

        // --- Forward pass ---
        $forwardOrder = $this->ordenacaoTopologica(array_keys($forwardSet), $deps);
        foreach ($forwardOrder as $v) {
            if ($v === $ancoraValue) {
                continue;
            }
            [$inicio, $fim] = $this->calcularFaseForward(
                $v,
                $deps[$v] ?? [],
                $duracoes[$v],
                $tipoDias[$v],
                $resolvidas,
                (bool) ($elasticas[$v] ?? false),
            );
            if (! $inicio) {
                // Sem deps resolvidas: encosta na âncora como fallback.
                $inicio = $dataAncora;
                $fim = $duracoes[$v] > 0
                    ? $this->adicionarDias($inicio, $duracoes[$v] - 1, $tipoDias[$v])
                    : $inicio;
            }
            $resolvidas[$v] = ['inicio' => $inicio, 'fim' => $fim];
        }

        // --- Backward pass ---
        // Estratégia: computar o subgrafo ascendente em FORWARD (como se fosse um
        // mini-cronograma independente, começando num dia virtual) e depois deslocar
        // o bloco inteiro para que o maior fim caia em (ancora - 1). Isso reproduz
        // exatamente o comportamento da planilha PMO, onde Início de Projeto é
        // "primeira fase" e tudo que vem antes da âncora é calculado a partir dela.
        //
        // Deps que cruzam para o conjunto forward (ex.: PIN Suframa → Implantação)
        // usam datas já resolvidas em $resolvidas e não recebem o shift global.
        if (! empty($backwardSet)) {
            $virtualInicio = CarbonImmutable::today();
            $virtualResolved = [];
            $hybridAnchored = [];

            // Ordem topológica (forward) restrita ao backwardSet.
            $backwardOrder = $this->ordenacaoTopologica(array_keys($backwardSet), $deps);
            foreach ($backwardOrder as $v) {
                $usaForward = false;
                foreach ($deps[$v] ?? [] as $d) {
                    if (isset($resolvidas[$d->de]) && ! isset($virtualResolved[$d->de])) {
                        $usaForward = true;
                        break;
                    }
                }

                // Combina backward já resolvido + forward. Permite que fases backward
                // (incl. elásticas como Recebimento Arquitetura/Complementares) usem
                // datas do forward (ex.: dep cruzada via FIM_ANTES_INICIO).
                $combined = $virtualResolved + $resolvidas;

                [$inicio, $fim] = $this->calcularFaseForward(
                    $v,
                    $deps[$v] ?? [],
                    $duracoes[$v],
                    $tipoDias[$v],
                    $combined,
                    (bool) ($elasticas[$v] ?? false),
                );

                if (! $inicio) {
                    // Raiz do subgrafo ascendente (nenhuma dep resolvida): posiciona em virtualInicio.
                    $inicio = $virtualInicio;
                    $dur = $duracoes[$v];
                    $fim = $dur > 0
                        ? $this->adicionarDias($inicio, $dur - 1, $tipoDias[$v])
                        : $inicio;
                }

                if ($usaForward) {
                    $hybridAnchored[$v] = true;
                }

                $virtualResolved[$v] = ['inicio' => $inicio, 'fim' => $fim];
            }

            $noShift = $hybridAnchored;
            foreach ($backwardOrder as $v) {
                if ($hybridAnchored[$v] ?? false) {
                    $noShift[$v] = true;
                }
                foreach ($deps[$v] ?? [] as $d) {
                    if (isset($backwardSet[$d->de]) && ($noShift[$d->de] ?? false)) {
                        $noShift[$v] = true;
                    }
                }
            }

            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($backwardOrder as $v) {
                    foreach ($deps[$v] ?? [] as $d) {
                        if (! isset($backwardSet[$d->de])) {
                            continue;
                        }
                        if (($noShift[$d->de] ?? false) && ! ($noShift[$v] ?? false)) {
                            $noShift[$v] = true;
                            $changed = true;
                        }
                    }
                }
            }

            // Shift = mínimo necessário para que cada dep direta da âncora caia
            // exatamente no ponto previsto pelo seu gatilho (ex.: Orçamentos termina
            // 1d antes da Posse). Cadeias paralelas mais longas (ex.: Prazo Legal)
            // ficam soltas no virtual — não reposicionam o bloco. Sem deps diretas,
            // cai no fallback (maior fim do bloco shiftável).
            $shiftDays = null;
            foreach ($deps[$ancoraValue] ?? [] as $d) {
                if (! isset($virtualResolved[$d->de])) {
                    continue;
                }
                $candidatoInicioAncora = $this->resolverInicioPorGatilho(
                    $d->gatilho,
                    $virtualResolved[$d->de]['inicio'],
                    $virtualResolved[$d->de]['fim'],
                    (int) $d->gap,
                    $duracoes[$ancoraValue] ?? 0,
                    $tipoDias[$ancoraValue] ?? TipoDiasTemplate::CORRIDOS,
                );
                $candidato = (int) round(
                    ($dataAncora->getTimestamp() - $candidatoInicioAncora->getTimestamp()) / 86400
                );
                if ($shiftDays === null || $candidato > $shiftDays) {
                    $shiftDays = $candidato;
                }
            }

            if ($shiftDays === null) {
                $maxFim = null;
                foreach ($virtualResolved as $v => $datas) {
                    if ($noShift[$v] ?? false) {
                        continue;
                    }
                    if ($maxFim === null || $datas['fim']->greaterThan($maxFim)) {
                        $maxFim = $datas['fim'];
                    }
                }
                if ($maxFim !== null) {
                    $targetEnd = $dataAncora->subDay();
                    $shiftDays = (int) round(
                        ($targetEnd->getTimestamp() - $maxFim->getTimestamp()) / 86400
                    );
                }
            }

            if ($shiftDays !== null) {
                foreach ($virtualResolved as $v => $datas) {
                    if ($noShift[$v] ?? false) {
                        $resolvidas[$v] = $datas;
                    } else {
                        $resolvidas[$v] = [
                            'inicio' => $datas['inicio']->addDays($shiftDays),
                            'fim' => $datas['fim']->addDays($shiftDays),
                        ];
                    }
                }
            } else {
                foreach ($virtualResolved as $v => $datas) {
                    $resolvidas[$v] = $datas;
                }
            }
        }

        // --- Re-forward pass ---
        // Reprocessa todo o forwardSet em ordem topológica agora que o backward
        // está resolvido. Fases forward com deps cruzando pro backward (ex.:
        // OBRAS depende de PRAZO_LEGAL) finalmente enxergam a segunda dep, e a
        // mudança propaga em cascata para descendentes (IMPLANTACAO, INAUGURACAO).
        foreach ($forwardOrder as $v) {
            if ($v === $ancoraValue) {
                continue;
            }
            [$inicio, $fim] = $this->calcularFaseForward(
                $v,
                $deps[$v] ?? [],
                $duracoes[$v],
                $tipoDias[$v],
                $resolvidas,
                (bool) ($elasticas[$v] ?? false),
            );
            if ($inicio) {
                $resolvidas[$v] = ['inicio' => $inicio, 'fim' => $fim];
            }
        }

        // --- Elastic resolve pass ---
        // Fases elásticas com FIM_ANTES_INICIO podem precisar do início de uma
        // fase que só é resolvida depois delas (dep "soft" via auto-amarração).
        // Reprocessamos elásticas iterativamente até estabilizar.
        $maxIter = 5;
        do {
            $mudou = false;
            foreach ($duracoes as $v => $_) {
                if ($v === $ancoraValue) {
                    continue;
                }
                if (! ($elasticas[$v] ?? false)) {
                    continue;
                }

                [$inicio, $fim] = $this->calcularFaseForward(
                    $v,
                    $deps[$v] ?? [],
                    $duracoes[$v],
                    $tipoDias[$v],
                    $resolvidas,
                    true,
                );
                if (! $inicio) {
                    continue;
                }

                $atual = $resolvidas[$v] ?? null;
                if (! $atual
                    || ! $atual['inicio']->equalTo($inicio)
                    || ! $atual['fim']->equalTo($fim)) {
                    $resolvidas[$v] = ['inicio' => $inicio, 'fim' => $fim];
                    $mudou = true;
                }
            }
        } while ($mudou && --$maxIter > 0);

        return $resolvidas;
    }

    /**
     * Calcula início/fim de uma fase no sentido forward a partir de suas dependências já resolvidas.
     *
     * @param  array<int, object>  $depsDaFase
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function calcularFaseForward(
        string $chave,
        array $depsDaFase,
        int $duracao,
        TipoDiasTemplate $tipo,
        array $resolvidas,
        bool $elastica = false,
    ): array {
        if ($elastica) {
            $candidatosInicio = [];
            $candidatosFim = [];

            foreach ($depsDaFase as $d) {
                if (! isset($resolvidas[$d->de])) {
                    continue;
                }
                $iniDep = $resolvidas[$d->de]['inicio'];
                $fimDep = $resolvidas[$d->de]['fim'];

                if ($d->gatilho === GatilhoTemplateFase::FIM_JUNTO || $d->gatilho === GatilhoTemplateFase::FIM_ANTES_INICIO) {
                    $cf = $this->resolverFimPorGatilho($d->gatilho, $iniDep, $fimDep, (int) $d->gap, $tipo);
                    if ($cf !== null) {
                        $candidatosFim[] = $cf;
                    }
                } else {
                    $candidatosInicio[] = $this->resolverInicioPorGatilho(
                        $d->gatilho,
                        $iniDep,
                        $fimDep,
                        (int) $d->gap,
                        0,
                        $tipo,
                    );
                }
            }

            if (empty($candidatosInicio) || empty($candidatosFim)) {
                return [null, null];
            }

            return [$this->maxCarbon($candidatosInicio), $this->maxCarbon($candidatosFim)];
        }

        $candidatos = [];
        foreach ($depsDaFase as $d) {
            if (! isset($resolvidas[$d->de])) {
                continue;
            }
            $candidatos[] = $this->resolverInicioPorGatilho(
                $d->gatilho,
                $resolvidas[$d->de]['inicio'],
                $resolvidas[$d->de]['fim'],
                (int) $d->gap,
                $duracao,
                $tipo,
            );
        }

        if (empty($candidatos)) {
            return [null, null];
        }

        $inicio = $this->maxCarbon($candidatos);
        $fim = $duracao > 0
            ? $this->adicionarDias($inicio, $duracao - 1, $tipo)
            : $inicio;

        return [$inicio, $fim];
    }

    private function resolverFimPorGatilho(
        GatilhoTemplateFase $gatilho,
        CarbonImmutable $inicioDependencia,
        CarbonImmutable $fimDependencia,
        int $gap,
        TipoDiasTemplate $tipo
    ): ?CarbonImmutable {
        return match ($gatilho) {
            GatilhoTemplateFase::FIM_JUNTO => $this->adicionarDias($fimDependencia, $gap, $tipo),
            GatilhoTemplateFase::FIM_ANTES_INICIO => $this->adicionarDias($inicioDependencia, $gap, $tipo),
            default => null,
        };
    }

    private function resolverInicioPorGatilho(
        GatilhoTemplateFase $gatilho,
        CarbonImmutable $inicioDependencia,
        CarbonImmutable $fimDependencia,
        int $gap,
        int $duracao,
        TipoDiasTemplate $tipo
    ): CarbonImmutable {
        return match ($gatilho) {
            GatilhoTemplateFase::INICIO_ANTERIOR => $this->adicionarDias($inicioDependencia, $gap, $tipo),
            GatilhoTemplateFase::FIM_ANTERIOR => $this->adicionarDias($fimDependencia, $gap + 1, $tipo),
            GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA => $this->adicionarDias($fimDependencia, $gap, $tipo),
            GatilhoTemplateFase::FIM_JUNTO => $this->adicionarDias($fimDependencia, $gap - max(0, $duracao - 1), $tipo),
            GatilhoTemplateFase::FIM_ANTES_INICIO => $this->adicionarDias($inicioDependencia, $gap - max(0, $duracao - 1), $tipo),
        };
    }

    /**
     * Extrai as regras efetivas (já resolvendo override local OR template) de cada fase
     * visível da obra, devolvendo arrays indexados por fase-value.
     *
     * @return array{0: array<string,int>, 1: array<string,TipoDiasTemplate>, 2: array<string, array<int, object>>, 3: array<string,bool>}
     */
    public function extrairRegrasEfetivas(Collection $fasesObra): array
    {
        $duracoes = [];
        $tipoDias = [];
        $deps = [];
        $elasticas = [];

        foreach ($fasesObra as $chave => $fase) {
            $regra = $fase->regraEfetiva();
            $duracoes[$chave] = (int) $regra->duracao_dias;
            $tipoDias[$chave] = $this->toTipoDiasEnum($regra->tipo_dias);
            $elasticas[$chave] = (bool) $regra->elastica;
            $deps[$chave] = [];

            foreach ($regra->dependencias as $d) {
                if (! empty($d->depende_de_item_id)) {
                    $item = $d->dependeDeItem;
                    if (! $item) {
                        continue;
                    }
                    // Proxy: usa a fase pai do item como âncora de data
                    $depValue = $item instanceof CronogramaTemplateFaseItem
                        ? $item->templateFase?->fase?->value
                        : $item->fase?->fase?->value;
                } else {
                    $depValue = $d->depende_de_fase instanceof FaseCronograma
                        ? $d->depende_de_fase->value
                        : (string) $d->depende_de_fase;
                }
                if (! $depValue || ! isset($fasesObra[$depValue])) {
                    continue; // dep aponta para fase oculta ou item sem fase — ignora
                }
                $deps[$chave][] = (object) [
                    'de' => $depValue,
                    'gatilho' => $this->toGatilhoEnum($d->gatilho),
                    'gap' => (int) $d->gap_dias,
                ];
            }
        }

        return [$duracoes, $tipoDias, $deps, $elasticas];
    }

    // =====================================================================
    // Grafo: BFS, ordenação topológica, detecção de ciclos
    // =====================================================================

    /**
     * BFS seguindo a direção "X tem sucessor Y se Y depende de X".
     * Retorna conjunto (associativo) com todas as fases alcançáveis a partir de $raiz.
     *
     * @param  array<string, array<int, object>>  $deps
     * @return array<string, true>
     */
    private function bfsForward(string $raiz, array $deps): array
    {
        $visitados = [$raiz => true];
        $fila = [$raiz];

        while (! empty($fila)) {
            $atual = array_shift($fila);
            foreach ($deps as $v => $listaDeps) {
                if (isset($visitados[$v])) {
                    continue;
                }
                foreach ($listaDeps as $d) {
                    if ($d->de === $atual) {
                        $visitados[$v] = true;
                        $fila[] = $v;
                        break;
                    }
                }
            }
        }

        return $visitados;
    }

    /**
     * Ordenação topológica de Kahn restrita a um subconjunto de fases.
     *
     * @param  array<int, string>  $nos
     * @param  array<string, array<int, object>>  $deps
     * @return array<int, string>
     */
    private function ordenacaoTopologica(array $nos, array $deps): array
    {
        $nosSet = array_flip($nos);
        $grauEntrada = array_fill_keys($nos, 0);

        // FIM_ANTES_INICIO é tratada como aresta soft (auto-amarração de fase
        // elástica) e não conta para topologia — o cálculo já garante que a
        // dep esteja resolvida antes via outra rota.
        foreach ($nos as $v) {
            foreach ($deps[$v] ?? [] as $d) {
                if ($d->gatilho === GatilhoTemplateFase::FIM_ANTES_INICIO) {
                    continue;
                }
                if (isset($nosSet[$d->de])) {
                    $grauEntrada[$v]++;
                }
            }
        }

        $fila = [];
        foreach ($grauEntrada as $v => $g) {
            if ($g === 0) {
                $fila[] = $v;
            }
        }

        $ordem = [];
        while (! empty($fila)) {
            $atual = array_shift($fila);
            $ordem[] = $atual;
            foreach ($nos as $v) {
                foreach ($deps[$v] ?? [] as $d) {
                    if ($d->gatilho === GatilhoTemplateFase::FIM_ANTES_INICIO) {
                        continue;
                    }
                    if ($d->de === $atual && isset($grauEntrada[$v])) {
                        $grauEntrada[$v]--;
                        if ($grauEntrada[$v] === 0) {
                            $fila[] = $v;
                        }
                    }
                }
            }
        }

        return $ordem;
    }

    /**
     * Ordenação topológica reversa: processa primeiro nós sem sucessores.
     *
     * @param  array<int, string>  $nos
     * @param  array<string, array<int, object>>  $sucessores
     * @return array<int, string>
     */
    private function ordenacaoTopologicaReversa(array $nos, array $sucessores): array
    {
        $nosSet = array_flip($nos);
        $grauSaida = array_fill_keys($nos, 0);

        foreach ($nos as $v) {
            foreach ($sucessores[$v] ?? [] as $s) {
                if (isset($nosSet[$s->sucessor])) {
                    $grauSaida[$v]++;
                }
            }
        }

        $fila = [];
        foreach ($grauSaida as $v => $g) {
            if ($g === 0) {
                $fila[] = $v;
            }
        }

        $ordem = [];
        while (! empty($fila)) {
            $atual = array_shift($fila);
            $ordem[] = $atual;
            foreach ($nos as $v) {
                foreach ($sucessores[$v] ?? [] as $s) {
                    if ($s->sucessor === $atual && isset($grauSaida[$v])) {
                        $grauSaida[$v]--;
                        if ($grauSaida[$v] === 0) {
                            $fila[] = $v;
                        }
                    }
                }
            }
        }

        return $ordem;
    }

    private function detectarCiclos(array $deps): void
    {
        $estado = []; // 0=não visitado, 1=em progresso, 2=concluído
        $nos = array_keys($deps);

        // FIM_ANTES_INICIO é uma "auto-amarração" de fase elástica passiva
        // (B.fim = A.inicio + gap). Ela NÃO impõe ordem topológica forte:
        // matematicamente, B é resolvida APÓS A no cálculo, e qualquer outra
        // fase que dependa de B normalmente já estabelece a aresta forte.
        // Ignoramos essa aresta na detecção de ciclo para permitir desenhos
        // como Briefing ⊃ Fase 1 (FIM_ANTERIOR) + Fase 1 ⊃ Briefing
        // (FIM_ANTES_INICIO), que são consistentes na prática.
        $visitar = function (string $no) use (&$visitar, &$estado, $deps) {
            if (($estado[$no] ?? 0) === 1) {
                throw new InvalidArgumentException("Ciclo detectado no grafo de dependências envolvendo a fase '{$no}'.");
            }
            if (($estado[$no] ?? 0) === 2) {
                return;
            }
            $estado[$no] = 1;
            foreach ($deps[$no] ?? [] as $d) {
                if ($d->gatilho === GatilhoTemplateFase::FIM_ANTES_INICIO) {
                    continue;
                }
                if (isset($deps[$d->de])) {
                    $visitar($d->de);
                }
            }
            $estado[$no] = 2;
        };

        foreach ($nos as $no) {
            $visitar($no);
        }
    }

    // =====================================================================
    // Helpers de data
    // =====================================================================

    private function adicionarDias(CarbonImmutable $data, int $dias, TipoDiasTemplate $tipo): CarbonImmutable
    {
        if ($tipo === TipoDiasTemplate::CORRIDOS) {
            return $data->addDays($dias);
        }

        if ($dias === 0) {
            return $data;
        }

        $sentido = $dias > 0 ? 1 : -1;
        $restante = abs($dias);
        $cursor = $data;

        while ($restante > 0) {
            $cursor = $cursor->addDays($sentido);
            if (! $cursor->isWeekend()) {
                $restante--;
            }
        }

        return $cursor;
    }

    /**
     * @param  array<int, CarbonImmutable>  $datas
     */
    private function maxCarbon(array $datas): CarbonImmutable
    {
        $max = $datas[0];
        foreach ($datas as $d) {
            if ($d->greaterThan($max)) {
                $max = $d;
            }
        }

        return $max;
    }

    /**
     * @param  array<int, CarbonImmutable>  $datas
     */
    private function minCarbon(array $datas): CarbonImmutable
    {
        $min = $datas[0];
        foreach ($datas as $d) {
            if ($d->lessThan($min)) {
                $min = $d;
            }
        }

        return $min;
    }

    private function toTipoDiasEnum(mixed $valor): TipoDiasTemplate
    {
        if ($valor instanceof TipoDiasTemplate) {
            return $valor;
        }

        return TipoDiasTemplate::tryFrom((string) $valor) ?? TipoDiasTemplate::CORRIDOS;
    }

    private function toGatilhoEnum(mixed $valor): GatilhoTemplateFase
    {
        if ($valor instanceof GatilhoTemplateFase) {
            return $valor;
        }

        return GatilhoTemplateFase::tryFrom((string) $valor) ?? GatilhoTemplateFase::FIM_ANTERIOR;
    }

    /**
     * Sincroniza as datas previstas do cronograma com os campos correspondentes
     * do Projeto e da Obra, garantindo que o planejamento alimente os modelos.
     */
    public function sincronizarDatasComProjeto(Projeto $projeto): void
    {
        $fases = CronogramaFase::where('projeto_id', $projeto->id)
            ->whereNotNull('data_prevista_inicio')
            ->get()
            ->keyBy(fn (CronogramaFase $f) => $f->fase->value);

        if ($fases->isEmpty()) {
            return;
        }

        $projeto->loadMissing('obras');
        $obra = $projeto->obras->first();

        $obraUpdates = [];
        $projetoUpdates = [];

        $mapeamento = [
            'inicio_projeto' => ['projeto' => ['cad_plan_inicio' => 'inicio', 'cad_plan_fim' => 'fim']],
            'assinatura_contrato' => ['projeto' => ['data_assinatura_contrato' => 'inicio']],
            'visita_tecnica' => ['projeto' => ['vis_plan_inicio' => 'inicio', 'vis_plan_fim' => 'fim']],
            'executivo' => ['projeto' => ['proj_plan_ini' => 'inicio', 'proj_plan_fim' => 'fim']],
            'orcamentos' => ['projeto' => ['orca_planejado_ini' => 'inicio', 'orca_planejado_fim' => 'fim']],
            'posse' => ['projeto' => ['data_posse' => 'inicio']],
            'obras' => ['obra' => ['inicio' => 'inicio', 'fim' => 'fim']],
            'implantacao' => ['projeto' => ['imp_inicio' => 'inicio', 'imp_fim' => 'fim']],
            'inauguracao' => ['projeto' => ['inauguracao' => 'inicio']],
        ];

        foreach ($mapeamento as $faseKey => $alvos) {
            $fase = $fases[$faseKey] ?? null;
            if (! $fase) {
                continue;
            }

            foreach ($alvos as $modelo => $campos) {
                foreach ($campos as $campo => $tipo) {
                    $valor = $tipo === 'inicio'
                        ? $fase->data_prevista_inicio?->toDateString()
                        : $fase->data_prevista_fim?->toDateString();

                    if (! $valor) {
                        continue;
                    }

                    if ($modelo === 'obra') {
                        $obraUpdates[$campo] = $valor;
                    } else {
                        $projetoUpdates[$campo] = $valor;
                    }
                }
            }
        }

        if (! empty($obraUpdates) && $obra) {
            $obra->updateQuietly($obraUpdates);
        }

        if (! empty($projetoUpdates)) {
            $projeto->updateQuietly($projetoUpdates);
        }
    }
}
