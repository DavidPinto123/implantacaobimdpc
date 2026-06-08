<?php

namespace App\Filament\Tables\TableExcel\Page\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends Filter
{
    /** @var array<string|int, string>|Closure */
    public array|Closure $options = [];

    public bool $multiple = false;

    public ?string $placeholder = null;

    /**
     * @param  array<string|int, string>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool $value = true): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return array<string|int, string>
     */
    public function resolveOptions(): array
    {
        $options = $this->options;

        if ($options instanceof Closure) {
            $options = ($options)();
        }

        return (array) $options;
    }

    public function getType(): string
    {
        return 'select';
    }

    protected function applyDefault(Builder $query, mixed $value): Builder
    {
        if ($this->isEmptyValue($value)) {
            return $query;
        }

        $values = $this->multiple ? (array) $value : [$value];
        $values = array_values(array_filter(
            $values,
            fn ($v): bool => $v !== null && $v !== '',
        ));

        if ($values === []) {
            return $query;
        }

        return $query->whereIn($this->key, $values);
    }
}
