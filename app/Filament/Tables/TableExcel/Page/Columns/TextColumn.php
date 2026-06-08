<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

class TextColumn extends Column
{
    public bool $compact = false;

    public bool $monospace = false;

    public bool $muted = false;

    public function compact(bool $value = true): static
    {
        $this->compact = $value;

        return $this;
    }

    public function monospace(bool $value = true): static
    {
        $this->monospace = $value;

        return $this;
    }

    public function muted(bool $value = true): static
    {
        $this->muted = $value;

        return $this;
    }

    public function getType(): string
    {
        return 'text';
    }
}
