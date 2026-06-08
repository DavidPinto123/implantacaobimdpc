<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Resources\ProjetoResource;
use Filament\Resources\Pages\ViewRecord;

class HistoricoProjetoPage extends ViewRecord
{
    protected static string $resource = ProjetoResource::class;

    public array $historico = [];

    public function mount($record): void
    {
        parent::mount($record);

        $fasesNomes = [
            1 => 'Prospecção',
            2 => 'Reunião de comitê',
            3 => 'Viabilidade',
            4 => 'Briefing e Layout',
            5 => 'Ordem de investimento',
            6 => 'Contrato',
            7 => 'Projetos de obra',
            8 => 'Orçamentos e equalização',
        ];

        $this->historico = $this->record->historicos
            ->map(function ($item) use ($fasesNomes) {
                $item->fase_nome = $fasesNomes[$item->fase] ?? 'Geral';

                return $item;
            })
            ->groupBy(fn ($item) => $item->fase_nome);
    }
}
