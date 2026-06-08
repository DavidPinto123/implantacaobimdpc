<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

/**
 * Coluna de seleção (checkbox por linha) para o modo Page do Table Excel.
 * O comportamento (qual está selecionada, handler de toggle) é decidido
 * pela view que renderiza a coluna — ela apenas declara o tipo.
 */
class SelectColumn extends Column
{
    public function getType(): string
    {
        return 'select';
    }
}
