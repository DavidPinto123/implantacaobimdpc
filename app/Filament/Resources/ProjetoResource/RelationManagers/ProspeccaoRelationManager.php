<?php

namespace App\Filament\Resources\ProjetoResource\RelationManagers;

use App\Models\Etapa;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProspeccaoRelationManager extends RelationManager
{
    protected static string $relationship = 'prospeccoes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informações do Ponto')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome do ponto')
                            ->placeholder('Digite o nome do ponto')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sigla')
                            ->placeholder('Digite a sigla do ponto')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Status da Prospecção')
                            ->options([
                                'Novo' => 'Novo',
                                'Em análise' => 'Em análise',
                                'Entregue' => 'Entregue',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->native(false),
                        Forms\Components\Select::make('tipo_entrada')
                            ->label('Tipo de Entrada')
                            ->options([
                                'Prospecção de Rua' => 'Prospecção de Rua',
                                'Email' => 'Email',
                                'Propietário' => 'Propietário',
                                'Indicação Interna' => 'Indicação Interna',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('nome_contato')
                            ->placeholder('Digite o nome do contato')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contato')
                            ->label('Telefone do contato')
                            ->mask('(99) 99999-9999')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('pin_google')
                            ->label('Pin do Google Maps')
                            ->url()
                            ->columnSpanFull(),
                    ])->columns(3)
                    ->grow(),
                Section::make('Características técnicas')
                    ->schema([
                        Forms\Components\TextInput::make('Tipo de loja')
                            ->placeholder('Digite o tipo de loja')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('Nº de vagas livres')
                            ->placeholder('Digite o nº de vagas livres')
                            ->maxLength(255)
                            ->numeric(),
                        Forms\Components\TextInput::make('Área da academia')
                            ->placeholder('Digite a área da academia')
                            ->maxLength(255)
                            ->step(0.01)
                            ->suffix('m²')
                            ->numeric(),
                        Forms\Components\TextInput::make('Área do terreno')
                            ->placeholder('Digite a área do terreno')
                            ->maxLength(255)
                            ->suffix('m²')
                            ->numeric(),
                        Forms\Components\TextInput::make('n_pisos')
                            ->label('Nº de pisos')
                            ->placeholder('Digite o nº de pisos')
                            ->maxLength(255)
                            ->numeric(),
                        Forms\Components\TextInput::make('pe_direito')
                            ->label('Pé-direito')
                            ->placeholder('Digite o pé-direito')
                            ->maxLength(255)
                            ->suffix('m')
                            ->numeric(),
                        Forms\Components\TextInput::make('modelo_entrega_pp')
                            ->label('Modelo de entrega de PP')
                            ->placeholder('Digite o modelo e entrega')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('aluguel_CTO')
                            ->label('Aluguel/CTO')
                            ->placeholder('Digite o aluguel/cto')
                            ->maxLength(255)
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('luvas')
                            ->placeholder('Digite o valor da luva')
                            ->maxLength(255)
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('iptu')
                            ->label('IPTU')
                            ->placeholder('Digite o valor do IPTU')
                            ->maxLength(255)
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('condominio')
                            ->label('Condomínio')
                            ->placeholder('Digite o valor do condomínio')
                            ->maxLength(255)
                            ->prefix('R$'),
                        Forms\Components\Textarea::make('configuracao_academia')
                            ->label('Configuração da Academia')
                            ->placeholder('Digite as informações a respeito das configurações da academia')
                            ->maxLength(255)
                            ->rows(5)->columnSpanFull(),
                        Forms\Components\Textarea::make('dados_engenharia')
                            ->label('Dados da Engenharia')
                            ->placeholder('Digite as informações a respeito dos dados de Engenharia')
                            ->maxLength(255)
                            ->rows(5)->columnSpanFull(),
                        Forms\Components\DatePicker::make('prazo_inicio')
                            ->label('Prazo para início de obra'),
                        Forms\Components\Toggle::make('projeto_croqui')
                            ->label('Projeto/Croqui')
                            ->columnSpan(3),
                    ])->columns(4)
                    ->grow(),
                Section::make('Estudo de projetos de alunos')
                    ->schema([
                        Forms\Components\TextInput::make('potencial_alunos')
                            ->label('Potencial final de alunos')
                            ->placeholder('Digite o nº de alunos')
                            ->maxLength(255)->columnSpan(2),
                        Forms\Components\TextInput::make('link_estudo_projecao_alunos')
                            ->label('Link para estudo de projeção de alunos')
                            ->url()
                            ->columnSpan(2),
                    ])->columns(4)
                    ->grow(),
            ])
            ->disabled(fn ($get) => Etapa::find($get('etapa_id'))?->nome !== 'Prospecção');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Prospeccao')
            ->columns([
                Tables\Columns\TextColumn::make('nome'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Prospecções'; // ou o título que desejar
    }
}
