<?php

namespace App\Filament\Resources\CapexSimulacaos\Tables;

use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use App\Models\CapexSimulacao;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CapexSimulacaosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'capex-simulacoes-table'])
            ->modifyQueryUsing(function (Builder $query): Builder {
                $lixeira = session('capex_simulacao_view', request()->query('lixeira', 'without'));

                return match ($lixeira) {
                    'only' => $query->onlyTrashed(),
                    default => $query->withoutTrashed(),
                };
            })
            ->columns([
                TextColumn::make('nome')
                    ->label('Unidade')
                    ->state(fn ($record) => $record->nome_exibicao),

                TextColumn::make('revisao')
                    ->label('Rev.')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->state(fn ($record) => $record->revisao_label),

                IconColumn::make('projeto_id')
                    ->label('Vinc.')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-link-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => filled($record->projeto_id) ? 'Vinculado a um projeto' : 'Não vinculado'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        1 => 'Aprovado',
                        2 => 'Reprovado',
                        default => 'Pendente',
                    })
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success',
                        2 => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('area_unidade')
                    ->label('Área')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),

                TextColumn::make('custo_total_estimado')
                    ->label('Custo Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'R$ '.number_format((float) $state, 2, ',', '.')),

                TextColumn::make('custo_por_m2')
                    ->label('Custo/m²')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'R$ '.number_format((float) $state, 2, ',', '.')),

                TextColumn::make('shell_custo')
                    ->label('Shell - Custo')
                    ->state(fn ($record) => $record->shellItem !== null
                        ? 'R$ '.number_format((float) $record->shellItem->custo_estimado, 2, ',', '.')
                        : '-'),

                TextColumn::make('shell_percentual')
                    ->label('Shell - %')
                    ->state(fn ($record) => $record->shellItem !== null
                        ? number_format((float) $record->shellItem->percentual, 2, ',', '.').'%'
                        : '-'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['shellItem', 'projeto']))
            ->defaultPaginationPageOption(25)
            ->defaultSort('updated_at', 'desc')
            ->headerActions([
                Action::make('lixeira')
                    ->label(fn () => 'Lixeira ('.CapexSimulacao::onlyTrashed()->count().')')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function () {
                        session(['capex_simulacao_view' => 'only']);

                        redirect(CapexSimulacaoResource::getUrl('index', [
                            'lixeira' => 'only',
                        ]));
                    }),

                Action::make('ativos')
                    ->label('Ver ativos')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function () {
                        session(['capex_simulacao_view' => 'without']);

                        redirect(CapexSimulacaoResource::getUrl('index', [
                            'lixeira' => 'without',
                        ]));
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([

                RestoreAction::make()
                    ->label('')
                    ->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),

                ForceDeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir definitivamente')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Excluir permanentemente')
                    ->modalDescription('Essa ação não pode ser desfeita.'),
                Action::make('alterar_status')
                    ->label('')
                    ->tooltip('Alterar status')
                    ->icon('heroicon-o-arrow-path')
                    ->iconButton()
                    ->color('gray')
                    ->modalHeading('Alterar status')
                    ->modalSubmitActionLabel('Salvar')
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                0 => 'Pendente',
                                1 => 'Aprovado',
                                2 => 'Reprovado',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->fillForm(fn ($record) => [
                        'status' => $record->status,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    })
                    ->successNotificationTitle('Status atualizado com sucesso'),

                Action::make('ver_comentario')
                    ->label('')
                    ->tooltip(fn ($record) => filled(trim((string) $record->comentario))
                        ? 'Ver comentário'
                        : 'Sem comentário'
                    )
                    ->icon(fn ($record) => filled(trim((string) $record->comentario))
                        ? 'heroicon-s-chat-bubble-left-ellipsis'
                        : 'heroicon-o-chat-bubble-left-ellipsis'
                    )
                    ->color(fn ($record) => filled(trim((string) $record->comentario)) ? 'info' : 'gray')
                    ->iconButton()
                    ->modalHeading('Comentário')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->form([
                        Textarea::make('comentario')
                            ->label('Comentário')
                            ->rows(6)
                            ->placeholder('Sem comentário')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->fillForm(fn ($record) => [
                        'comentario' => $record->comentario,
                    ]),
            ])->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('Confirmar exclusão')
                        ->modalDescription('Tem certeza que deseja excluir o(s) registro(s)?')
                        ->successNotificationTitle('Registro(s) excluído(s) com sucesso'),
                ]),
            ]);
    }
}
