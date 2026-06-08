<?php

declare(strict_types=1);

namespace App\Support;

final class Cnpj
{
    private const FIRST_DIGIT_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const SECOND_DIGIT_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public static function normalize(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        if (in_array($normalized, ['NULL', 'UNDEFINED'], true)) {
            return '';
        }

        return preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';
    }

    public static function format(?string $value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) !== 14) {
            return $normalized;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($normalized, 0, 2),
            substr($normalized, 2, 3),
            substr($normalized, 5, 3),
            substr($normalized, 8, 4),
            substr($normalized, 12, 2),
        );
    }

    public static function isValid(?string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === '' || strlen($normalized) !== 14) {
            return false;
        }

        if (preg_match('/^[0-9]{14}$/', $normalized) === 1 && preg_match('/^(\d)\1{13}$/', $normalized) === 1) {
            return false;
        }

        if (preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $normalized) !== 1) {
            return false;
        }

        $base = substr($normalized, 0, 12);
        $expectedDigits = self::calculateCheckDigits($base);

        return substr($normalized, 12, 2) === $expectedDigits;
    }

    public static function calculateCheckDigits(string $base): string
    {
        $normalizedBase = self::normalize($base);

        if (strlen($normalizedBase) !== 12 || preg_match('/^[A-Z0-9]{12}$/', $normalizedBase) !== 1) {
            return '';
        }

        $firstDigit = self::calculateDigit($normalizedBase, self::FIRST_DIGIT_WEIGHTS);
        $secondDigit = self::calculateDigit($normalizedBase.$firstDigit, self::SECOND_DIGIT_WEIGHTS);

        return $firstDigit.$secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private static function calculateDigit(string $value, array $weights): string
    {
        $sum = 0;

        foreach (str_split($value) as $index => $character) {
            $sum += self::characterValue($character) * $weights[$index];
        }

        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;

        return (string) $digit;
    }

    private static function characterValue(string $character): int
    {
        return ord($character) - 48;
    }
}
