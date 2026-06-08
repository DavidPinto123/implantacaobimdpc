<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Resources\ProjetoResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProjeto extends CreateRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        $user = Auth::user();

        if ($user->hasAnyRole('Planejamento Estratégico', 'PMO', 'Comercial', 'super_admin')) {

            $destinatarios = User::role([
                'Planejamento Estratégico',
                'PMO',
                'Comercial',
                'super_admin',
            ])->get();

            Notification::make()
                ->title('Projeto Criado')
                ->body("O projeto {$record->nome} foi criado por {$user->name}.")
                ->success()
                ->sendToDatabase($destinatarios);
        }
    }
}
