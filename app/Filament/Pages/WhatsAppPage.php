<?php

namespace App\Filament\Pages;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class WhatsAppPage extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuWhatsApp';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?string $title = 'WhatsApp';
    protected static ?string $slug = 'whatsapp';
    protected string $view = 'filament.pages.em-construcao';
}
