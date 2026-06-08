<?php

namespace App\Filament\Resources\AsFaixaAreas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AsFaixaAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome da Faixa')
                    ->required()
                    ->maxLength(255),

                TextInput::make('area_min')
                    ->label('Área Mínima (m²)')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                TextInput::make('area_max')
                    ->label('Área Máxima (m²)')
                    ->numeric()
                    ->nullable()
                    ->gt('area_min')
                    ->helperText('Deixe vazio para faixa aberta (ex: acima de 500 m²).'),
            ]);
    }
}
