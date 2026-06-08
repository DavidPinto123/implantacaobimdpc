<?php

namespace App\Filament\Tables\TableExcel\Support;

final class TableExcelPreferences
{
    /**
     * Memo por processo. Evita custo extra do SESSION_DRIVER=database a cada render
     * (preferências lidas repetidamente em colunas, linhas e helpers de blade).
     *
     * @var array<string, mixed>
     */
    protected static array $memo = [];

    public const ALLOWED_PREFS = [
        'visible_columns',
        'hidden_columns',
        'column_order',
        'density',
        'filters',
        'sort',
        'frozen_columns',
        'column_widths',
        'busca',
        'pagina',
        'per_page',
        'mostrar_filtros_avancados',
    ];

    public static function get(string $tableKey, string $pref, mixed $default = null): mixed
    {
        if (! self::isAllowed($pref)) {
            return $default;
        }

        $userId = self::userId();

        if ($userId === null) {
            return $default;
        }

        $key = self::key($userId, $tableKey, $pref);

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key] ?? $default;
        }

        $value = session($key);
        self::$memo[$key] = $value;

        return $value ?? $default;
    }

    public static function put(string $tableKey, string $pref, mixed $value): void
    {
        if (! self::isAllowed($pref)) {
            return;
        }

        $userId = self::userId();

        if ($userId === null) {
            return;
        }

        $key = self::key($userId, $tableKey, $pref);

        if (array_key_exists($key, self::$memo) && self::$memo[$key] === $value) {
            return;
        }

        self::$memo[$key] = $value;
        session()->put($key, $value);
    }

    public static function forget(string $tableKey): void
    {
        $userId = self::userId();

        if ($userId === null) {
            return;
        }

        foreach (self::ALLOWED_PREFS as $pref) {
            $key = self::key($userId, $tableKey, $pref);
            unset(self::$memo[$key]);
            session()->forget($key);
        }
    }

    protected static function key(int|string $userId, string $tableKey, string $pref): string
    {
        return "table-excel.{$userId}.{$tableKey}.{$pref}";
    }

    protected static function isAllowed(string $pref): bool
    {
        return in_array($pref, self::ALLOWED_PREFS, true);
    }

    protected static function userId(): int|string|null
    {
        return auth()->id();
    }
}
