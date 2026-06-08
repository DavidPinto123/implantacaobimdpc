<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\Rules\ValidCnpj;
use App\Support\Cnpj;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

final class CnpjInput extends TextInput
{
    protected bool $shouldValidateCnpj = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('CNPJ')
            ->default('')
            ->mask(RawJs::make(<<<'JS'
                (() => {
                    const rawValue = typeof $input === 'undefined' || $input === null
                        ? ''
                        : $input;

                    if (['undefined', 'null'].includes(String(rawValue).toLowerCase())) {
                        return '';
                    }

                    const value = String(rawValue)
                        .toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .slice(0, 14);

                    if (value === '') {
                        return '';
                    }

                    if (value.length <= 2) {
                        return value;
                    }

                    if (value.length <= 5) {
                        return `${value.slice(0, 2)}.${value.slice(2)}`;
                    }

                    if (value.length <= 8) {
                        return `${value.slice(0, 2)}.${value.slice(2, 5)}.${value.slice(5)}`;
                    }

                    if (value.length <= 12) {
                        return `${value.slice(0, 2)}.${value.slice(2, 5)}.${value.slice(5, 8)}/${value.slice(8)}`;
                    }

                    return `${value.slice(0, 2)}.${value.slice(2, 5)}.${value.slice(5, 8)}/${value.slice(8, 12)}-${value.slice(12)}`;
                })()
            JS))
            ->placeholder('00.000.000/0000-00')
            ->maxLength(18)
            ->stripCharacters(['.', '/', '-'])
            ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                if ($state === null) {
                    $component->state('');
                }
            })
            ->formatStateUsing(fn (mixed $state): string => Cnpj::format((string) $state) ?? '')
            ->dehydrateStateUsing(fn (mixed $state): string => Cnpj::format((string) $state) ?? '')
            ->extraInputAttributes([
                'onkeydown' => "if (event.ctrlKey || event.metaKey || event.altKey) return; const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End', 'ArrowLeft', 'ArrowRight']; if (allowedKeys.includes(event.key)) return; if (!/^[a-zA-Z0-9]$/.test(event.key)) event.preventDefault();",
            ])
            ->rules(fn (): array => $this->shouldValidateCnpj ? [new ValidCnpj] : []);
    }

    public function validateCnpj(bool $condition = true): static
    {
        $this->shouldValidateCnpj = $condition;

        return $this;
    }
}
