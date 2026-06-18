<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Comentario;
use App\Models\Task;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\Computed;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    public string $novoComentario = '';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function adicionarComentario(): void
    {
        $texto = trim($this->novoComentario);
        if (! $texto) {
            return;
        }

        Comentario::create([
            'comentavel_type' => Task::class,
            'comentavel_id'   => $this->record->id,
            'usuario_id'      => auth()->id(),
            'conteudo'        => $texto,
        ]);

        $this->novoComentario = '';
        $this->record->unsetRelation('comentarios');

        Notification::make()->title('Comentário adicionado')->success()->send();
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
