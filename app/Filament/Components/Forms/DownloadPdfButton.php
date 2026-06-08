<?php

namespace App\Filament\Components\Forms;

use Filament\Forms\Components\ViewField;

class DownloadPdfButton extends ViewField
{
    protected string $view = 'forms.components.download-pdf-button';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false); // não salva no banco
        $this->columnSpanFull();  // ocupa a linha toda no form
    }
}
