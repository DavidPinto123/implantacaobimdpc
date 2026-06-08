<?php

namespace App\Filament\Resources\Asas\Pages;

use App\Enums\AsStatus;
use App\Filament\Resources\Asas\AsaResource;
use App\Models\Asa;
use App\Services\AsaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Mantemos esta página comentada em uso pela resource para preservar a implementação
// anterior caso seja necessário reabilitar o fluxo de rascunho manual no futuro.
class CreateAsa extends CreateRecord
{
    protected static string $resource = AsaResource::class;

    public function mount(): void
    {
        parent::mount();

        $record = $this->createDraft();

        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]), navigate: true);
    }

    protected function createDraft(): Asa
    {
        $record = Asa::create($this->getDraftPayload());

        $this->record = $record;
        $this->form->model($record);

        /** @var AsaService $asaService */
        $asaService = app(AsaService::class);
        $asaService->normalizeMediaPaths($record);

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDraftPayload(): array
    {
        $userId = Auth::id();

        return [
            'numero_asa' => 'ASA-DRAFT-'.Str::upper(Str::random(10)),
            'status' => AsStatus::RASCUNHO->value,
            'objeto' => 'Rascunho automático de ASA',
            'altera_prazo' => 'Não',
            'valor_bruto' => 0,
            'desconto' => 0,
            'valor_total' => 0,
            'gestor_id' => $userId,
        ];
    }
}
