<?php

namespace App\Observers;

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Enums\StatusLiberacaoPosse;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CronogramaFaseObserver
{
    private const STATUS_INICIAIS = [
        StatusCronograma::NAO_INICIADO,
        StatusCronograma::NA,
        StatusCronograma::INDEFINIDO,
        StatusCronograma::NAO_REALIZADO,
    ];

    private const STATUS_FINAIS = [
        StatusCronograma::CONCLUIDO,
        StatusCronograma::REALIZADO,
        StatusCronograma::ASSINADO,
        StatusCronograma::FINALIZADO,
        StatusCronograma::PRONTO,
    ];

    /**
     * Fases que ficam travadas após a assinatura do contrato.
     * Conforme reunião 09/05: depois que o contrato é ASSINADO, as datas
     * de Obras, Implantação e Inauguração viram compromisso e não devem
     * mais ser recalculadas automaticamente.
     */
    private const FASES_TRAVADAS_POS_CONTRATO = [
        FaseCronograma::OBRAS,
        FaseCronograma::IMPLANTACAO,
        FaseCronograma::INAUGURACAO,
    ];

    /**
     * Fases críticas cujo replanejamento por Engenharia gera alerta para o PMO.
     * Conforme reunião 08/05: Visita Técnica e duração de Obras. (Energia SF/PP
     * passaram a ser subitens de Obras na reunião 11/05; Posse vive no Projeto,
     * tratada no ProjetoSyncObserver.)
     */
    private const FASES_CRITICAS_ENGENHARIA = [
        'obras',
        'visita_tecnica',
    ];

    /**
     * Roles que disparam o alerta quando alteram fases críticas.
     */
    private const ROLES_QUE_DISPARAM = ['Engenharia'];

    /**
     * Roles que recebem a notificação in-app.
     */
    private const ROLES_QUE_RECEBEM = ['PMO', 'Planejamento Estratégico'];

    public function updating(CronogramaFase $fase): bool|null
    {
        // Bloqueia início de OBRAS quando pré-requisitos não foram atendidos.
        if ($this->bloquearObrasSemDependencias($fase) === false) {
            return false;
        }

        if (! $fase->isDirty('status')) {
            return null;
        }

        $novoStatus = $fase->status;

        // Status do contrato (NEGOCIACAO/MINUTA/EM_ASSINATURA/ASSINADO) propaga
        // porcentagem proporcional automaticamente: 0/25/50/100. Aplicável
        // apenas à fase ASSINATURA_CONTRATO.
        if ($fase->fase === FaseCronograma::ASSINATURA_CONTRATO) {
            $percentualContrato = $novoStatus?->percentualConclusao();
            if ($percentualContrato !== null) {
                $fase->percentual_conclusao = $percentualContrato;
            }
        }

        // Status final → percentual 100% + data_realizada_fim
        if (in_array($novoStatus, self::STATUS_FINAIS, true)) {
            $fase->percentual_conclusao = 100;
            if (! $fase->data_realizada_inicio) {
                $fase->data_realizada_inicio = $fase->data_realizada_fim ?? now();
            }
            if (! $fase->data_realizada_fim) {
                $fase->data_realizada_fim = $fase->data_realizada_inicio ?? now();
            }

            return null;
        }

        // Status inicial → limpa datas reais
        if (in_array($novoStatus, self::STATUS_INICIAIS, true)) {
            $fase->data_realizada_inicio = null;
            $fase->data_realizada_fim = null;
            if ($fase->itens()->doesntExist()) {
                $fase->percentual_conclusao = 0;
            }

            return null;
        }

        // Em andamento (qualquer outro status) → marca início, limpa fim
        if (! $fase->data_realizada_inicio) {
            $fase->data_realizada_inicio = now();
        }
        $fase->data_realizada_fim = null;

        return null;
    }

    /**
     * Após salvar:
     * 1. Se ASSINATURA_CONTRATO virou ASSINADO, trava as fases finais com
     *    bloqueada_pos_contrato=true (PR 6).
     * 2. Se usuário com role Engenharia alterou data/duração de fase crítica,
     *    dispara notificação warning para PMO + Planejamento (PR 9).
     */
    public function saved(CronogramaFase $fase): void
    {
        $this->travarFasesPosAssinatura($fase);
        $this->dispararAlertaEngenharia($fase);
    }

    /**
     * Bloqueia o início da fase OBRAS quando faltam pré-requisitos:
     *  - Orçamentos concluído (data_realizada_inicio != null)
     *  - Liberação de Posse → subitem "Engenharia" com status SIM
     *  - Liberação de Posse → subitem "Legalização" com status SIM
     *
     * Disparado apenas quando o usuário muda o status de OBRAS para um status
     * não-inicial (i.e. tentou iniciar/concluir a fase) ou preencheu
     * data_realizada_inicio. Retorna false para cancelar o update.
     */
    private function bloquearObrasSemDependencias(CronogramaFase $fase): ?bool
    {
        if ($fase->fase !== FaseCronograma::OBRAS) {
            return null;
        }

        $statusMudouParaIniciado = $fase->isDirty('status')
            && ! in_array($fase->status, [
                StatusCronograma::NAO_INICIADO,
                StatusCronograma::NA,
                StatusCronograma::INDEFINIDO,
                StatusCronograma::NAO_REALIZADO,
            ], true);

        $dataRealizadaInicioPreenchida = $fase->isDirty('data_realizada_inicio')
            && ! blank($fase->data_realizada_inicio);

        if (! $statusMudouParaIniciado && ! $dataRealizadaInicioPreenchida) {
            return null;
        }

        $pendencias = $this->checarPreRequisitosObras($fase);
        if (empty($pendencias)) {
            return null;
        }

        Notification::make()
            ->title('Não é possível iniciar OBRAS')
            ->body('Aguardando: '.implode(' · ', $pendencias))
            ->danger()
            ->persistent()
            ->send();

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function checarPreRequisitosObras(CronogramaFase $fase): array
    {
        $pendencias = [];

        $orcamentos = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->where('fase', FaseCronograma::ORCAMENTOS->value)
            ->first();

        if (! $orcamentos || blank($orcamentos->data_realizada_inicio)) {
            $pendencias[] = 'Orçamentos não iniciado';
        }

        $liberacao = CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->where('fase', FaseCronograma::LIBERACAO_POSSE->value)
            ->first();

        if ($liberacao) {
            $engenharia = $liberacao->itens()
                ->where('titulo', 'Engenharia')
                ->first();
            $legalizacao = $liberacao->itens()
                ->where('titulo', 'Legalização')
                ->first();

            if (! $engenharia || $engenharia->status_liberacao !== StatusLiberacaoPosse::SIM) {
                $pendencias[] = 'Engenharia (Liberação de Posse) não confirmada';
            }
            if (! $legalizacao || $legalizacao->status_liberacao !== StatusLiberacaoPosse::SIM) {
                $pendencias[] = 'Legalização (Liberação de Posse) não confirmada';
            }
        } else {
            $pendencias[] = 'Liberação de Posse não cadastrada';
        }

        return $pendencias;
    }

    private function travarFasesPosAssinatura(CronogramaFase $fase): void
    {
        if ($fase->fase !== FaseCronograma::ASSINATURA_CONTRATO) {
            return;
        }

        if (! $fase->wasChanged('status')) {
            return;
        }

        if ($fase->status !== StatusCronograma::ASSINADO) {
            return;
        }

        CronogramaFase::where('projeto_id', $fase->projeto_id)
            ->whereIn('fase', array_map(fn (FaseCronograma $f) => $f->value, self::FASES_TRAVADAS_POS_CONTRATO))
            ->where('bloqueada_pos_contrato', false)
            ->update(['bloqueada_pos_contrato' => true]);
    }

    private function dispararAlertaEngenharia(CronogramaFase $fase): void
    {
        $usuario = Auth::user();
        if (! $usuario instanceof User) {
            return;
        }

        if (! $usuario->hasAnyRole(self::ROLES_QUE_DISPARAM)) {
            return;
        }

        $faseValue = $fase->fase instanceof FaseCronograma ? $fase->fase->value : (string) $fase->fase;
        if (! in_array($faseValue, self::FASES_CRITICAS_ENGENHARIA, true)) {
            return;
        }

        $camposObservados = [
            'data_prevista_inicio',
            'data_prevista_fim',
            'regra_duracao_dias',
        ];
        $alterou = false;
        foreach ($camposObservados as $campo) {
            if ($fase->wasChanged($campo)) {
                $alterou = true;
                break;
            }
        }
        if (! $alterou) {
            return;
        }

        $destinatarios = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ROLES_QUE_RECEBEM))
            ->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        $projetoNome = $fase->projeto?->nome ?? 'Projeto #'.$fase->projeto_id;
        $faseLabel = $fase->fase instanceof FaseCronograma ? $fase->fase->label() : $faseValue;

        Notification::make()
            ->title('⚠️ Alerta de Engenharia')
            ->body("Engenharia ({$usuario->name}) alterou prazo de \"{$faseLabel}\" no projeto \"{$projetoNome}\".")
            ->icon('heroicon-o-wrench-screwdriver')
            ->warning()
            ->sendToDatabase($destinatarios);
    }
}
