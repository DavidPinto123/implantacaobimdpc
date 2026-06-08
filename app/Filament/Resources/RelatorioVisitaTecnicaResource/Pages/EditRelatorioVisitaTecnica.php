<?php

namespace App\Filament\Resources\RelatorioVisitaTecnicaResource\Pages;

use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Services\RelatorioVisitaTecnicaTaskService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\Renderless;

class EditRelatorioVisitaTecnica extends EditRecord
{
    protected static string $resource = RelatorioVisitaTecnicaResource::class;

    protected string $view = 'filament.resources.relatorio-visita-tecnica-resource.pages.edit-relatorio-visita-tecnica';

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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('finalizar')
                ->label('Finalizar')
                ->color('primary')
                ->submit(null)
                ->action('finalizar'),

            Action::make('saveDraft')
                ->label('Salvar rascunho')
                ->color('gray')
                ->action('saveDraft'),
        ];
    }

    protected function persistDraftUploads(): void
    {
        foreach ($this->form->getFlatFields(withHidden: true) as $field) {
            if ($field instanceof BaseFileUpload) {
                $field->saveUploadedFiles();
            }
        }
    }

    protected function normalizeNaValues(array $data): array
    {
        $fields = [
            'prever_acustica_condensadores',
            'prever_protecao_condensadores',
            'necessario_elevador_plataforma',
            'necessario_pelicula_fachada',
            'prever_marquise',
            'prever_porta_enrolar',
            'prever_impermeabilizacao',
        ];

        foreach ($fields as $field) {
            if (($data[$field] ?? null) === 'na') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    protected function normalizeDraftRichEditors(array $data): array
    {
        if (! array_key_exists('pontos_atencao', $data) || blank($data['pontos_atencao'])) {
            return $data;
        }

        $value = $data['pontos_atencao'];

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data['pontos_atencao'] = $this->tiptapJsonToHtml($decoded);

                return $data;
            }

            $data['pontos_atencao'] = $value;

            return $data;
        }

        if (is_array($value)) {
            $data['pontos_atencao'] = $this->tiptapJsonToHtml($value);
        }

        return $data;
    }

    protected function tiptapJsonToHtml(array $node): string
    {
        $type = $node['type'] ?? null;
        $content = $node['content'] ?? [];
        $attrs = $node['attrs'] ?? [];

        return match ($type) {
            'doc' => collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode(''),

            'paragraph' => '<p>'.collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode('').'</p>',

            'text' => $this->applyTiptapMarks(
                e($node['text'] ?? ''),
                $node['marks'] ?? [],
            ),

            'bulletList' => '<ul>'.collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode('').'</ul>',

            'orderedList' => '<ol>'.collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode('').'</ol>',

            'listItem' => '<li>'.collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode('').'</li>',

            'heading' => $this->renderHeading($attrs, $content),

            'hardBreak' => '<br>',

            default => collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode(''),
        };
    }

    protected function renderHeading(array $attrs, array $content): string
    {
        $level = (int) ($attrs['level'] ?? 3);
        $level = max(1, min(6, $level));

        $innerHtml = collect($content)->map(fn ($child) => $this->tiptapJsonToHtml($child))->implode('');

        return "<h{$level}>{$innerHtml}</h{$level}>";
    }

    protected function applyTiptapMarks(string $text, array $marks): string
    {
        foreach ($marks as $mark) {
            $type = $mark['type'] ?? null;

            $text = match ($type) {
                'bold' => "<strong>{$text}</strong>",
                'italic' => "<em>{$text}</em>",
                'underline' => "<u>{$text}</u>",
                'strike' => "<s>{$text}</s>",
                default => $text,
            };
        }

        return $text;
    }

    protected function getDraftData(bool $persistUploads = true): array
    {
        if ($persistUploads) {
            $this->persistDraftUploads();
        }

        $data = $this->form->getRawState();
        $data = $this->normalizeNaValues($data);
        $data = $this->normalizeDraftRichEditors($data);

        if (isset($data['foto_capa']) && is_array($data['foto_capa'])) {
            $data['foto_capa'] = array_values($data['foto_capa'])[0] ?? null;
        }

        $data['status'] = 'Rascunho';
        $data['concluido_em'] = null;

        if (blank($data['iniciado_em'] ?? null)) {
            $data['iniciado_em'] = now();
        }

        if (blank($data['autor'] ?? null)) {
            $data['autor'] = auth()->user()?->name;
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
            logger()->error('Erro no autosave do relatório de visita técnica', [
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
        $data = $this->normalizeNaValues($data);
        $data = $this->normalizeDraftRichEditors($data);

        if (isset($data['foto_capa']) && is_array($data['foto_capa'])) {
            $data['foto_capa'] = array_values($data['foto_capa'])[0] ?? null;
        }

        if (blank($data['iniciado_em'] ?? null)) {
            $data['iniciado_em'] = now();
        }

        if (blank($data['autor'] ?? null)) {
            $data['autor'] = auth()->user()?->name;
        }

        $data['status'] = 'Concluído';
        $data['concluido_em'] = now();

        $this->record->update($data);

        RelatorioVisitaTecnicaTaskService::syncVisitaConsultorEnergia($this->record);

        Notification::make()
            ->title('Relatório finalizado com sucesso.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
