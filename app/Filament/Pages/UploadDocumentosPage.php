<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class UploadDocumentosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Upload de documentos';
    protected static ?string $navigationLabel = 'Upload de documentos';
    protected static ?string $title = 'Upload de documentos';
    protected static ?string $slug = 'upload-documentos';
    protected string $view = 'filament.pages.em-construcao';
}
