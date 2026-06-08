<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Pages;

use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Models\Construtora;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListImportacaoNotaFiscals extends ListRecords
{
    protected static string $resource = 'App\\Filament\\Resources\\ImportacaoNotaFiscals\\ImportacaoNotaFiscalResource';

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('Fornecedor') && filled($user->construtoras_id)) {
            if (ConstrutoraControlesNotaFiscalPage::canAccess()) {
                $this->redirect(ConstrutoraControlesNotaFiscalPage::getUrl());
            }
        }
    }

    public function getTitle(): string
    {
        return 'Importação de Notas Fiscais';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('meusControles')
                ->label('Meus controles de NF')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(fn (): string => ConstrutoraControlesNotaFiscalPage::getUrl())
                ->visible(fn (): bool => ConstrutoraControlesNotaFiscalPage::canAccess()),
            CreateAction::make()
                ->label('Importar nota fiscal')
                ->visible(function (): bool {
                    $user = Auth::user();

                    return $user instanceof User && $user->can('Create:ControleNotaFiscalNota');
                }),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($query === null) {
            return null;
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->hasRole('super_admin')) {
            return $query;
        }

        if (! $user->hasRole('Fornecedor') || blank($user->construtoras_id)) {
            return $query;
        }

        $construtora = Construtora::query()->find($user->construtoras_id);

        if (! $construtora instanceof Construtora) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder) use ($construtora): void {
            $builder
                ->whereHas('autorizacaoServico.controleNotaFiscalItem', function (Builder $itemBuilder) use ($construtora): void {
                    $itemBuilder
                        ->where('empresa', $construtora->nome)
                        ->whereNotNull('liberado_para_fornecedor_at');
                })
                ->orWhereHas('asa.controleNotaFiscalAuxiliar', function (Builder $auxiliarBuilder) use ($construtora): void {
                    $auxiliarBuilder
                        ->where('empresa', $construtora->nome)
                        ->whereNotNull('liberado_para_fornecedor_at');
                });
        });
    }
}
