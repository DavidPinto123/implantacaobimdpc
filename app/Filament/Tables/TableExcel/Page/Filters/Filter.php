<?php

namespace App\Filament\Tables\TableExcel\Page\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    public string $key;

    public string $label;

    public mixed $default = null;

    public ?string $group = null;

    public bool $secondary = false;

    public ?Closure $applyUsing = null;

    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public static function make(string $key, string $label): static
    {
        return new static($key, $label);
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function secondary(bool $value = true): static
    {
        $this->secondary = $value;

        return $this;
    }

    public function group(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Permite sobrescrever a aplicação padrão do filtro à query.
     * Recebe ($query, $value) e retorna o Builder modificado.
     */
    public function applyUsing(Closure $callback): static
    {
        $this->applyUsing = $callback;

        return $this;
    }

    /**
     * Tipo usado no @switch do Blade da barra de filtros.
     */
    abstract public function getType(): string;

    /**
     * Aplica o filtro à query. Se $value for "vazio" (null, [], ''),
     * a implementação padrão deve ser no-op.
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if ($this->applyUsing !== null) {
            return ($this->applyUsing)($query, $value) ?? $query;
        }

        return $this->applyDefault($query, $value);
    }

    abstract protected function applyDefault(Builder $query, mixed $value): Builder;

    public function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] || $value === '0';
    }
}
