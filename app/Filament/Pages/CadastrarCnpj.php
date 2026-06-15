<?php

namespace App\Filament\Pages;

use App\Forms\Components\CnpjInput;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use App\Support\Cnpj;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CadastrarCnpj extends Page implements HasTable
{
    use InteractsWithTable {
        setPage as filamentSetPage;
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Cadastro de CNPJ';

    protected static ?string $title = 'Cadastro de CNPJ';

    protected static ?string $slug = 'cadastrar-cnpj';

    protected string $view = 'filament.pages.cadastrar-cnpj';

    protected static ?int $navigationSort = 3;

    protected static function can(string $permission): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can($permission);
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeAction(string $permission): void
    {
        if (static::can($permission)) {
            return;
        }

        throw new AuthorizationException('Você não tem permissão para executar esta ação.');
    }

    public static function canAccess(): bool
    {
        return static::can('View:CadastrarCnpj');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void {}

    public function mountAction(string $name, array $arguments = [], array $context = []): mixed
    {
        if (($context['table'] ?? false) === true) {
            $this->clearInvalidMountedTableActionState();
        }

        return parent::mountAction($name, $arguments, $context);
    }

    public function setPage(int|string $page, ?string $pageName = null): void
    {
        $this->clearMountedTableActionState();

        $this->filamentSetPage($page, $pageName);
    }

    public function updatedTableRecordsPerPage(): void
    {
        $this->clearMountedTableActionState();

        session()->put([
            $this->getTablePerPageSessionKey() => $this->getTableRecordsPerPage(),
        ]);

        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Projeto::query()
                    ->select([
                        'id',
                        'nome',
                        'codigo',
                        'nova_sigla',
                        'sigla_antiga',
                        'cnpj',
                        'cnpj_provisorio',
                        'status_cnpj',
                        'pais_id',
                        'estado_id',
                        'cidade_id',
                        'updated_at',
                    ])
                    ->orderBy('nome')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Projeto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nova_sigla')
                    ->label('Nova Sigla')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sigla_antiga')
                    ->label('Sigla Antiga')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ Definitivo')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cnpj_provisorio')
                    ->label('CNPJ Provisório')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_cnpj')
                    ->label('Status do CNPJ')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'definitivo' => 'CNPJ Definitivo',
                        'provisorio' => 'CNPJ Provisório',
                        default => $state ?: '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'definitivo' => 'success',
                        'provisorio' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Importar planilha')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->visible(fn (): bool => static::can('Create:CadastrarCnpj'))
                    ->url(url('/admin/import-cnpjs')),

                Action::make('create')
                    ->label('Cadastrar CNPJ')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn (): bool => static::can('Create:CadastrarCnpj'))
                    ->fillForm([
                        'projeto_id' => null,
                        'nova_sigla' => null,
                        'sigla_antiga' => null,
                        'cnpj' => null,
                        'cnpj_provisorio' => null,
                        'status_cnpj' => null,
                        'pais_id' => null,
                        'estado_id' => null,
                        'cidade_id' => null,
                    ])
                    ->schema($this->getCreateFormComponents())
                    ->modalHeading('Cadastrar CNPJ')
                    ->modalSubmitActionLabel('Salvar CNPJ')
                    ->action(function (array $data): void {
                        $this->authorizeAction('Create:CadastrarCnpj');

                        $projeto = Projeto::find($data['projeto_id'] ?? null);

                        if (! $projeto instanceof Projeto) {
                            Notification::make()
                                ->title('Projeto inválido')
                                ->body('Selecione um projeto válido para cadastrar o CNPJ.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $this->persistProjetoCnpj($projeto, $data)) {
                            return;
                        }

                        Notification::make()
                            ->title('CNPJ salvo com sucesso.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (): bool => static::can('Update:CadastrarCnpj'))
                    ->fillForm(fn (Projeto $record): array => [
                        'nova_sigla' => $record->nova_sigla,
                        'sigla_antiga' => $record->sigla_antiga,
                        'cnpj' => $record->cnpj,
                        'cnpj_provisorio' => $record->cnpj_provisorio,
                        'status_cnpj' => $record->status_cnpj,
                        'pais_id' => $record->pais_id,
                        'estado_id' => $record->estado_id,
                        'cidade_id' => $record->cidade_id,
                    ])
                    ->schema($this->getFormComponents())
                    ->modalHeading(fn (Projeto $record): string => 'Editar CNPJ • '.$this->formatProjetoLabel($record))
                    ->modalSubmitActionLabel('Salvar alterações')
                    ->action(function (Projeto $record, array $data): void {
                        $this->authorizeAction('Update:CadastrarCnpj');

                        if (! $this->persistProjetoCnpj($record, $data)) {
                            return;
                        }

                        Notification::make()
                            ->title('CNPJ salvo com sucesso.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Nenhum projeto encontrado.')
            ->striped()
            ->defaultSort('nome');
    }

    /**
     * @return array<int, TextInput|Select|CnpjInput>
     */
    protected function getFormComponents(): array
    {
        return [
            TextInput::make('nova_sigla')
                ->label('Nova Sigla'),

            TextInput::make('sigla_antiga')
                ->label('Sigla Antiga'),

            CnpjInput::make('cnpj')
                ->label('CNPJ Definitivo')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): null => $this->resetCnpjStatusWhenDocumentIsEmpty('definitivo', $state, $get, $set)),

            CnpjInput::make('cnpj_provisorio')
                ->label('CNPJ Provisório')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): null => $this->resetCnpjStatusWhenDocumentIsEmpty('provisorio', $state, $get, $set)),

            Select::make('status_cnpj')
                ->label('Status do CNPJ')
                ->helperText('Para selecionar um Status, primeiro preencha o CNPJ.')
                ->options([
                    'definitivo' => 'CNPJ Definitivo',
                    'provisorio' => 'CNPJ Provisório',
                ])
                ->required()
                ->disableOptionWhen(fn (string $value, Get $get): bool => $this->isCnpjStatusOptionDisabled($value, $get))
                ->rule(fn (Get $get): Closure => $this->cnpjStatusMatchesFilledDocumentRule($get))
                ->native(false),

            Select::make('pais_id')
                ->label('País')
                ->options(fn (): array => Pais::query()->orderBy('nome')->pluck('nome', 'id')->all())
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('estado_id', null)),

            Select::make('estado_id')
                ->label('Estado')
                ->options(fn (callable $get): array => Estado::query()
                    ->when($get('pais_id'), fn ($query, $paisId) => $query->where('pais_id', $paisId))
                    ->orderBy('nome')
                    ->pluck('nome', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('cidade_id', null)),

            Select::make('cidade_id')
                ->label('Cidade')
                ->options(fn (callable $get): array => Cidade::query()
                    ->when($get('estado_id'), fn ($query, $estadoId) => $query->where('estado_id', $estadoId))
                    ->orderBy('nome')
                    ->pluck('nome', 'id')
                    ->all())
                ->searchable()
                ->preload(),
        ];
    }

    /**
     * @return array<int, TextInput|Select|CnpjInput>
     */
    protected function getCreateFormComponents(): array
    {
        return [
            Select::make('projeto_id')
                ->label('Projeto')
                ->options(
                    Projeto::query()
                        ->orderBy('nome')
                        ->get()
                        ->mapWithKeys(fn (Projeto $projeto): array => [
                            $projeto->id => $this->formatProjetoLabel($projeto),
                        ])
                        ->all()
                )
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set): void {
                    $projeto = filled($state) ? Projeto::find($state) : null;

                    $set('nova_sigla', $projeto?->nova_sigla);
                    $set('sigla_antiga', $projeto?->sigla_antiga);
                    $set('cnpj', $projeto?->cnpj);
                    $set('cnpj_provisorio', $projeto?->cnpj_provisorio);
                    $set('status_cnpj', $projeto?->status_cnpj);
                    $set('pais_id', $projeto?->pais_id);
                    $set('estado_id', $projeto?->estado_id);
                    $set('cidade_id', $projeto?->cidade_id);
                }),

            ...$this->getFormComponents(),
        ];
    }

    protected function formatProjetoLabel(Projeto $projeto): string
    {
        return trim(collect([
            $projeto->nome,
            $projeto->codigo,
            $projeto->nova_sigla,
        ])->filter()->implode(' • '));
    }

    protected function clearMountedTableActionState(): void
    {
        while (! empty($this->mountedActions)) {
            $this->unmountTableAction(false);
        }
    }

    protected function clearInvalidMountedTableActionState(): void
    {
        $hasInvalidMountedAction = collect($this->mountedActions)
            ->contains(fn (mixed $mountedAction): bool => ! is_array($mountedAction) || blank($mountedAction['name'] ?? null));

        if (! $hasInvalidMountedAction) {
            return;
        }

        $this->mountedActions = [];
        $this->cachedMountedActions = null;
    }

    protected function normalizeCnpj(mixed $value): string
    {
        return Cnpj::normalize(is_string($value) ? $value : null);
    }

    protected function resetCnpjStatusWhenDocumentIsEmpty(string $status, mixed $state, Get $get, Set $set): null
    {
        if ($get('status_cnpj') === $status && $this->normalizeCnpj($state) === '') {
            $set('status_cnpj', null);
        }

        return null;
    }

    protected function isCnpjStatusOptionDisabled(string $status, Get $get): bool
    {
        return match ($status) {
            'definitivo' => $this->normalizeCnpj($get('cnpj')) === '',
            'provisorio' => $this->normalizeCnpj($get('cnpj_provisorio')) === '',
            default => false,
        };
    }

    protected function cnpjStatusMatchesFilledDocumentRule(Get $get): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            if ($value === 'definitivo' && $this->normalizeCnpj($get('cnpj')) === '') {
                $fail('Preencha o CNPJ definitivo para selecionar o status definitivo.');
            }

            if ($value === 'provisorio' && $this->normalizeCnpj($get('cnpj_provisorio')) === '') {
                $fail('Preencha o CNPJ provisório para selecionar o status provisório.');
            }
        };
    }

    protected function persistProjetoCnpj(Projeto $projeto, array $data): bool
    {
        $cnpj = $this->normalizeCnpj($data['cnpj'] ?? null);
        $cnpjProvisorio = $this->normalizeCnpj($data['cnpj_provisorio'] ?? null);

        if ($cnpj !== '' && $cnpj === $cnpjProvisorio) {
            Notification::make()
                ->title('CNPJs duplicados')
                ->body('O CNPJ definitivo e o CNPJ provisório não podem ser iguais.')
                ->danger()
                ->send();

            return false;
        }

        $formattedCnpj = Cnpj::format($cnpj);
        $formattedCnpjProvisorio = Cnpj::format($cnpjProvisorio);

        if ($formattedCnpj || $formattedCnpjProvisorio) {
            $conflictingProjeto = Projeto::query()
                ->where('id', '!=', $projeto->id)
                ->where(function ($query) use ($formattedCnpj, $formattedCnpjProvisorio): void {
                    if ($formattedCnpj) {
                        $query
                            ->orWhere('cnpj', $formattedCnpj)
                            ->orWhere('cnpj_provisorio', $formattedCnpj);
                    }

                    if ($formattedCnpjProvisorio) {
                        $query
                            ->orWhere('cnpj', $formattedCnpjProvisorio)
                            ->orWhere('cnpj_provisorio', $formattedCnpjProvisorio);
                    }
                })
                ->first();

            if ($conflictingProjeto instanceof Projeto) {
                Notification::make()
                    ->title('CNPJ já vinculado')
                    ->body('Já existe outro projeto com este CNPJ definitivo ou provisório.')
                    ->danger()
                    ->send();

                return false;
            }
        }

        $projeto->update([
            'nova_sigla' => $data['nova_sigla'] ?? null,
            'sigla_antiga' => $data['sigla_antiga'] ?? null,
            'cnpj' => $formattedCnpj,
            'cnpj_provisorio' => $formattedCnpjProvisorio,
            'status_cnpj' => $data['status_cnpj'] ?? null,
            'pais_id' => $data['pais_id'] ?? null,
            'estado_id' => $data['estado_id'] ?? null,
            'cidade_id' => $data['cidade_id'] ?? null,
        ]);

        return true;
    }
}
