<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

use Carbon\Carbon;
use DateTimeInterface;

class DateColumn extends Column
{
    public string $format = 'd/m/Y';

    public string $placeholder = '—';

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getType(): string
    {
        return 'date';
    }

    public function formatValue(mixed $state): string
    {
        if (blank($state)) {
            return $this->placeholder;
        }

        if ($state instanceof DateTimeInterface) {
            return $state->format($this->format);
        }

        try {
            return Carbon::parse((string) $state)->format($this->format);
        } catch (\Throwable) {
            return (string) $state;
        }
    }
}
