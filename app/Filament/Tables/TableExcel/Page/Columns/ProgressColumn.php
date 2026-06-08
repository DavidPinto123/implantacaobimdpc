<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

class ProgressColumn extends Column
{
    public ?Closure $percentageUsing = null;

    public ?Closure $colorUsing = null;

    public string $defaultColor = 'info';

    public function percentageUsing(Closure $callback): static
    {
        $this->percentageUsing = $callback;

        return $this;
    }

    public function colorUsing(Closure $callback): static
    {
        $this->colorUsing = $callback;

        return $this;
    }

    public function defaultColor(string $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    public function getType(): string
    {
        return 'progress';
    }

    public function resolvePercentage(Model $record): int
    {
        $raw = $this->percentageUsing !== null
            ? ($this->percentageUsing)($record)
            : $this->resolveState($record);

        $n = (int) round((float) $raw);

        return max(0, min(100, $n));
    }

    public function resolveColor(Model $record): string
    {
        if ($this->colorUsing !== null) {
            return (string) ($this->colorUsing)($record);
        }

        return $this->defaultColor;
    }
}
