<?php

namespace App\Enums;

enum StatusControleNotaFiscalNota: string
{
    case PENDENTE = 'pendente';
    case EM_ANALISE = 'em_analise';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Esperando Aprovação do Gestor',
            self::EM_ANALISE => 'Aguardando Aprovação do Gestor',
            self::APROVADO => 'Aprovado',
            self::REPROVADO => 'Reprovado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDENTE => 'gray',
            self::EM_ANALISE => 'warning',
            self::APROVADO => 'success',
            self::REPROVADO => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function comImpactoNoSaldo(): array
    {
        return [
            self::APROVADO->value,
            self::EM_ANALISE->value,
            self::PENDENTE->value,
        ];
    }

    public static function labelFrom(?string $status): string
    {
        return self::tryFrom((string) $status)?->label() ?? '-';
    }

    public static function colorFrom(?string $status): string
    {
        return self::tryFrom((string) $status)?->color() ?? 'gray';
    }
}
