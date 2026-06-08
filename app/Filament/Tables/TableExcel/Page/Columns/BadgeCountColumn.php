<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

class BadgeCountColumn extends Column
{
    public string $color = 'danger';

    public bool $hideWhenZero = false;

    public string $placeholder = '0';

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function hideWhenZero(bool $value = true): static
    {
        $this->hideWhenZero = $value;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getType(): string
    {
        return 'badge-count';
    }

    public function formatValue(mixed $state): string
    {
        if ($state === null || $state === '') {
            return $this->placeholder;
        }

        return (string) (int) $state;
    }
}
