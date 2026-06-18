<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detalhes')
                ->schema([
                    Grid::make(4)->schema([

                        TextEntry::make('setor.setor')
                            ->label('Setor'),

                        TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        // 'nao_iniciada' => 'Não iniciada',
                        'pendente' => 'Pendente',
                        'em_andamento' => 'Em andamento',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                        default => (string) $state,
                    })
                    ->color(function ($state, $record) {
                        $isOverdue =
                            $record->termino_programado
                            && $record->status !== 'concluida'
                            && $record->status !== 'cancelada'
                            && $record->termino_programado->lt(today());

                        if ($isOverdue) {
                            return 'danger';
                        }

                        return match ($state) {
                            // 'nao_iniciada' => 'gray',
                            'pendente' => 'warning',
                            'em_andamento' => 'info',
                            'concluida' => 'success',
                            'cancelada' => 'gray',
                            default => 'gray',
                        };
                    }),

                        TextEntry::make('title')
                            ->label('Nome da Tarefa')
                            ->columnSpan(3),

                        TextEntry::make('description')
                            ->label('Informações da Tarefa')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->markdown()
                            ->prose(),

                        TextEntry::make('category.name')
                            ->label('Categoria da Tarefa')
                            ->placeholder('-'),

                        TextEntry::make('sigla')
                            ->label('Sigla')
                            ->placeholder('-'),

                        TextEntry::make('marca.nome')
                            ->label('Unidade')
                            ->placeholder('-'),

                        TextEntry::make('solicitante.name')
                            ->label('Solicitante')
                            ->placeholder('-'),

                        TextEntry::make('responsavel.name')
                            ->label('Responsável')
                            ->placeholder('-'),

                    ]),

                ]),

            ViewComponent::make('filament.resources.tasks.comentarios')
                ->columnSpanFull(),

            Section::make('Datas')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('inicio')
                            ->label('Data de início')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('prazo')
                            ->label('Prazo (dias)')
                            ->numeric()
                            ->placeholder('-'),

                        TextEntry::make('dias_corridos')
                            ->label('Contagem do prazo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Dias corridos' : 'Dias úteis')
                            ->color(fn ($state) => $state ? 'warning' : 'success'),

                        TextEntry::make('termino_programado')
                            ->label('Data de término programado')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('data_entrega')
                            ->label('Data de entrega')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),
                ]),
        ]);
    }
}
