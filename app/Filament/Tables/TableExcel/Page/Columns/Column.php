<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Coluna base do modo Page do Table Excel.
 *
 * Cada subclasse representa um tipo visual (texto, pill, progresso, etc.)
 * e é renderizada pela partial Blade correspondente em
 * resources/views/filament/table-excel/page/columns/.
 */
abstract class Column
{
    public string $key;

    public string $label;

    public string $align = 'start';

    public ?Closure $getStateUsing = null;

    public ?Closure $onEditUsing = null;

    public ?Closure $authorizeEditUsing = null;

    public ?string $cellClass = null;

    public ?string $headerClass = null;

    public bool $sortable = false;

    public ?string $sortColumn = null;

    public ?string $group = null;

    public bool $toggleable = true;

    public bool $hiddenByDefault = false;

    public bool $reorderable = true;

    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public function group(?string $label): static
    {
        $this->group = $label;

        return $this;
    }

    public function toggleable(bool $value = true): static
    {
        $this->toggleable = $value;

        return $this;
    }

    public function hiddenByDefault(bool $value = true): static
    {
        $this->hiddenByDefault = $value;

        return $this;
    }

    public function reorderable(bool $value = true): static
    {
        $this->reorderable = $value;

        return $this;
    }

    public static function make(string $key, string $label): static
    {
        return new static($key, $label);
    }

    public function align(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    public function getStateUsing(Closure $callback): static
    {
        $this->getStateUsing = $callback;

        return $this;
    }

    public function onEditUsing(Closure $callback): static
    {
        $this->onEditUsing = $callback;

        return $this;
    }

    public function authorizeEditUsing(Closure $callback): static
    {
        $this->authorizeEditUsing = $callback;

        return $this;
    }

    public function isEditAuthorized(Model $record): bool
    {
        if ($this->authorizeEditUsing === null) {
            return true;
        }

        return (bool) ($this->authorizeEditUsing)($record, auth()->user());
    }

    public function cellClass(string $class): static
    {
        $this->cellClass = $class;

        return $this;
    }

    public function headerClass(string $class): static
    {
        $this->headerClass = $class;

        return $this;
    }

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;

        return $this;
    }

    public function sortColumn(string $column): static
    {
        $this->sortColumn = $column;
        $this->sortable = true;

        return $this;
    }

    public function getSortColumn(): string
    {
        return $this->sortColumn ?? $this->key;
    }

    public function isEditable(): bool
    {
        return $this->onEditUsing !== null;
    }

    /**
     * Resolve o valor bruto a partir do modelo (respeita getStateUsing).
     */
    public function resolveState(Model $record): mixed
    {
        if ($this->getStateUsing !== null) {
            return ($this->getStateUsing)($record);
        }

        return data_get($record, $this->key);
    }

    /**
     * Tipo usado no @switch do Blade.
     */
    abstract public function getType(): string;
}
