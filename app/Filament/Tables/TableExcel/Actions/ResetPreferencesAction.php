<?php

namespace App\Filament\Tables\TableExcel\Actions;

use App\Filament\Tables\TableExcel\Support\TableExcelPreferences;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

final class ResetPreferencesAction
{
    public static function make(string $tableKey, ?string $name = null): Action
    {
        return Action::make($name ?? 'resetTableExcelPreferences')
            ->label('Resetar preferências')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Resetar preferências da tabela')
            ->modalDescription('Isso vai limpar densidade, colunas congeladas e ordenação salvas para esta tabela.')
            ->action(function () use ($tableKey): void {
                TableExcelPreferences::forget($tableKey);

                Notification::make()
                    ->title('Preferências resetadas')
                    ->success()
                    ->send();
            });
    }
}
