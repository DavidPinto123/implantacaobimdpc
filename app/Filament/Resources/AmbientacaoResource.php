<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbientacaoResource\Pages;
use App\Filament\Resources\AmbientacaoResource\RelationManagers\ImagensRelationManager;
use App\Models\Ambientacao;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class AmbientacaoResource extends Resource
{
    protected static ?string $model = Ambientacao::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Ambientação';

    protected static ?string $modelLabel = 'Ambientação';

    protected static ?string $slug = 'ambientacoes';

    protected static ?int $navigationSort = 6;

    protected static string|null|UnitEnum $navigationGroup = null;

    protected static ?string $pluralModelLabel = 'Ambientação';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ambiente')
                    ->description('Localização e ambiente representados pelo render')
                    ->schema([
                        Forms\Components\TextInput::make('bloco_torre')
                            ->label('Bloco/Torre')
                            ->placeholder('Ex: Torre A, Bloco 1')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pavimento')
                            ->label('Pavimento')
                            ->placeholder('Ex: Térreo, 1º Pavimento')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('departamento')
                            ->label('Departamento')
                            ->placeholder('Ex: Musculação, Vestiário')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('ambiente')
                            ->label('Ambiente')
                            ->placeholder('Ex: Recepção, Sala de Musculação')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Render 360°')
                    ->description('Link da página de renderização 360° gerada na Autodesk (ex: pano.autodesk.com/...)')
                    ->schema([
                        Forms\Components\TextInput::make('link_render')
                            ->label('Link do Render 360°')
                            ->url()
                            ->required()
                            ->live(onBlur: true)
                            ->placeholder('https://pano.autodesk.com/pano.html?...'),
                    ]),

                Section::make('Pré-visualização')
                    ->description('Amostra do Render 360° direto na tela, com opção de tela cheia')
                    ->schema([
                        View::make('filament.components.ambientacao-pano-preview')
                            ->viewData(fn (Get $get) => ['url' => $get('link_render')]),
                    ]),

                Section::make('Imagem 360° (equirretangular)')
                    ->description('Arquivo-fonte do panorama (baixado da Autodesk Rendering), usado para escolher um ângulo e gerar um recorte estático')
                    ->schema([
                        FileUpload::make('pano_equirretangular')
                            ->label('Imagem equirretangular')
                            ->image()
                            ->disk((string) config('filesystems.media_disk', 'r2'))
                            ->directory(fn ($record) => filled($record?->id)
                                ? "ambientacoes/{$record->id}/pano"
                                : 'ambientacoes/tmp/pano')
                            ->visibility('public')
                            ->fetchFileInformation(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('ambiente')
                            ->searchable()
                            ->weight(FontWeight::Bold)
                            ->size('sm')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0'])
                            ->limit(22),
                        TextColumn::make('pavimento')
                            ->searchable()
                            ->size('xs')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0'])
                            ->limit(16),
                        TextColumn::make('bloco_torre')
                            ->label('Bloco/Torre')
                            ->size('xs')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0'])
                            ->limit(16),
                    ])->extraAttributes(['class' => 'flex-nowrap gap-1 overflow-hidden']),

                    Split::make([
                        TextColumn::make('nome')
                            ->searchable()
                            ->label('Unidade')
                            ->size('xs')
                            ->color('gray')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0'])
                            ->limit(16),
                        TextColumn::make('departamento')
                            ->size('xs')
                            ->color('gray')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0'])
                            ->limit(16),
                        TextColumn::make('codigo')
                            ->label('Código')
                            ->size('xs')
                            ->color('gray')
                            ->grow(false)
                            ->extraAttributes(['class' => 'truncate min-w-0']),
                    ])->extraAttributes(['class' => 'flex-nowrap gap-1 overflow-hidden']),
                ])->space(1),

                Split::make([
                    ViewColumn::make('preview')
                        ->label('Pré-visualização')
                        ->view('filament.components.ambientacao-pano-preview')
                        ->viewData(fn ($record) => ['url' => $record->link_render, 'height' => 120]),

                    ViewColumn::make('imagem_destaque')
                        ->label('Imagem estática')
                        ->view('filament.components.ambientacao-imagem-destaque')
                        ->viewData(function ($record) {
                            $imagem = $record->imagens->sortByDesc('created_at')->first();

                            return [
                                'url' => $imagem
                                    ? Storage::disk((string) config('filesystems.media_disk', 'r2'))->url($imagem->arquivo)
                                    : null,
                                'height' => 120,
                            ];
                        }),
                ])->from('lg')->extraAttributes(['class' => 'flex-wrap gap-1']),

                ViewColumn::make('comentarios')
                    ->label('Comentários')
                    ->view('filament.components.ambientacao-comentarios-feed')
                    ->viewData(fn ($record) => ['ambientacao' => $record]),
            ])->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn () => null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['imagens.comentarios.autor']))
            ->contentGrid([
                'sm' => 2,
                'md' => 4,
            ])
            ->filters([
                SelectFilter::make('bloco_torre')
                    ->label('Bloco/Torre')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('bloco_torre')->orderBy('bloco_torre')->pluck('bloco_torre', 'bloco_torre')->toArray()),

                SelectFilter::make('pavimento')
                    ->label('Pavimento')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('pavimento')->orderBy('pavimento')->pluck('pavimento', 'pavimento')->toArray()),

                SelectFilter::make('departamento')
                    ->label('Departamento')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('departamento')->orderBy('departamento')->pluck('departamento', 'departamento')->toArray()),

                SelectFilter::make('ambiente')
                    ->label('Ambiente')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Ambientacao::whereNotNull('ambiente')->orderBy('ambiente')->pluck('ambiente', 'ambiente')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmbientacaos::route('/'),
            'create' => Pages\CreateAmbientacao::route('/create'),
            'view' => Pages\ViewAmbientacao::route('/{record}'),
            'edit' => Pages\EditAmbientacao::route('/{record}/edit'),
            'angulo' => Pages\SelecionarAngulo::route('/{record}/angulo'),
        ];
    }
}
