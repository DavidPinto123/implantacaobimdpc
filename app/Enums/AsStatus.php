<?php

namespace App\Enums;

enum AsStatus: string
{
    case RASCUNHO = 'rascunho';
    case SOLICITADO = 'solicitado';
    case EM_APROVACAO_GESTOR = 'em_aprovacao_gestor';
    case EM_APROVACAO_ORCAMENTO = 'em_aprovacao_orcamento';
    case APROVADO = 'aprovado';
    case REPROVADO_GESTOR = 'reprovado_gestor';
    case REPROVADO_ORCAMENTO = 'reprovado_orcamento';
    case CRIADA = 'criada';
    case ENVIADA = 'enviada';
    case EM_ORCAMENTO = 'em_orcamento';
    case ORCADA = 'orcada';
    case CANCELADA = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::RASCUNHO => 'Rascunho',
            self::SOLICITADO => 'Solicitado',
            self::EM_APROVACAO_GESTOR => 'Em aprovação do gestor',
            self::EM_APROVACAO_ORCAMENTO => 'Em aprovação do orçamento',
            self::APROVADO => 'Aprovado',
            self::REPROVADO_GESTOR => 'Reprovado pelo gestor',
            self::REPROVADO_ORCAMENTO => 'Reprovado pelo orçamento',
            self::CRIADA => 'Criada',
            self::ENVIADA => 'Enviada',
            self::EM_ORCAMENTO => 'Em orçamento',
            self::ORCADA => 'Orçada',
            self::CANCELADA => 'Cancelada',
        };
    }

    /**
     * Mapeia para variantes da pill `cpr-obra-pill--{cor}` (neutral/info/warning/success/danger).
     */
    public function color(): string
    {
        return match ($this) {
            self::RASCUNHO => 'neutral',
            self::SOLICITADO,
            self::EM_APROVACAO_GESTOR,
            self::EM_APROVACAO_ORCAMENTO,
            self::EM_ORCAMENTO,
            self::ORCADA => 'info',
            self::APROVADO,
            self::ENVIADA => 'success',
            self::CRIADA => 'warning',
            self::REPROVADO_GESTOR,
            self::REPROVADO_ORCAMENTO,
            self::CANCELADA => 'danger',
        };
    }

    public function permiteCancelar(): bool
    {
        return match ($this) {
            self::CRIADA, self::ENVIADA => true,
            default => false,
        };
    }

    public function permiteVisualizar(): bool
    {
        return match ($this) {
            self::CRIADA, self::ENVIADA, self::CANCELADA => true,
            default => false,
        };
    }

    public function permiteCriarAs(): bool
    {
        return $this === self::APROVADO;
    }

    public function permiteEnviarAs(): bool
    {
        return $this === self::CRIADA;
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

    public static function labelFrom(?string $status): string
    {
        return self::tryFrom((string) $status)?->label() ?? '-';
    }

    public static function colorFrom(?string $status): string
    {
        return self::tryFrom((string) $status)?->color() ?? 'neutral';
    }
}
