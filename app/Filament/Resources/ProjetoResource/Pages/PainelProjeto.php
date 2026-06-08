<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Resources\ProjetoResource;
use Carbon\Carbon;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PainelProjeto extends ViewRecord
{
    protected static string $resource = ProjetoResource::class;

    protected string $view = 'filament.resources.projeto-resource.pages.painel-projeto';

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $projeto = $this->record->loadMissing(['cidade', 'estado', 'usuarios', 'obras']);
        $obra = $projeto->obras->sortByDesc('id')->first();

        $statusLabels = ['Iniciado', 'Assinado', 'Em processo'];
        $statusAtual = Str::lower((string) ($projeto->status ?? ''));

        $statusAtivo = match (true) {
            Str::contains($statusAtual, 'obra'), Str::contains($statusAtual, 'processo') => 'Em processo',
            Str::contains($statusAtual, 'assinado'), Str::contains($statusAtual, 'contrat') => 'Assinado',
            default => 'Iniciado',
        };

        $timeline = [
            ['label' => 'Posse', 'value' => $this->formatDate($projeto->posse_data_posse ?? $projeto->data_posse)],
            ['label' => 'IO', 'value' => $this->formatDate($projeto->data_ass_contrato)],
            ['label' => 'EP', 'value' => $this->formatDate($projeto->entrega_projeto)],
            ['label' => 'Implantação', 'value' => $this->formatDate($projeto->imp_inicio)],
            ['label' => 'Inaug', 'value' => $this->formatDate($projeto->inauguracao)],
        ];

        $cronograma = [
            ['etapa' => 'Cadastro', 'plan' => $this->formatDate($projeto->cad_plan_inicio), 'real' => $this->formatDate($projeto->cad_rea_inicio)],
            ['etapa' => 'Visita tecnica', 'plan' => $this->formatDate($projeto->vis_plan_inicio), 'real' => $this->formatDate($projeto->vis_rea_inicio)],
            ['etapa' => 'Projetos', 'plan' => $this->formatDate($projeto->proj_plan_ini), 'real' => $this->formatDate($projeto->proj_real_ini)],
            ['etapa' => 'Orcamentos', 'plan' => $this->formatDate($projeto->orca_planejado_ini), 'real' => $this->formatDate($projeto->orca_real_ini)],
            ['etapa' => 'Implantacao', 'plan' => $this->formatDate($projeto->imp_inicio), 'real' => $this->formatDate($projeto->imp_fim)],
        ];

        $contratacaoPercent = $this->parsePercent($projeto->status_contrato);
        $obrasPercent = $this->mapObrasPercent((string) $projeto->status);
        $implantacaoPercent = min(100, max(0, (int) (($projeto->imp_mes ?? 0) * (100 / 12))));
        $docsPendente = blank($projeto->link_docs);
        $docsPercent = $docsPendente ? 0 : 100;
        $dashboardPercent = (int) round(($contratacaoPercent + $obrasPercent + $implantacaoPercent + $docsPercent) / 4);

        return [
            'projeto' => $projeto,
            'obra' => $obra,
            'statusLabels' => $statusLabels,
            'statusAtivo' => $statusAtivo,
            'timeline' => $timeline,
            'cronograma' => $cronograma,
            'squad' => $projeto->usuarios->take(8),
            'endereco' => $this->formatAddress($projeto),
            'contratoArquivo' => $this->resolveContratoPreview($projeto),
            'dashboardPercent' => $dashboardPercent,
            'kpis' => [
                'contratacao' => $contratacaoPercent,
                'obras' => $obrasPercent,
                'implantacao' => $implantacaoPercent,
                'documentacao' => $docsPendente ? 'PENDENCIA COMERCIAL' : 'CONCLUIDO',
                'docsPendente' => $docsPendente,
            ],
            'statusPosseIo' => [
                'Posse' => $projeto->legal_doc_posse ?: 'Termo de Posse',
                'Tipo' => $projeto->tipo ?: 'Obra',
                'Liberado Eng' => $projeto->posse_engenharia ?: 'Não',
                'Resp. Energia' => $projeto->posse_comentarios ?: 'DPC',
                'Aprovação de Projetos' => $projeto->ordem_status_aprov ?: 'Parcial',
                'Liberado Leg' => $projeto->posse_legalizacao ?: 'Não',
                'Documentos Pendentes' => $projeto->legal_status_consulta_prev ?: 'Habite-se | AVCB | Projeto Aprovado',
                'Prazo Legal' => filled($projeto->legal_prazo_legal) ? $projeto->legal_prazo_legal . ' dias' : '90 dias',
            ],
            'statusProjetos' => [
                'Liberado: Briefing' => filled($projeto->brief_real) ? 'Sim' : 'Não',
                'Start' => filled($projeto->proj_real_reuniao_start) ? 'Sim' : 'Não',
            ],
        ];
    }

    protected function parsePercent(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) min(100, max(0, (float) $value));
        }

        if (! is_string($value) || trim($value) === '') {
            return 0;
        }

        preg_match('/(\d+(?:[.,]\d+)?)/', $value, $matches);
        if (! isset($matches[1])) {
            return 0;
        }

        return (int) min(100, max(0, (float) str_replace(',', '.', $matches[1])));
    }

    protected function mapObrasPercent(string $status): int
    {
        $status = Str::lower(trim($status));

        return match (true) {
            $status === '' => 0,
            Str::contains($status, ['conclu', 'finaliz', 'inaugur']) => 100,
            Str::contains($status, ['process', 'obra']) => 50,
            Str::contains($status, ['inici', 'start']) => 25,
            default => 0,
        };
    }

    protected function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '--/--/----';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return '--/--/----';
        }
    }

    protected function formatAddress(mixed $projeto): string
    {
        $cidade = $projeto->cidade?->nome;
        $uf = $projeto->estado?->sigla;

        return collect([
            $projeto->rua,
            $projeto->numero,
            $projeto->bairro,
            trim(($cidade ? $cidade : '').($uf ? '/'.$uf : '')),
        ])->filter()->implode(' - ');
    }

    protected function resolveContratoPreview(mixed $projeto): ?string
    {
        $arquivo = Arr::first($projeto->anexo_contrato_assinado ?? []);

        if (! is_string($arquivo) || $arquivo === '') {
            return null;
        }

        return $arquivo;
    }
}
