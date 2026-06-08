<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReuniaoResource\Pages;
use App\Filament\Resources\ReuniaoResource\RelationManagers;
use App\Filament\Resources\ReuniaoResource\RelationManagers\ProjetoRelationManager;

use App\Models\Reuniao;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReuniaoResource extends Resource
{
    protected static ?string $model = Reuniao::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
  
  	protected static ?string $navigationLabel = 'Cadastro de Reuniões';
  
  	protected static ?string $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Reuniões';

    protected static ?string $slug = 'reunioes';

    protected static ?string $breadcrumb = 'Reuniões';

    protected static ?string $pluralModelLabel = 'Lista de Reuniões'; 
  
  	protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('titulo')
              		->required()
              		->columnSpan(2),
            	DatePicker::make('data')
              		->required(),
                TimePicker::make('hora')
              		->required(),
                Select::make('tipo')
                    ->options([
                        'online' => 'Online',
                        'presencial' => 'Presencial',
                    ])
                    ->required()
              		->columnSpan(2),
                TextInput::make('convidados')
              		->required()
              		->columnSpan(2),
                TextInput::make('link_video')
              		->url()
              		->label('Link para Vídeo Conferência')
              		->columnSpan(2),
                TextInput::make('local')
              		->label('Local')
              		->required()
              		->columnSpan(2),
              /*
              	// Seleção múltipla de projetos
                Select::make('projetos')
                    ->label('Projetos Relacionados')
                    ->relationship('projetos', 'nome') // campo que você quer exibir
                    ->multiple()
                    ->preload()
              		->columnSpan(2),
                    */
                Textarea::make('descricao')
              		->rows(4)
              		->columnSpanFull(),
            	
                ])
          	->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')
              		->searchable()
              		->sortable(),
                TextColumn::make('data')
              		->date(),
                TextColumn::make('hora')
              		->time(),
                TextColumn::make('tipo')
              		->badge(),
                TextColumn::make('projetos.nome')
              		->label('Projetos'),
              		//->limit(3),
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
            ProjetoRelationManager::class,
        ];
    }
	/*
  	public static function mutateFormDataBeforeSave(array $data): array
    {
        // Remove projetos do array para salvar depois na relação
        $this->projetosParaSincronizar = $data['projetos'] ?? [];
        unset($data['projetos']);
        return $data;
	}
	*/
	public static function afterSave($record, array $data): void
    {
        if (array_key_exists('projetos', $data)) {
            $record->projetos()->syncWithoutDetaching($data['projetos']);
        }
    }
  
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReuniaos::route('/'),
            'create' => Pages\CreateReuniao::route('/create'),
            'edit' => Pages\EditReuniao::route('/{record}/edit'),
        ];
    }
}
