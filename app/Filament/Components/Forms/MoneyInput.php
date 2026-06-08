<?php

declare(strict_types=1);

namespace App\Filament\Components\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

final class MoneyInput
{
    public static function parse(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Se for um número (int/float real, não string), retorna direto
        if (! is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        $original = (string) $value;
        $clean = preg_replace('/[^\d,\.\-]/', '', $original);

        if ($clean === null || $clean === '') {
            return null;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($lastComma !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif ($lastDot !== false) {
            // String com ponto decimal (ex: "5000.00" vindo do banco)
            // Mantém como está
        } else {
            // String só de dígitos: vem do mask com stripCharacters,
            // onde os 2 últimos dígitos representam centavos.
            $negative = str_starts_with($clean, '-');
            $digits = ltrim($clean, '-');
            if (strlen($digits) > 2) {
                $clean = ($negative ? '-' : '').substr($digits, 0, -2).'.'.substr($digits, -2);
            } else {
                $clean = ($negative ? '-' : '').'0.'.str_pad($digits, 2, '0', STR_PAD_LEFT);
            }
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    public static function formatBr(mixed $value): string
    {
        $number = self::parse($value);

        if ($number === null) {
            return '';
        }

        return number_format($number, 2, ',', '.');
    }

    /**
     * @return array<string, string>
     */
    public static function getInputAttributes(): array
    {
        return [
            'x-on:input' => <<<'JS'
                const digits = $el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

                if (digits === '') {
                    return;
                }

                const cents = digits.slice(-2).padStart(2, '0');
                const integer = (digits.slice(0, -2) || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                const formatted = `${integer},${cents}`;

                if ($el.value !== formatted) {
                    $el.value = formatted;
                    $el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            JS,
        ];
    }

    public static function make(string $name, ?string $label = null): TextInput
    {
        $field = TextInput::make($name)
            ->prefix('R$')
            ->default('')
            ->mask(RawJs::make(<<<'JS'
                (() => {
                    const digits = String($input ?? '').replace(/\D/g, '');

                    if (digits === '') {
                        return '';
                    }

                    const integerLength = Math.max(digits.length - 2, 1);
                    const integerMask = '9'.repeat(integerLength)
                        .replace(/\B(?=(9{3})+(?!9))/g, '.');

                    return `${integerMask},99`;
                })()
            JS))
            ->stripCharacters(['.', ','])
            ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                if ($state === null) {
                    $component->state('');
                }
            })
            ->formatStateUsing(fn (mixed $state): string => self::formatBr($state))
            ->dehydrateStateUsing(fn (mixed $state): ?float => self::parse($state))
            ->inputMode('decimal')
            ->extraInputAttributes(self::getInputAttributes());

        if ($label !== null) {
            $field->label($label);
        }

        return $field;
    }

    public static function makeNonNull(string $name, string $label, float $emptyValue = 0): TextInput
    {
        return self::make($name, $label)
            ->default($emptyValue)
            ->afterStateHydrated(function (TextInput $component, mixed $state) use ($emptyValue): void {
                if ($state === null || $state === '') {
                    $component->state($emptyValue);
                }
            })
            ->dehydrateStateUsing(fn ($state) => self::parse($state) ?? $emptyValue);
    }
}
