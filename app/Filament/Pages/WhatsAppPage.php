<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class WhatsAppPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static UnitEnum|string|null $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?string $title = 'WhatsApp';
    protected static ?string $slug = 'whatsapp';
    protected string $view = 'filament.pages.em-construcao';
}
