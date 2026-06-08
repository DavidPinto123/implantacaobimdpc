<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Cnpj;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ValidCnpj implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rawValue = trim((string) $value);

        if ($rawValue === '') {
            return;
        }

        if (! Cnpj::isValid($rawValue)) {
            $fail('Informe um CNPJ válido.');
        }
    }
}
