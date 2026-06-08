<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaFiscalResource\Pages;
use App\Filament\Resources\NotaFiscalResource\RelationManagers;
use App\Models\NotaFiscal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\MultiSelect;

class NotaFiscalResource extends Resource
{
    protected static ?string $model = NotaFiscal::class;
   
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
  
    protected static ?string $navigationLabel = 'Notas Fiscais';
  
    protected static ?string $navigationGroup = 'Fornecedor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Nota Fiscal')
                    ->schema([
                        // Card: Campos principais
                        Forms\Components\Card::make('Informações Principais')
                            ->columns(3)
                        	->collapsible()
                            ->schema([
                                Forms\Components\TextInput::make('numero')
                                    ->required()
                                    //->unique(ignoreRecord: true)
                                    ->label('Nº Nota'),

                                Forms\Components\TextInput::make('fornecedor')
                              		->required(),

                                Forms\Components\TextInput::make('cnpj')
                                    ->mask('99.999.999/9999-99')
                                    ->maxLength(18),

                                Forms\Components\TextInput::make('valor')
                                    ->required()
                                    ->numeric()
                                    ->prefix('R$'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'paga' => 'Paga',
                                        'cancelada' => 'Cancelada',
                                    ])
                                    ->required(),

                                MultiSelect::make('tiposFaturamento')
                                    ->label('Tipos de Faturamento')
                                    ->relationship('tiposFaturamento', 'nome')
                                    ->required(),
                            ]),

                        // Card: Datas
                        Forms\Components\Card::make('Datas')
                            ->columns(3)
                        	->collapsible()
                            ->schema([
                                Forms\Components\DatePicker::make('data_emissao')->label('Emissão'),
                                Forms\Components\DatePicker::make('data_recebimento')->label('Recebimento'),
                                Forms\Components\DatePicker::make('data_envio')->label('Envio'),
                            ]),

                        // Card: Upload
                        Forms\Components\Card::make('Anexos')
                        	->collapsible()
                            ->schema([
                                Forms\Components\FileUpload::make('arquivo')
                                    ->label('Anexo da Nota')
                                    ->directory('notas-fiscais')
                                    ->preserveFilenames()
                              		->multiple()
                                    ->acceptedFileTypes(['application/pdf', 'application/xml', 'text/xml'])
                                    ->downloadable()
                                    ->openable()
                                    ->maxSize(2048)
                                    ->helperText('Aceita PDF ou XML (máx. 2MB)'),
                            ]),

                        // Card: Observações + obra
                        Forms\Components\Card::make('Obra e Observações')
                            ->columns(2)
                        	->collapsible()
                            ->schema([
                                Forms\Components\Select::make('obra_id')
                                    ->label('Obra')
                                    ->relationship('obra', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                              
                                Forms\Components\RichEditor::make('observacoes')
                              		->label('Observações'),
                            ]),
                    ]),
              
              	Forms\Components\Section::make('Faturamentos Diretos')
                  ->visible(fn ($get) => in_array('Direto', $get('tiposFaturamento') ?? []))
                  ->schema([
                      Forms\Components\Repeater::make('faturamentos_direto')
                          ->label('Faturamentos Diretos')
                          ->relationship('faturamentosDireto')
                          ->collapsible()
                          ->schema([
                              Forms\Components\Card::make('Dados da Empresa e Valor')
                                  ->columns(2)
                                  ->schema([
                                      Forms\Components\Select::make('tipo')
                                          ->options(['direto' => 'Direto', 'indireto' => 'Indireto'])
                                          ->required(),

                                      Forms\Components\TextInput::make('empresa')->required(),

                                      Forms\Components\TextInput::make('numero_nf')->required(),

                                      Forms\Components\TextInput::make('cnpj_faturamento_smart')->nullable(),

                                      Forms\Components\TextInput::make('valor_acumulado_medido_nf')
                                          ->numeric()
                                          ->required(),
                                  ]),

                              Forms\Components\Card::make('Datas da Nota Fiscal')
                                  ->columns(3)
                                  ->schema([
                                      Forms\Components\DatePicker::make('emissao')->required(),
                                      Forms\Components\DatePicker::make('recebimento')->nullable(),
                                      Forms\Components\DatePicker::make('envio')->nullable(),
                                  ]),

                              Forms\Components\Card::make('Status e Observações')
                                  ->columns(2)
                                  ->schema([
                                      Forms\Components\Select::make('status')
                                          ->options([
                                              'aprovado' => 'Aprovado',
                                              'em_analise' => 'Em Análise',
                                              'reprovada' => 'Reprovada',
                                          ])
                                          ->required(),

                                      Forms\Components\RichEditor::make('observacoes')->nullable(),
                                  ]),
                          ]),
                  ]),

              Forms\Components\Section::make('Faturamentos Indiretos')
                ->visible(fn ($get) => in_array('Indireto', $get('tiposFaturamento') ?? []))
                ->schema([
                    Forms\Components\Repeater::make('faturamentos_indireto')
                        ->label('Faturamentos Indiretos')
                        ->relationship('faturamentosIndireto')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Card::make('Dados da Empresa e Valor')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('tipo')
                                        ->options(['direto' => 'Direto', 'indireto' => 'Indireto'])
                                        ->required(),

                                    Forms\Components\TextInput::make('empresa')->required(),

                                    Forms\Components\TextInput::make('numero_nf')->required(),

                                    Forms\Components\TextInput::make('cnpj_faturamento_smart')->nullable(),

                                    Forms\Components\TextInput::make('valor_acumulado_medido_nf')
                                        ->numeric()
                                        ->required(),
                                ]),

                            Forms\Components\Card::make('Datas da Nota Fiscal')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('emissao')->required(),
                                    Forms\Components\DatePicker::make('recebimento')->nullable(),
                                    Forms\Components\DatePicker::make('envio')->nullable(),
                                ]),

                            Forms\Components\Card::make('Status e Observações')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->options([
                                              'aprovado' => 'Aprovado',
                                              'em_analise' => 'Em Análise',
                                              'reprovada' => 'Reprovada',
                                        ])
                                        ->required(),

                                    Forms\Components\RichEditor::make('observacoes')->nullable(),
                                ]),
                        ]),
                ]),
				/*
                Forms\Components\Section::make('Faturamentos')
                    ->schema([
                        Forms\Components\Repeater::make('faturamentos')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->options(['direto' => 'Direto', 'indireto' => 'Indireto'])
                                    ->required(),

                                Forms\Components\TextInput::make('empresa')->required(),

                                Forms\Components\TextInput::make('numero_nf')->required(),

                                Forms\Components\TextInput::make('cnpj_faturamento_smart')->nullable(),

                                Forms\Components\TextInput::make('valor_acumulado_medido_nf')
                                    ->numeric()
                                    // ->mask(fn ($mask) => $mask->money(prefix: 'R$ '))
                                    ->required(),

                                Forms\Components\DatePicker::make('emissao')->required(),

                                Forms\Components\DatePicker::make('recebimento')->nullable(),

                                Forms\Components\DatePicker::make('envio')->nullable(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'APROVADO' => 'APROVADO',
                                        'PENDENTE' => 'PENDENTE',
                                        'REJEITADO' => 'REJEITADO',
                                    ])
                                    ->required(),

                                Forms\Components\Textarea::make('observacoes')->nullable(),
                            ])
                            ->createItemButtonLabel('Adicionar Faturamento')
                            ->collapsible(),
                    ]),
                    */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('Nº NF')->searchable(),
                Tables\Columns\TextColumn::make('fornecedor')->searchable(),
                Tables\Columns\TextColumn::make('cnpj'),
                Tables\Columns\TextColumn::make('valor')->money('BRL'),
                Tables\Columns\TextColumn::make('data_emissao')->date(),
                Tables\Columns\TextColumn::make('obra.nome')->label('Obra'),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'pendente',
                    'success' => 'paga',
                    'danger' => 'cancelada',
                ]),
              	Tables\Columns\IconColumn::make('arquivo')
                  ->label('Anexo')
                  ->icon(fn ($state) => $state ? 'heroicon-o-paper-clip' : 'heroicon-o-x-mark')
                  ->tooltip(fn ($state) => $state ? 'Clique para baixar' : 'Nenhum anexo')
                  ->url(fn ($record) => $record->arquivo ? Storage::url($record->arquivo) : null, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListNotaFiscals::route('/'),
            'create' => Pages\CreateNotaFiscal::route('/create'),
            'edit' => Pages\EditNotaFiscal::route('/{record}/edit'),
        ];
    }
}
