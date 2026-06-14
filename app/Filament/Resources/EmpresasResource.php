<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpresasResource\Pages;
use App\Forms\Components\CnpjInput;
use App\Models\Cidade;
use App\Models\Empresas;
use App\Models\Estado;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use UnitEnum;

class EmpresasResource extends Resource
{
    protected static ?string $model = Empresas::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Cadastro de Empresas';

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $slug = 'empresas';

    protected static ?string $breadcrumb = 'Empresas';

    protected static ?string $pluralModelLabel = 'Lista de Empresas';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Razão Social')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nome_fantasia')
                    ->label('Nome Fantasia')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('responsavel')
                    ->label('Responsável')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contato')
                    ->mask('(99) 99999-9999')
                    ->maxLength(255),
                CnpjInput::make('cnpj')
                    ->required(),
                Select::make('tipo')
                    ->options([
                        'Gerenciadora' => 'Gerenciadora',
                        'Complamentares' => 'Complementares',
                        'Orçamentos' => 'Orçamentos',
                        'Fornecedor' => 'Fornecedor',
                        'Instaladora' => 'Instaladora',
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label('Ativo')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false),
                Select::make('pais_id')
                    ->label('País')
                    ->relationship('pais', 'nome')
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        if (! $get('pais_id')) {
                            $set('estado_id', null);
                            $set('cidade_id', null);
                        }
                    }),
                Select::make('estado_id')
                    ->relationship('estado', 'nome')
                    ->reactive()
                    ->disabled(fn (Get $get) => ! $get('pais_id'))
                    ->options(function (Get $get) {
                        $paisId = $get('pais_id');
                        if ($paisId) {
                            return Estado::where('pais_id', $paisId)->pluck('nome', 'id');
                        }

                        return Estado::pluck('nome', 'id');
                    })
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        if (! $get('estado_id')) {
                            $set('cidade_id', null);
                        }
                    }),
                Select::make('cidade_id')
                    ->relationship('cidade', 'nome')
                    ->disabled(fn (Get $get) => ! $get('estado_id'))
                    ->options(function (Get $get) {
                        $estadoId = $get('estado_id');
                        if ($estadoId) {
                            return Cidade::where('estado_id', $estadoId)->pluck('nome', 'id');
                        }

                        return Cidade::pluck('nome', 'id');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('nome_fantasia')
                    ->label('Nome curto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('responsavel')
                    ->label('Responsável')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('contato')->searchable(),
                Tables\Columns\TextColumn::make('tipo')->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Ativo' : 'Inativo')
                    ->colors([
                        'success' => static fn ($state): bool => $state === true,
                        'danger' => static fn ($state): bool => $state === false,
                        // 'success' => fn ($state) => $state === true,
                        // 'danger' => fn ($state) => $state === false,
                    ]),

                Tables\Columns\TextColumn::make('cidade.nome')->label('Cidade'),
                Tables\Columns\TextColumn::make('estado.nome')->label('Estado'),
                Tables\Columns\TextColumn::make('pais.nome')->label('País'),

                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpresas::route('/'),
            'create' => Pages\CreateEmpresas::route('/create'),
            'view' => Pages\ViewEmpresas::route('/{record}'),
            'edit' => Pages\EditEmpresas::route('/{record}/edit'),
        ];
    }
}
