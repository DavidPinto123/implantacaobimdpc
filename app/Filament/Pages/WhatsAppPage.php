<?php

namespace App\Filament\Pages;

use App\Models\PosObra\MensagemWhatsapp;
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
    protected static UnitEnum|string|null $navigationGroup = 'WhatsApp';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard WhatsApp';
    protected static ?string $slug = 'whatsapp';
    protected string $view = 'filament.pages.whatsapp-dashboard';

    public function getViewData(): array
    {
        $mensagens = MensagemWhatsapp::latest()->limit(50)->get();

        $total     = MensagemWhatsapp::count();
        $enviadas  = MensagemWhatsapp::where('direcao', 'ENVIADA')->count();
        $recebidas = MensagemWhatsapp::where('direcao', 'RECEBIDA')->count();
        $falhas    = MensagemWhatsapp::where('status_entrega', 'FALHA')->count();
        $entregues = MensagemWhatsapp::whereIn('status_entrega', ['ENTREGUE', 'LIDA'])->count();
        $taxa      = $enviadas > 0 ? round(($entregues / $enviadas) * 100) : 0;

        $stats = [
            ['label' => 'Total de mensagens', 'value' => $total,    'sub' => 'desde o início',          'color' => 'var(--fi-color-primary-500, #6366f1)'],
            ['label' => 'Enviadas',            'value' => $enviadas, 'sub' => 'pelo sistema',             'color' => '#3b82f6'],
            ['label' => 'Recebidas',           'value' => $recebidas,'sub' => 'dos usuários',             'color' => '#10b981'],
            ['label' => 'Taxa de entrega',     'value' => $taxa.'%', 'sub' => "{$falhas} falha(s)",       'color' => $taxa >= 80 ? '#10b981' : '#f59e0b'],
        ];

        return compact('mensagens', 'stats');
    }
}
