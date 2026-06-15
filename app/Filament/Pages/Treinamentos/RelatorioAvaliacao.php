<?php

namespace App\Filament\Pages\Treinamentos;

use Filament\Pages\Page;
use UnitEnum;

class RelatorioAvaliacao extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'Relatório resumo de avaliação de treinamentos';
    protected static ?string $title = 'Relatório resumo de avaliação de treinamentos';
    protected static ?string $slug = 'treinamentos-relatorio-avaliacao';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.em-construcao';
}
