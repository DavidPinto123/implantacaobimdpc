<?php

namespace App\Filament\Resources;

use App\Enums\PosObra\TipoConstrutora;
use App\Filament\Resources\ConstrutoraResource\Pages;
use App\Forms\Components\CnpjInput;
use App\Models\Construtora;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ConstrutoraResource extends Resource
{
    protected static ?string $model = Construtora::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::HomeModern;

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Fornecedor';

    protected static ?string $navigationLabel = 'Fornecedores';

    protected static ?string $modelLabel = 'fornecedor';

    protected static ?string $pluralModelLabel = 'fornecedores';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('nome')->required()->maxLength(255),
                CnpjInput::make('cnpj')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'Informe o CNPJ do fornecedor.',
                        'unique' => 'Este CNPJ já está cadastrado.',
                    ]),
                Forms\Components\TextInput::make('inscricao_estadual')
                    ->label('Inscrição Estadual')
                    ->maxLength(255),
                Forms\Components\TextInput::make('telefone')
                    ->label('Telefone')
                    ->mask('(99) 9999-9999')
                    ->mask('(99) 99999-9999')
                    ->placeholder('(00) 00000-0000')
                    ->tel()
                    ->maxLength(20)
                    ->validationMessages([
                        'max' => 'O número de telefone é muito longo.',
                    ]),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255)
                    ->validationMessages([
                        'email' => 'Informe um e-mail válido.',
                        'required' => 'O campo e-mail é obrigatório.',
                        'max' => 'E-mail não pode passar de 255 caracteres.',
                    ]),
                Forms\Components\TextInput::make('endereco')
                    ->label('Endereço')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cep')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->maxLength(255),
                Forms\Components\TextInput::make('responsavel')
                    ->label('Responsável')
                    ->maxLength(255),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->native(false)
                    ->options(collect(TipoConstrutora::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                    ->default(TipoConstrutora::CONSTRUTORA->value)
                    ->required(),
                Forms\Components\TextInput::make('telefone_whatsapp')
                    ->label('WhatsApp')
                    ->placeholder('5511999999999')
                    ->helperText('Formato internacional sem +: 5511999999999')
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('cnpj')->label('CNPJ')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('inscricao_estadual')->label('I.E')->searchable(),
                Tables\Columns\TextColumn::make('telefone'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state instanceof TipoConstrutora ? $state->label() : $state)
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListConstrutoras::route('/'),
            'create' => Pages\CreateConstrutora::route('/create'),
            'edit' => Pages\EditConstrutora::route('/{record}/edit'),
        ];
    }
}
