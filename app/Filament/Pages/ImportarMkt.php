<?php

namespace App\Filament\Pages;

use App\Services\ImportadorMktService;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportarMkt extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'PMO';

    protected static ?string $navigationLabel = 'Importar Datas MKT';

    protected static ?string $title = 'Importar Datas de Pré-Vendas (MKT)';

    protected static ?string $slug = 'importar-mkt';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.importar-mkt';

    public ?array $data = [];

    public ?Collection $preview = null;

    public array $decisoes = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('View:ImportarMkt') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                FileUpload::make('arquivo')
                    ->label('Planilha (.csv)')
                    ->helperText('Formato: código_unidade,data_pre_venda_fisico,data_pre_venda_online (cabeçalho opcional).')
                    ->disk('local')
                    ->directory('importacoes/mkt')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function gerarPreview(): void
    {
        $state = $this->form->getState();
        $arquivo = $state['arquivo'] ?? null;
        if (! $arquivo) {
            Notification::make()->title('Envie um arquivo CSV')->warning()->send();

            return;
        }

        $caminhoAbsoluto = Storage::disk('local')->path($arquivo);
        $this->preview = app(ImportadorMktService::class)->preview($caminhoAbsoluto);
        $this->decisoes = [];

        Notification::make()
            ->title('Preview gerado')
            ->body($this->preview->count().' linha(s) processada(s).')
            ->success()
            ->send();
    }

    public function aplicarImportacao(): void
    {
        if (! $this->preview) {
            Notification::make()->title('Gere o preview primeiro')->warning()->send();

            return;
        }

        $result = app(ImportadorMktService::class)->aplicar($this->preview, $this->decisoes);

        Notification::make()
            ->title('Importação concluída')
            ->body("Aplicados: {$result['aplicados']} · Ignorados: {$result['ignorados']} · Erros: {$result['erros']}")
            ->success()
            ->send();

        $this->preview = null;
        $this->decisoes = [];
        $this->form->fill();
    }
}
