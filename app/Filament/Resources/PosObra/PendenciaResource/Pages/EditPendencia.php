<?php

namespace App\Filament\Resources\PosObra\PendenciaResource\Pages;

use App\Filament\Resources\PosObra\PendenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendencia extends EditRecord
{
    protected static string $resource = PendenciaResource::class;

    public function getHeading(): string
    {
        return $this->record?->codigo ?? 'Pendência';
    }

    public function getSubheading(): ?string
    {
        $record = $this->record;
        if (! $record) {
            return null;
        }

        $parts = [];

        if ($record->obra) {
            $parts[] = $record->obra->sigla ?? $record->obra->unidade;
        }

        if ($record->disciplina) {
            $parts[] = $record->disciplina->label;
        }

        if ($record->status) {
            $parts[] = $record->status->label();
        }

        if ($record->urgencia) {
            $parts[] = $record->urgencia->label();
        }

        return implode(' · ', array_filter($parts)) ?: null;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
