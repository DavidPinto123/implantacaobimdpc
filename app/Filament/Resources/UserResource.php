<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Obras;
use App\Models\Pais;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Cadastro de Usuários';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $slug = 'usuarios';

    protected static ?string $breadcrumb = 'Usuários';

    protected static ?string $pluralModelLabel = 'Lista de Usuários';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Seção: Dados do Usuário
                Section::make('Dados do Usuário')
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('WhatsApp')
                            ->placeholder('5511999999999')
                            ->helperText('Formato internacional sem +: 5511999999999')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->helperText('Opcional. Se deixar em branco, o sistema gera uma senha temporária e o usuário define a própria senha pelo e-mail.')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->autocomplete('new-password'),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Verificado em'),
                    ])
                    ->columns(3),

                Section::make('Dados de Perfil')
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->helperText('Se desmarcado, este usuário não acessa o sistema.'),

                        // Foto de perfil (certifique-se da coluna 'foto_perfil' em users)
                        FileUpload::make('foto_perfil')
                            ->hiddenLabel()
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn ($record) => 'user/fotos-perfil/'.($record?->id ?? 'temp')
                            ),
                    ])->columns(2),

                Section::make('Fornecedores')
                    ->schema([
                        Toggle::make('is_fornecedor')
                            ->label('É fornecedor?')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('construtoras_id', null);
                                }
                            }),

                        Select::make('construtoras_id')
                            ->label('Fornecedor associada')
                            ->relationship('construtora', 'nome')
                            ->preload()
                            ->searchable()
                            ->visible(fn ($get) => (bool) $get('is_fornecedor'))
                            ->required(fn ($get) => (bool) $get('is_fornecedor')),
                    ]),

                Section::make('Líder de Obra')
                    ->schema([
                        Toggle::make('is_lider_obra')
                            ->label('É líder de unidade?')
                            ->default(false)
                            ->live(),
                        Select::make('obrasComoLider')
                            ->label('Obras sob responsabilidade')
                            ->multiple()
                            ->relationship(
                                'obrasComoLider',
                                'codigo',
                                fn (Builder $query) => $query->whereHas('projeto', fn (Builder $q) => $q->whereNotNull('sigla')),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Obras $record) => $record->sigla ?? $record->codigo)
                            ->preload()
                            ->searchable()
                            ->visible(fn ($get) => (bool) $get('is_lider_obra')),
                    ]),

                // Seção: Localização
                Section::make('Região de Atuação')
                    ->schema([
                        Select::make('pais_id')
                            ->label('País')
                            ->options(Pais::pluck('nome', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('estado_id', null))
                            ->searchable(),

                        Select::make('estado_id')
                            ->label('Estado')
                            ->options(function (callable $get) {
                                $paisId = $get('pais_id');
                                if (! $paisId) {
                                    return [];
                                }

                                return Estado::query()
                                    ->where('pais_id', $paisId)
                                    ->pluck('nome', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null))
                            ->disabled(fn (callable $get) => empty($get('pais_id'))),

                        Select::make('cidade_id')
                            ->label('Cidade')
                            ->options(function (callable $get) {
                                $estadoId = $get('estado_id');
                                if (! $estadoId) {
                                    return [];
                                }

                                return Cidade::query()
                                    ->where('estado_id', $estadoId)
                                    ->pluck('nome', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => empty($get('estado_id'))),
                    ])
                    ->columns(3),

                // Seção: Permissões e Funções
                Section::make('Cargo e Setor')
                    ->schema([
                        Select::make('roles')
                            ->label('Cargo')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->multiple()
                            ->searchable(),

                        Select::make('setores')
                            ->label('Setor')
                            ->relationship('setores', 'setor')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('foto_perfil')
                    ->label('Foto')
                    ->disk((string) config('filesystems.media_disk', 'r2'))
                    ->circular()
                    ->size(40)
                    // se não houver foto, cai no ui-avatars com o nome:
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->label('Ativo?')
                    ->onColor('success')
                    ->offColor('danger'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    /*
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    */
}
