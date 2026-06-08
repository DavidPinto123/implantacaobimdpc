<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

class PillColumn extends Column
{
    public const COLOR_SUCCESS = 'success';

    public const COLOR_DANGER = 'danger';

    public const COLOR_INFO = 'info';

    public const COLOR_WARNING = 'warning';

    public const COLOR_NEUTRAL = 'neutral';

    /** @var array<string|int, string> */
    public array $options = [];

    /** @var array<string|int, string>|Closure */
    public array|Closure $pillColors = [];

    public string|Closure $defaultColor = self::COLOR_NEUTRAL;

    public bool $chevron = true;

    /**
     * @param  array<string|int, string>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<string|int, string>|Closure  $map
     */
    public function colors(array|Closure $map): static
    {
        $this->pillColors = $map;

        return $this;
    }

    public function defaultColor(string|Closure $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    public function chevron(bool $value = true): static
    {
        $this->chevron = $value;

        return $this;
    }

    public function getColorForState(mixed $state): string
    {
        $map = $this->pillColors;

        if ($map instanceof Closure) {
            $map = ($map)($state);
        }

        if (is_array($map) && $state !== null) {
            $key = (string) $state;
            if (array_key_exists($key, $map)) {
                return (string) $map[$key];
            }
            if (array_key_exists($state, $map)) {
                return (string) $map[$state];
            }
        }

        $default = $this->defaultColor;

        return (string) ($default instanceof Closure ? ($default)($state) : $default);
    }

    public function getLabelForState(mixed $state): string
    {
        if ($state === null) {
            return '—';
        }

        $key = (string) $state;

        return (string) ($this->options[$key] ?? $this->options[$state] ?? $state);
    }

    public function getType(): string
    {
        return 'pill';
    }

    public function resolveState(Model $record): mixed
    {
        return parent::resolveState($record);
    }
}
