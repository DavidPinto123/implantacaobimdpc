<?php

namespace App\Filament\Resources\RelatorioFotograficos\Pages;

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Services\RelatorioFotograficoTaskService;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\Renderless;

class EditRelatorioFotografico extends EditRecord
{
    protected static string $resource = RelatorioFotograficoResource::class;

    public ?string $statusToSave = null;

    protected string $view = 'filament.resources.relatorio-fotografico-resource.pages.edit-relatorio-fotografico';

    public bool $isAutosaving = false;

    public bool $pendingAutosave = false;

    public ?string $lastSavedStateHash = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->lastSavedStateHash = $this->generateDraftHash(
            $this->getDraftData(false),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /*/
    protected function mutateFormDataBeforeSave(array $data): array
    {

        // Quando clicar em "Salvar rascunho"
        if ($this->statusToSave === 'Rascunho') {
            $data['status'] = 'Rascunho';
            $data['status_relatorio'] = 'Rascunho';

            return $data;
        }

        if (! in_array($data['status'] ?? null, [
            'aprovado_com_pendencia',
            'concluido',
        ])) {

            Notification::make()
                ->title('Defina o status antes de finalizar')
                ->body('O status deve ser "Aprovado com pendência" ou "Concluído".')
                ->danger()
                ->send();

            $this->halt(); // impede o save
        }

        return $data;
    }
    */
    protected function getFormActions(): array
    {
        return [

            Actions\Action::make('finalizar')
                ->label('Finalizar')
                ->color('primary')
                ->action('finalizar')
                ->submit(null),

            Actions\Action::make('salvar_rascunho')
                ->label('Salvar rascunho')
                ->color('gray')
                ->action('saveDraft'),

            /*

            Actions\Action::make('salvar_rascunho')
                ->label('Salvar rascunho')
                ->color('gray')
                ->action(function () {

                    $data = $this->form->getRawState();

                    $data['status'] = 'Rascunho';

                    $this->record->update($data);

                    Notification::make()
                        ->title('Rascunho salvo com sucesso')
                        ->success()
                        ->send();

                    return redirect(
                        static::getResource()::getUrl('index')
                    );
                }),
*/
            Actions\Action::make('cancelar')
                ->label('Cancelar')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),

    ];
    }

    protected function validarEntregasNaoEntregues(array $data): void
    {
        foreach (($data['entregas_contratuais'] ?? []) as $index => $item) {
            if (($item['status'] ?? null) === 'nao_entregue' && blank($item['data_prevista'] ?? null)) {
                Notification::make()
                    ->title('Pendência na entrega contratual')
                    ->body('A entrega "'.($item['titulo'] ?? ('#'.($index + 1))).'" está como "Não entregue" e precisa informar a data prevista.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function persistDraftUploads(): void
    {
        foreach ($this->form->getFlatFields(withHidden: true) as $field) {
            if ($field instanceof BaseFileUpload) {
                $field->saveUploadedFiles();
            }
        }
    }

    protected function normalizeDraftData(array $data): array
    {
        $data['data_posse'] = blank($data['data_posse'] ?? null) ? null : $data['data_posse'];

        if (isset($data['entregas_contratuais']) && is_array($data['entregas_contratuais'])) {
            $data['entregas_contratuais'] = collect($data['entregas_contratuais'])
                ->map(function (array $item) {
                    if (isset($item['arquivo']) && is_array($item['arquivo'])) {
                        $item['arquivo'] = array_values($item['arquivo']);
                    }

                    return [
                        'titulo' => $item['titulo'] ?? null,
                        'status' => $item['status'] ?? null,
                        'data_prevista' => $item['data_prevista'] ?? null,
                        'arquivo' => $item['arquivo'] ?? [],
                        'comentario' => $item['comentario'] ?? null,
                    ];
                })
                ->values()
                ->all();
        }

        if (isset($data['fotos']) && is_array($data['fotos'])) {
            $data['fotos'] = array_values($data['fotos']);
        }

        return $data;
    }

    protected function getDraftData(bool $persistUploads = true): array
    {
        if ($persistUploads) {
            $this->persistDraftUploads();
        }

        $data = $this->form->getRawState();
        $data = $this->normalizeDraftData($data);

        $data['status'] = 'Rascunho';
        $data['status_relatorio'] = 'Rascunho';

        if (blank($data['autor_id'] ?? null)) {
            $data['autor_id'] = auth()->id();
        }

        if (blank($data['gestor_id'] ?? null)) {
            $data['gestor_id'] = auth()->id();
        }

        return $data;
    }

    protected function normalizeForHash(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeForHash($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForHash($item);
        }

        return $value;
    }

    protected function generateDraftHash(array $data): string
    {
        return md5(json_encode($this->normalizeForHash($data)));
    }

    public function saveDraft(): void
    {
        $data = $this->getDraftData(true);

        $this->record->update($data);
        $this->lastSavedStateHash = $this->generateDraftHash($data);

        Notification::make()
            ->title('Rascunho salvo com sucesso.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    #[Renderless]
    public function autoSaveDraft(): void
    {
        if ($this->isAutosaving) {
            $this->pendingAutosave = true;

            return;
        }

        $this->isAutosaving = true;

        try {
            $data = $this->getDraftData(true);
            $currentHash = $this->generateDraftHash($data);

            if ($currentHash !== $this->lastSavedStateHash) {
                $this->record->fill($data);
                $this->record->save();

                $this->lastSavedStateHash = $currentHash;
            }

            $this->dispatch('draft-autosaved');
        } catch (\Throwable $e) {
            logger()->error('Erro no autosave do relatório fotográfico', [
                'record_id' => $this->record->id ?? null,
                'message' => $e->getMessage(),
            ]);

            $this->dispatch('draft-autosave-error');
        } finally {
            $this->isAutosaving = false;

            if ($this->pendingAutosave) {
                $this->pendingAutosave = false;
                $this->autoSaveDraft();
            }
        }
    }

    public function finalizar(): void
    {
        $this->persistDraftUploads();

        $data = $this->form->getState();

        if (! in_array($data['status'] ?? null, [
            'aprovado_com_pendencia',
            'concluido',
        ])) {
            Notification::make()
                ->title('Defina o status antes de finalizar')
                ->body('O status deve ser "Aprovado com pendência" ou "Concluído".')
                ->danger()
                ->send();

            return;
        }

        $this->validarEntregasNaoEntregues($data);

        if (blank($data['autor_id'] ?? null)) {
            $data['autor_id'] = auth()->id();
        }

        if (blank($data['gestor_id'] ?? null)) {
            $data['gestor_id'] = auth()->id();
        }

        $data['status_relatorio'] = $data['status'] ?? null;

        $this->record->update($data);

        RelatorioFotograficoTaskService::syncPendencias($this->record);

        Notification::make()
            ->title('Relatório finalizado com sucesso.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
