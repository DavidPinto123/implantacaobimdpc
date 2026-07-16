<?php

namespace App\Filament\Resources\AmbientacaoResource\RelationManagers;

use App\Filament\Resources\AmbientacaoResource\Pages\SelecionarAngulo;
use App\Models\AmbientacaoImagem;
use App\Services\AiRenderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagensRelationManager extends RelationManager
{
    protected static string $relationship = 'imagens';

    protected static ?string $title = 'Imagens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Imagem')
                    ->image()
                    ->required()
                    ->disk((string) config('filesystems.media_disk', 'r2'))
                    ->directory(fn () => "ambientacoes/{$this->getOwnerRecord()->id}/imagens")
                    ->visibility('public')
                    ->fetchFileInformation(false),

                Textarea::make('legenda')
                    ->label('Legenda')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('legenda')
            ->columns([
                ImageColumn::make('arquivo')
                    ->label('Imagem')
                    ->disk((string) config('filesystems.media_disk', 'r2'))
                    ->square(),
                TextColumn::make('legenda')
                    ->label('Legenda')
                    ->limit(40),
                TextColumn::make('origem')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'recorte_360' => 'Recorte do 360°',
                        'ia_gerada' => 'Gerada por IA',
                        default => 'Upload manual',
                    }),
                TextColumn::make('uploadedBy.name')
                    ->label('Enviado por'),
                TextColumn::make('created_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('comentarios_count')
                    ->label('Comentários')
                    ->counts('comentarios'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Adicionar imagem')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['origem'] = 'upload';
                        $data['uploaded_by'] = auth()->id();

                        return $data;
                    }),

                Action::make('selecionarAngulo')
                    ->label('Selecionar ângulo (360°)')
                    ->icon('heroicon-o-view-columns')
                    ->color('gray')
                    ->visible(fn () => filled($this->getOwnerRecord()->pano_equirretangular))
                    ->url(fn () => SelecionarAngulo::getUrl(['record' => $this->getOwnerRecord()])),
            ])
            ->actions([
                Action::make('gerarComIa')
                    ->label('Gerar com IA')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->visible(fn (AmbientacaoImagem $record) => $record->origem === 'recorte_360')
                    ->action(function (AmbientacaoImagem $record) {
                        try {
                            app(AiRenderService::class)->generate($record);

                            Notification::make()
                                ->title('Imagem gerada com sucesso')
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Não foi possível gerar a imagem')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
                Action::make('comentarios')
                    ->label('Comentários')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->modalHeading('Comentários da imagem')
                    ->modalContent(fn (AmbientacaoImagem $record) => view(
                        'filament.components.ambientacao-imagem-comentarios',
                        ['imagem' => $record->load('comentarios.autor')]
                    ))
                    ->schema([
                        Textarea::make('comentario')
                            ->label('Novo comentário')
                            ->required()
                            ->rows(3),
                    ])
                    ->modalSubmitActionLabel('Adicionar comentário')
                    ->action(function (AmbientacaoImagem $record, array $data) {
                        $record->comentarios()->create([
                            'comentario' => $data['comentario'],
                            'user_id' => auth()->id(),
                        ]);
                    }),
                EditAction::make()
                    ->schema([
                        Textarea::make('legenda')
                            ->label('Legenda')
                            ->rows(2),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
