<?php

namespace App\Filament\Pages;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class UploadDocumentosPage extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuUpload';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = 'Upload de documentos';
    protected static ?string $title = 'Upload de documentos';
    protected static ?string $slug = 'upload-documentos';
    protected string $view = 'filament.pages.em-construcao';
}
