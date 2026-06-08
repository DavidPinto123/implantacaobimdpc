<?php

namespace App\Filament\Tables\TableExcel\Actions;

use App\Filament\Tables\TableExcel\Support\TableExcelPreferences;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

final class ManageFrozenColumnsAction
{
    /**
     * @param  array<string, string>  $columns  Map of column name => label
     */
    public static function make(string $tableKey, array $columns, ?string $name = null): Action
    {
        return Action::make($name ?? 'manageFrozenColumns')
            ->label('Colunas congeladas')
            ->icon('heroicon-o-lock-closed')
            ->color('gray')
            ->modalHeading('Congelar colunas')
            ->modalDescription('Selecione quais colunas devem ficar fixas à esquerda durante a rolagem horizontal.')
            ->modalWidth('md')
            ->fillForm(function ($livewire) use ($tableKey): array {
                // Page mode (HasTableExcelPage) mantém a pref em $frozenColumns
                // hidratada do localStorage; Preset antigo lê de session.
                if (is_object($livewire) && property_exists($livewire, 'frozenColumns') && is_array($livewire->frozenColumns)) {
                    return ['columns' => array_values($livewire->frozenColumns)];
                }

                return ['columns' => (array) TableExcelPreferences::get($tableKey, 'frozen_columns', [])];
            })
            ->schema(fn (Schema $schema): Schema => $schema->components([
                CheckboxList::make('columns')
                    ->label('')
                    ->options($columns)
                    ->columns(1)
                    ->bulkToggleable()
                    ->selectAllAction(fn (Action $action): Action => $action->label('Selecionar tudo'))
                    ->deselectAllAction(fn (Action $action): Action => $action->label('Desmarcar tudo')),
            ]))
            ->action(function (array $data, $livewire) use ($tableKey): void {
                $selected = array_values(array_filter(
                    (array) ($data['columns'] ?? []),
                    fn ($name): bool => is_string($name) && $name !== '',
                ));

                // Page mode (HasTableExcelPage) atualiza a propriedade Livewire
                // para re-render automático; Preset antigo persiste em session.
                if (is_object($livewire) && method_exists($livewire, 'salvarFrozenColumns')) {
                    $livewire->salvarFrozenColumns($selected);
                } else {
                    TableExcelPreferences::put($tableKey, 'frozen_columns', $selected);
                }

                Notification::make()
                    ->title('Colunas congeladas atualizadas')
                    ->success()
                    ->send();
            });
    }
}
