<?php

namespace App\Filament\Resources\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\TipoAnexo;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Events\PosObra\PendenciaAprovada;
use App\Filament\Resources\PosObra\PendenciaResource\Pages;
use App\Filament\Resources\PosObra\PendenciaResource\RelationManagers;
use App\Models\Construtora;
use App\Models\Obras;
use App\Models\PosObra\DisciplinaConfig;
use App\Models\PosObra\Pendencia;
use App\Models\User;
use App\Services\PosObra\PendenciaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PendenciaResource extends Resource
{
    protected static ?string $model = Pendencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Pós Obra';

    protected static ?string $navigationLabel = 'Pendências';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pendência';

    protected static ?string $pluralModelLabel = 'Pendências';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identificação')->schema([
                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->disabled()
                    ->placeholder('Gerado automaticamente'),
                Forms\Components\Select::make('obras_id')
                    ->label('Obra')
                    ->options(Obras::whereHas('projeto', fn ($q) => $q->whereNotNull('sigla'))->with('projeto:id,sigla')->get()->sortBy('sigla')->pluck('sigla', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('disciplina_config_id')
                    ->label('Disciplina')
                    ->options(DisciplinaConfig::where('ativo', true)->whereNotNull('label')->orderBy('ordem')->pluck('label', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('urgencia')
                    ->label('Urgência')
                    ->options(collect(UrgenciaPendencia::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                    ->required(),
                Forms\Components\TextInput::make('ticket')
                    ->label('Ticket')
                    ->maxLength(100),
                Forms\Components\Toggle::make('impacto_operacao')
                    ->label('Impacto operacional'),
            ])->columns(2),

            Section::make('Responsáveis')->schema([
                Forms\Components\Select::make('gestor_id')
                    ->label('Gestor')
                    ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['gestor_obra', 'super_admin']))
                        ->whereNotNull('name')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('lider_obra_id')
                    ->label('Líder de Unidade')
                    ->options(User::where('is_lider_obra', true)->whereNotNull('name')->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('construtora_id')
                    ->label('Fornecedor / Prestadora')
                    ->options(Construtora::whereNotNull('nome')->orderBy('nome')->pluck('nome', 'id'))
                    ->searchable(),
            ])->columns(3),

            Section::make('Detalhes')->schema([
                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('local_especifico')
                    ->label('Local específico')
                    ->maxLength(255),
                Forms\Components\Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Datas')->schema([
                Forms\Components\DatePicker::make('data_inicio')
                    ->label('Data de início'),
                Forms\Components\DatePicker::make('data_termino')
                    ->label('Data de término previsto'),
            ])->columns(2),

            Section::make('Status')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(collect(StatusPendencia::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                    ->required(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Resumo ───────────────────────────────────────────────────────
            Section::make()->schema([
                Grid::make(['default' => 2, 'md' => 3, 'lg' => 6])->schema([
                    TextEntry::make('codigo')
                        ->label('Código')
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold)
                        ->color('warning'),
                    TextEntry::make('obra.sigla')
                        ->label('Obra'),
                    TextEntry::make('disciplina.label')
                        ->label('Disciplina')
                        ->placeholder('—'),
                    TextEntry::make('urgencia')
                        ->label('Urgência')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof UrgenciaPendencia ? $state->label() : $state)
                        ->color(fn ($state) => match (true) {
                            $state === UrgenciaPendencia::P1 => 'green',
                            $state === UrgenciaPendencia::P2 => 'warning',
                            $state === UrgenciaPendencia::P3 => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : $state)
                        ->color(fn ($state) => match (true) {
                            $state === StatusPendencia::REGISTRADA => 'warning',
                            $state === StatusPendencia::NOTIFICADA_PRESTADORA => 'info',
                            $state === StatusPendencia::PENDENTE_COM_PRAZO => 'indigo',
                            $state === StatusPendencia::EM_EXECUCAO => 'amber',
                            $state === StatusPendencia::AGUARDANDO_APROVACAO => 'purple',
                            $state === StatusPendencia::CONCLUIDA => 'success',
                            $state === StatusPendencia::AS_ORCAMENTOS => 'cyan',
                            $state === StatusPendencia::GARANTIA_SOLICITADA => 'pink',
                            $state === StatusPendencia::PROJ_COMPLEMENTAR => 'violet',
                            $state === StatusPendencia::CANCELADA => 'gray',
                            default => 'gray',
                        }),
                    IconEntry::make('impacto_operacao')
                        ->label('Impacto Op.')
                        ->boolean()
                        ->trueIcon('heroicon-o-exclamation-triangle')
                        ->trueColor('warning')
                        ->falseIcon('heroicon-o-check-circle')
                        ->falseColor('success'),
                ]),
            ])->compact()->columnSpanFull(),

            // ── Responsáveis ─────────────────────────────────────────────────
            Section::make('Responsáveis')->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    TextEntry::make('gestor.name')
                        ->label('Gestor')
                        ->placeholder('—'),
                    TextEntry::make('liderObra.name')
                        ->label('Líder de Unidade')
                        ->placeholder('—'),
                    TextEntry::make('fornecedor.nome')
                        ->label('Fornecedor / Prestadora')
                        ->placeholder('—'),
                ]),
            ])->columnSpanFull(),

            // ── Detalhes ─────────────────────────────────────────────────────
            Section::make('Detalhes')->schema([
                TextEntry::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),
                TextEntry::make('local_especifico')
                    ->label('Local específico')
                    ->placeholder('—'),
                TextEntry::make('ticket')
                    ->label('Ticket')
                    ->placeholder('—'),
                TextEntry::make('observacoes')
                    ->label('Observações')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),

            // ── Datas ────────────────────────────────────────────────────────
            Section::make('Datas')->schema([
                Grid::make(['default' => 2, 'md' => 4])->schema([
                    TextEntry::make('data_inicio')
                        ->label('Data de início')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('data_termino')
                        ->label('Prazo previsto')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('data_conclusao')
                        ->label('Concluída em')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Criado em')
                        ->dateTime('d/m/Y H:i'),
                ]),
            ])->columnSpanFull(),

            // ── Fotos e Anexos ───────────────────────────────────────────────
            Section::make('Fotos e Anexos')
                ->schema([
                    RepeatableEntry::make('anexos')
                        ->hiddenLabel()
                        ->schema([
                            ImageEntry::make('url')
                                ->hiddenLabel()
                                ->height(180)
                                ->extraImgAttributes(['style' => 'object-fit:cover; border-radius:.5rem; width:100%;']),
                            TextEntry::make('tipo')
                                ->label('Tipo')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state instanceof TipoAnexo ? $state->label() : $state),
                            TextEntry::make('nome_arquivo')
                                ->label('Arquivo')
                                ->placeholder('—')
                                ->limit(30),
                            TextEntry::make('created_at')
                                ->label('Enviado em')
                                ->dateTime('d/m/Y H:i'),
                        ])
                        ->grid(['default' => 2, 'md' => 3, 'lg' => 4])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false)
                ->columnSpanFull(),

            // ── Histórico de Status ───────────────────────────────────────────
            Section::make('Histórico de Status')
                ->schema([
                    RepeatableEntry::make('atualizacoesStatus')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Data')
                                ->dateTime('d/m/Y H:i')
                                ->weight(FontWeight::SemiBold),
                            TextEntry::make('status_anterior')
                                ->label('De')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : ($state ?? '—'))
                                ->color(fn ($state) => $state instanceof StatusPendencia ? $state->color() : 'gray'),
                            TextEntry::make('status_novo')
                                ->label('Para')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : $state)
                                ->color(fn ($state) => $state instanceof StatusPendencia ? $state->color() : 'gray'),
                            TextEntry::make('atualizado_por')
                                ->label('Por'),
                            TextEntry::make('comentario')
                                ->label('Observação')
                                ->placeholder('—')
                                ->columnSpan(['default' => 1, 'md' => 2]),
                        ])
                        ->columns(['default' => 2, 'md' => 5])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->color('warning')
                    ->url(fn (Pendencia $record) => static::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('obra.sigla')
                    ->label('Obra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('disciplina.label')
                    ->label('Disciplina')
                    ->searchable(),
                Tables\Columns\TextColumn::make('urgencia')
                    ->label('Urgência')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof UrgenciaPendencia ? $state->label() : $state)
                    ->color(fn ($state) => match (true) {
                        $state === UrgenciaPendencia::P1 => 'green',
                        $state === UrgenciaPendencia::P2 => 'warning',
                        $state === UrgenciaPendencia::P3 => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : $state)
                    ->color(fn ($state) => match (true) {
                        $state === StatusPendencia::REGISTRADA => 'warning',
                        $state === StatusPendencia::NOTIFICADA_PRESTADORA => 'info',
                        $state === StatusPendencia::PENDENTE_COM_PRAZO => 'indigo',
                        $state === StatusPendencia::EM_EXECUCAO => 'amber',
                        $state === StatusPendencia::AGUARDANDO_APROVACAO => 'purple',
                        $state === StatusPendencia::CONCLUIDA => 'success',
                        $state === StatusPendencia::AS_ORCAMENTOS => 'cyan',
                        $state === StatusPendencia::GARANTIA_SOLICITADA => 'pink',
                        $state === StatusPendencia::PROJ_COMPLEMENTAR => 'violet',
                        $state === StatusPendencia::CANCELADA => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('gestor.name')
                    ->label('Gestor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_termino')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('atrasada')
                    ->label('Atrasada')
                    ->boolean()
                    ->getStateUsing(fn (Pendencia $record) => $record->estaAtrasada())
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fornecedor.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(StatusPendencia::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])),
                Tables\Filters\SelectFilter::make('urgencia')
                    ->options(collect(UrgenciaPendencia::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])),
                Tables\Filters\SelectFilter::make('obras_id')
                    ->label('Obra')
                    ->options(Obras::whereHas('projeto', fn ($q) => $q->whereNotNull('sigla'))->with('projeto:id,sigla')->get()->sortBy('sigla')->pluck('sigla', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('gestor_id')
                    ->label('Gestor')
                    ->options(User::whereNotNull('name')->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('construtora_id')
                    ->label('Fornecedor')
                    ->options(Construtora::whereNotNull('nome')->orderBy('nome')->pluck('nome', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('disciplina_config_id')
                    ->label('Disciplina')
                    ->options(DisciplinaConfig::whereNotNull('label')->orderBy('label')->pluck('label', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Action::make('avancar')
                    ->label('Avançar status')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn (Pendencia $record) => ! $record->status->isTerminal())
                    ->action(function (Pendencia $record) {
                        app(PendenciaService::class)->avancarStatus($record, auth()->user()->name ?? 'Painel');
                        Notification::make()->title('Status atualizado')->success()->send();
                    }),
                Action::make('concluir')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Pendencia $record) => $record->status === StatusPendencia::AGUARDANDO_APROVACAO)
                    ->requiresConfirmation()
                    ->action(function (Pendencia $record) {
                        app(PendenciaService::class)->registrarAtualizacaoStatus(
                            $record,
                            StatusPendencia::CONCLUIDA,
                            auth()->user()->name ?? 'Painel',
                        );
                        event(new PendenciaAprovada($record));
                        Notification::make()->title('Pendência aprovada e concluída')->success()->send();
                    }),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Pendencia $record) => ! $record->status->isTerminal())
                    ->requiresConfirmation()
                    ->action(function (Pendencia $record) {
                        app(PendenciaService::class)->registrarAtualizacaoStatus(
                            $record,
                            StatusPendencia::CANCELADA,
                            auth()->user()->name ?? 'Painel',
                        );
                        Notification::make()->title('Pendência cancelada')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\HistoricoStatusRelationManager::class,
            RelationManagers\AnexosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendencias::route('/'),
            'create' => Pages\CreatePendencia::route('/create'),
            'view' => Pages\ViewPendencia::route('/{record}'),
            'edit' => Pages\EditPendencia::route('/{record}/edit'),
        ];
    }
}
