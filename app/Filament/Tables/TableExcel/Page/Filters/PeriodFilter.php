<?php

namespace App\Filament\Tables\TableExcel\Page\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PeriodFilter extends Filter
{
    public string $column;

    /** @var array<string, string> */
    public array $options = [
        '' => 'Todo o Período',
        'ultimos_30_dias' => 'Últimos 30 dias',
        'este_mes' => 'Este mês',
        'este_trimestre' => 'Este trimestre',
        'este_ano' => 'Este ano',
    ];

    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->column = $key;
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getType(): string
    {
        return 'period';
    }

    protected function applyDefault(Builder $query, mixed $value): Builder
    {
        if ($this->isEmptyValue($value)) {
            return $query;
        }

        [$start, $end] = $this->resolveRange((string) $value);

        if ($start === null || $end === null) {
            return $query;
        }

        return $query->whereBetween($this->column, [$start, $end]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveRange(string $value): array
    {
        $now = Carbon::now();

        return match ($value) {
            'ultimos_30_dias' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            'este_mes' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'este_trimestre' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()->endOfDay()],
            'este_ano' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };
    }
}
