<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasInputMode;
use Filament\Forms\Components\Concerns\HasStep;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Concerns;
use Filament\Tables\Columns\Concerns\CanFormatState;
use Filament\Tables\Columns\Contracts\Editable;

class InlineEditColumn extends Column implements Editable
{
    use CanFormatState;
    use Concerns\CanBeValidated;
    use Concerns\CanUpdateState;
    use HasExtraInputAttributes;
    use HasInputMode;
    use HasStep;

    protected string $view = 'filament.tables.columns.inline-edit-column';

    protected string|RawJs|Closure|null $mask = null;

    protected string|Closure|null $type = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();
    }

    public function type(string|Closure|null $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->evaluate($this->type) ?? 'text';
    }

    public function mask(string|RawJs|Closure|null $mask): static
    {
        $this->mask = $mask;

        return $this;
    }

    public function getMask(): string|RawJs|null
    {
        return $this->evaluate($this->mask);
    }
}
