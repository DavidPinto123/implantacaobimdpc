<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ApsDocsController;
use App\Models\Projeto;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use UnitEnum;

class Viewer3D extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.viewer3-d';

    // public string $modelUrn = 'dXJuOmFkc2sud2lwcHJvZDpmcy5maWxlOnZmLm1pRm8wN1RFUUwyWXUzSms0MkVsRkE_dmVyc2lvbj0x';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Visualizador 3D';

    protected static ?string $title = 'Visualizador 3D';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 25;

    public Projeto $projeto;

    public string $modelUrn = '';

    public function mount(Projeto $projeto, ApsDocsController $apsDocs)
    {
        $this->projeto = $projeto;

        $prefix = $projeto->nova_sigla;
        $projId = $projeto->acc_project_id; // ajuste para o seu campo
        $folderId = $projeto->acc_folder_id;  // ajuste para o seu campo

        $data = $apsDocs->firstRvtByPrefix($projId, $folderId, $prefix);

        if (is_array($data) && isset($data['modelUrn'])) {
            $this->modelUrn = $data['modelUrn'];
        } else {
            // aqui você pode jogar uma notificação ou redirecionar
            $this->modelUrn = ''; // sem URN -> viewer não carrega
        }
    }
}
