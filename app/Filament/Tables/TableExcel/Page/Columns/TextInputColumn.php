<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

/**
 * Coluna com input inline. Usa onEditUsing() para persistir no blur.
 *
 * Tipos suportados (apenas visual/placeholder): 'text' | 'number' | 'date'.
 * A validação/parse é responsabilidade do closure passado em onEditUsing().
 */
class TextInputColumn extends Column
{
    public string $inputType = 'text';

    public ?string $placeholder = null;

    public ?string $mask = null;

    public ?int $maxLength = null;

    public ?string $step = null;

    public function type(string $type): static
    {
        $this->inputType = $type;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function mask(?string $mask): static
    {
        $this->mask = $mask;

        return $this;
    }

    public function maxLength(?int $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    public function step(?string $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getType(): string
    {
        return 'text-input';
    }
}
