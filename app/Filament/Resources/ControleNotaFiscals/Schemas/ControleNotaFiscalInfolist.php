<?php

namespace App\Filament\Resources\ControleNotaFiscals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ControleNotaFiscalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->schema([
                        TextEntry::make('obra.unidade')
                            ->label('Unidade'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('data_base')
                            ->label('Data base')
                            ->date('d/m/Y'),
                    ])
                    ->columns(2),
            ]);
    }
}
