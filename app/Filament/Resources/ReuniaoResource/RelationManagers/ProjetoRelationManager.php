<?php

namespace App\Filament\Resources\ReuniaoResource\RelationManagers;

use App\Models\Projeto;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjetoRelationManager extends RelationManager
{
    protected static string $relationship = 'projetos'; // método no model Reuniao

    protected static ?string $title = 'Projetos Relacionados';

    protected static ?string $recordTitleAttribute = 'nome';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('status')
                    ->label('Status na Reunião')
                    ->required(),

                TextInput::make('corretor')
                    ->label('Corretor')
                    ->default(fn (RelationManager $livewire) => Filament::auth()->user()?->name ?? 'Usuário Desconhecido')
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                // Dados da tabela de projetos
                TextColumn::make('nova_sigla')->label('Sigla'),
                TextColumn::make('nome')->label('Nome do Projeto'),
                TextColumn::make('rua')->label('Endereço'),
                TextColumn::make('estado.nome')->label('UF'),

                // Dados do responsável (user relacionado ao projeto)
                TextColumn::make('responsavel.name')->label('Responsável'),

                // Dados da tabela prospeccao
                TextColumn::make('prospeccao.potencial_alunos')->label('Potencial de Alunos'),

                // Campos da tabela pivot
                TextColumn::make('pivot.status')->label('Status na Reunião'),
                TextColumn::make('pivot.corretor')->label('Corretor'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('status')
                            ->label('Status na Reunião')
                            ->required(),
                        TextInput::make('corretor')
                            ->label('Corretor')
                            ->default(fn () => auth()->user()?->name ?? 'Usuário Desconhecido')
                            ->disabled()
                            ->dehydrated(true)
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),   // Para editar os dados da pivot
                Tables\Actions\DetachAction::make(), // Para remover a associação
            ])
            ->paginated(false);
    }
    /*
    protected function getTableQuery(): Builder|BelongsToMany
    {
        // Garante que a relação carregue os dados relacionados corretamente
        return parent::getTableQuery()->with(['responsavel', 'prospeccao']);
    }
    */
}
