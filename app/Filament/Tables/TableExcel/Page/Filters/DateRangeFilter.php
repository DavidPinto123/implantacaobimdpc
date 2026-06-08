<?php

namespace App\Filament\Tables\TableExcel\Page\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtro de intervalo de data. O estado esperado em `$filtros[key]` é
 * um array `['from' => 'Y-m-d', 'until' => 'Y-m-d']`.
 */
class DateRangeFilter extends Filter
{
    public ?string $column = null;

    /** @var null|Closure(Builder, ?string, ?string): Builder */
    public ?Closure $queryUsing = null;

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function queryUsing(Closure $callback): static
    {
        $this->queryUsing = $callback;

        return $this;
    }

    public function getType(): string
    {
        return 'date_range';
    }

    public function isEmptyValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return true;
        }

        $from = trim((string) ($value['from'] ?? ''));
        $until = trim((string) ($value['until'] ?? ''));

        return $from === '' && $until === '';
    }

    protected function applyDefault(Builder $query, mixed $value): Builder
    {
        if (! is_array($value)) {
            return $query;
        }

        $from = trim((string) ($value['from'] ?? '')) ?: null;
        $until = trim((string) ($value['until'] ?? '')) ?: null;

        if ($this->queryUsing !== null) {
            return ($this->queryUsing)($query, $from, $until) ?? $query;
        }

        $column = $this->column ?? $this->key;

        return $query
            ->when($from, fn (Builder $q, $date) => $q->whereDate($column, '>=', $date))
            ->when($until, fn (Builder $q, $date) => $q->whereDate($column, '<=', $date));
    }
}
