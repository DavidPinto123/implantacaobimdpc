<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

class DurationColumn extends Column
{
    public string $unitSingular = 'dia';

    public string $unitPlural = 'dias';

    public string $placeholder = '—';

    public function units(string $singular, string $plural): static
    {
        $this->unitSingular = $singular;
        $this->unitPlural = $plural;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getType(): string
    {
        return 'duration';
    }

    public function formatValue(mixed $state): string
    {
        if ($state === null || $state === '') {
            return $this->placeholder;
        }

        $n = (int) $state;

        if ($n === 0) {
            return $this->placeholder;
        }

        return $n.' '.($n === 1 ? $this->unitSingular : $this->unitPlural);
    }
}
