<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Enums\MotivoAlteracaoObra;
use App\Filament\Resources\ProjetoResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditProjeto extends EditRecord
{
    protected static string $resource = ProjetoResource::class;

    /**
     * @var list<string>
     */
    public array $validationSummaryMessages = [];

    protected function getHeaderActions(): array
    {
        Filament::registerRenderHook(
            'panels::page.start',
            fn () => view('filament.resources.projetos.partials.custom-title-style')
        );

        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->color('primary')
            ->keyBindings(['mod+s'])
            ->modalHeading(fn () => $this->dataPosseMudou() ? 'Justifique a alteração da Data de Posse' : null)
            ->modalDescription(fn () => $this->dataPosseMudou() ? 'A alteração da Data de Posse será registrada no histórico do projeto.' : null)
            ->modalSubmitActionLabel('Salvar com justificativa')
            ->modalWidth('lg')
            ->form(function (): ?array {
                if (! $this->dataPosseMudou()) {
                    return null;
                }

                return [
                    Select::make('motivo_codigo')
                        ->label('Motivo padronizado')
                        ->options(MotivoAlteracaoObra::paraSelect())
                        ->required(),
                    Textarea::make('motivo_historico')
                        ->label('Detalhe a justificativa (motivo histórico)')
                        ->rows(3)
                        ->maxLength(2000),
                ];
            })
            ->action(function (array $data = []): void {
                if (! empty($data['motivo_codigo'])) {
                    $this->record->motivo_alteracao_posse_codigo = $data['motivo_codigo'];
                }
                if (! empty($data['motivo_historico'])) {
                    $this->record->motivo_alteracao_posse_historico = $data['motivo_historico'];
                }

                $this->save();
            });
    }

    protected function dataPosseMudou(): bool
    {
        $original = $this->record->getOriginal('data_posse');
        $atual = $this->data['data_posse'] ?? null;

        $originalStr = $original ? Carbon::parse($original)->toDateString() : null;
        $atualStr = $atual ? Carbon::parse($atual)->toDateString() : null;

        return $originalStr !== $atualStr;
    }

    protected function beforeSave(): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole('Planejamento Estratégico', 'PMO', 'Comercial', 'super_admin')) {

            $destinatarios = User::query()
                ->whereHas(
                    'roles',
                    fn ($query) => $query->whereIn('name', [
                        'Planejamento Estratégico',
                        'PMO',
                        'Comercial',
                        'super_admin',
                    ])
                )
                ->get();

            Notification::make()
                ->title('Projeto Atualizado')
                ->body("O projeto {$this->record->nome} foi atualizado por {$user->name}.")
                ->icon('heroicon-o-pencil-square')
                ->warning()
                ->sendToDatabase($destinatarios);
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $this->validationSummaryMessages = [];

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        $this->validationSummaryMessages = collect($exception->validator->errors()->all())
            ->take(5)
            ->values()
            ->all();

        Notification::make()
            ->title('Não foi possível salvar o projeto.')
            ->body($this->validationSummaryMessages !== [] ? implode("\n", $this->validationSummaryMessages) : 'Revise os campos obrigatórios e tente novamente.')
            ->danger()
            ->persistent()
            ->send();
    }

    public function getFormActionsContentComponent(): Component
    {
        return Group::make([
            View::make('filament.resources.projetos.partials.validation-summary')
                ->viewData(fn (): array => [
                    'messages' => $this->validationSummaryMessages,
                ]),
            parent::getFormActionsContentComponent(),
        ]);
    }

    public function getTitle(): string
    {
        if ($this->record->nova_sigla) {
            return "{$this->record->nome} - {$this->record->nova_sigla}";
        }

        return $this->record->codigo
            ? "{$this->record->nome} - {$this->record->codigo}"
            : $this->record->nome;
    }
}
