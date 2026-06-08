<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Pages;

use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Filament\Resources\ElaboracaoAditivos\Widgets\AditivosResumoWidget;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListElaboracaoAditivos extends ListRecords implements HasForms
{
    use ExposesTableToWidgets;
    use InteractsWithForms;

    protected static string $resource = ElaboracaoAditivoResource::class;

    public ?string $construtoraFiltro = null;

    public ?string $gestorFiltro = null;

    public ?string $obraFiltro = null;

    public ?string $dataDeFiltro = null;

    public ?string $dataAteFiltro = null;

    public function getTitle(): string
    {
        return 'Elaboração de Aditivos';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'construtoraFiltro' => $this->construtoraFiltro,
            'gestorFiltro' => $this->gestorFiltro,
            'obraFiltro' => $this->obraFiltro,
            'dataDeFiltro' => $this->dataDeFiltro,
            'dataAteFiltro' => $this->dataAteFiltro,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('obraFiltro')
                    ->label('Obra')
                    ->options(
                        Obras::query()
                            ->whereNotNull('unidade')
                            ->where('unidade', '<>', '')
                            ->orderBy('unidade')
                            ->pluck('unidade', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('gestorFiltro', null);
                        $set('construtoraFiltro', null);
                    }),

                Select::make('gestorFiltro')
                    ->label('Gestor')
                    ->options(fn () => $this->getGestorFilterOptions())
                    ->searchable()
                    ->preload()
                    ->live(),

                Select::make('construtoraFiltro')
                    ->label('Fornecedor')
                    ->options(fn () => $this->getConstrutoraFilterOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(function () {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            return false;
                        }

                        if ($user->hasRole('super_admin')) {
                            return true;
                        }

                        return $user->hasRole('Gestor')
                            && $user->setores()->where('setor', 'Obras')->exists();
                    }),
                /*
                DatePicker::make('dataDeFiltro')
                    ->label('Data de')
                    ->live(),

                DatePicker::make('dataAteFiltro')
                    ->label('Data até')
                    ->live(),
                */
            ])
            ->columns(3)
            ->statePath('');
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.elaboracao-aditivos.pages.list-elaboracao-aditivos-filters');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Criar Aditivo')
                ->url(static::getResource()::getUrl('create-custom'))
                ->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AditivosResumoWidget::make([
                'construtoraFiltro' => $this->construtoraFiltro,
                'gestorFiltro' => $this->gestorFiltro,
                'obraFiltro' => $this->obraFiltro,
                'dataDeFiltro' => $this->dataDeFiltro,
                'dataAteFiltro' => $this->dataAteFiltro,
            ]),
        ];
    }

    public function limparFiltros(): void
    {
        $this->construtoraFiltro = null;
        $this->gestorFiltro = null;
        $this->obraFiltro = null;
        $this->dataDeFiltro = null;
        $this->dataAteFiltro = null;

        $this->form->fill([
            'construtoraFiltro' => null,
            'gestorFiltro' => null,
            'obraFiltro' => null,
            'dataDeFiltro' => null,
            'dataAteFiltro' => null,
        ]);

        $this->resetTable();
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 1,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('obra.unidade')
                    ->label('Obra')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('asEscopo.escopo')
                    ->label('Escopo')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('fornecedor.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('gestor.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('itens_count')
                    ->label('Qtd. Itens')
                    ->counts('itens')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_aditivo')
                    ->label('Total do Aditivo')
                    ->getStateUsing(fn (ElaboracaoAditivo $record) => $record->itens->sum('valor_total_geral'))
                    ->money('BRL'),

                TextColumn::make('status_fluxo')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'elaboracao' => 'Elaboração',
                        'em_aprovacao_gestor' => 'Em aprovação do gestor',
                        'em_aprovacao_orcamento' => 'Em aprovação do orçamentista',
                        'aprovado' => 'Aprovado pelo orçamentista',
                        'reprovado_gestor' => 'Reprovado pelo gestor',
                        'reprovado_orcamento' => 'Reprovado pelo orçamentista',
                        default => $state ?: 'Elaboração',
                    })
                    ->colors([
                        'gray' => ['elaboracao', null],
                        'warning' => ['em_aprovacao_gestor', 'em_aprovacao_orcamento'],
                        'success' => 'aprovado',
                        'danger' => ['reprovado_gestor', 'reprovado_orcamento'],
                    ]),

                TextColumn::make('motivo_reprovacao')
                    ->label('Motivo da reprovação')
                    ->getStateUsing(function (ElaboracaoAditivo $record): ?string {
                        if ($record->status_fluxo === 'reprovado_orcamento') {
                            return $record->justificativa_reprovacao_orcamento;
                        }

                        if ($record->status_fluxo === 'reprovado_gestor') {
                            return $record->justificativa_reprovacao_gestor;
                        }

                        return null;
                    })
                    ->limit(40)
                    ->tooltip(fn (ElaboracaoAditivo $record) => $record->status_fluxo === 'reprovado_orcamento'
                        ? $record->justificativa_reprovacao_orcamento
                        : $record->justificativa_reprovacao_gestor),

                TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->recordUrl(
                fn (ElaboracaoAditivo $record) => ElaboracaoAditivoResource::getUrl('visualizar', [
                    'record' => $record,
                ])
            )
            ->actions([
                Action::make('visualizar')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ElaboracaoAditivo $record) => ElaboracaoAditivoResource::getUrl('visualizar', [
                        'record' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();

        $query = ElaboracaoAditivo::query()
            ->with([
                'obra',
                'construtora',
                'gestor',
                'asEscopo',
                'itens',
            ]);

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $podeVerTudo =
            $user->hasRole('super_admin') ||
            $user->hasRole('Gestor') ||
            $user->setores()->where('setor', 'Obras')->exists();

        if (! $podeVerTudo) {
            if ($user->hasRole('Fornecedor') && $user->construtoras_id) {
                $query->where('construtora_id', $user->construtoras_id);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        return $this->applyFilters($query);
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->construtoraFiltro, fn (Builder $q) => $q->where('construtora_id', $this->construtoraFiltro))
            ->when($this->gestorFiltro, fn (Builder $q) => $q->where('gestor_id', $this->gestorFiltro))
            ->when($this->obraFiltro, fn (Builder $q) => $q->where('obra_id', $this->obraFiltro))
            ->when($this->dataDeFiltro, fn (Builder $q) => $q->whereDate('data', '>=', $this->dataDeFiltro))
            ->when($this->dataAteFiltro, fn (Builder $q) => $q->whereDate('data', '<=', $this->dataAteFiltro));
    }

    protected function getConstrutoraFilterOptions(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $podeVerFiltro =
            $user->hasRole('super_admin') ||
            (
                $user->hasRole('Gestor') &&
                $user->setores()->where('setor', 'Obras')->exists()
            );

        if (! $podeVerFiltro) {
            return [];
        }

        if (! $this->obraFiltro) {
            return [];
        }

        $obra = Obras::query()
            ->with('construtoras')
            ->find($this->obraFiltro);

        if (! $obra) {
            return [];
        }

        return $obra->construtoras
            ->sortBy('nome')
            ->filter(fn ($construtora) => filled($construtora->nome))
            ->pluck('nome', 'id')
            ->toArray();
    }

    protected function getGestorFilterOptions(): array
    {
        if (! $this->obraFiltro) {
            return [];
        }

        $obra = Obras::query()->find($this->obraFiltro);

        if (! $obra || blank($obra->engenharia)) {
            return [];
        }

        return User::query()
            ->where('name', $obra->engenharia)
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedConstrutoraFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedGestorFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedObraFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedDataDeFiltro(): void
    {
        $this->resetTable();
    }

    public function updatedDataAteFiltro(): void
    {
        $this->resetTable();
    }
}
