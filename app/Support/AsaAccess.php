<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AsaAccess
{
    /**
     * Usuário com shield "Gestor" ou "Coordenador" e setor "Obras" enxerga tudo.
     */
    public static function canViewAllStatuses(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasAnyRole(['Gestor', 'Coordenador'])
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    /**
     * Usuário com shield "Coordenador" e setor "Orçamentos" enxerga apenas os status do orçamento.
     */
    public static function shouldRestrictToOrcamentoStatuses(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return false;
        }

        if (! $user->hasAnyRole(['Coordenador', 'coordenador', 'Coordenador_Orcamento', 'coordenador_orcamento'])) {
            return false;
        }

        return $user->setores()
            ->whereRaw('LOWER(setor) in (?, ?, ?, ?, ?, ?)', [
                'orçamento',
                'orcamento',
                'orçamentos',
                'orcamentos',
                'orÃ§amento',
                'orÃ§amentos',
            ])
            ->exists();
    }

    /**
     * Coordenador do Orçamento não edita campos (exceto Desconto).
     * Se também for do setor Obras (caso raro), não restringe.
     */
    public static function shouldRestrictEditingToDesconto(User $user): bool
    {
        if (self::canViewAllStatuses($user)) {
            return false;
        }

        return self::shouldRestrictToOrcamentoStatuses($user);
    }

    /**
     * Aplica o filtro dos status do orçamento (em_aprovacao_orcamento, aprovado, reprovado_orcamento).
     * Mantém compatibilidade com variações históricas (snake_case e textos).
     */
    public static function scopeOnlyOrcamentoStatuses(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', [
                'em_aprovacao_orcamento',
                'aprovado',
                'reprovado_orcamento',
                'Em aprovação do orçamento',
                'Em aprovaÃ§Ã£o do orÃ§amento',
                'Aprovado',
                'Reprovado pelo orçamento',
                'Reprovado pelo orÃ§amento',
            ]);

            // Em aprovação do orçamento (variações).
            $q->orWhere(function (Builder $q2) {
                $q2->whereRaw('LOWER(status) LIKE ?', ['%aprov%'])
                    ->where(function (Builder $q3) {
                        $q3->whereRaw('LOWER(status) LIKE ?', ['%orc%'])
                            ->orWhereRaw('LOWER(status) LIKE ?', ['%orç%'])
                            ->orWhereRaw('LOWER(status) LIKE ?', ['%orÃ§%']);
                    });
            });

            // Aprovado.
            $q->orWhereRaw('LOWER(status) LIKE ?', ['%aprovad%']);

            // Reprovado pelo orçamento (variações).
            $q->orWhere(function (Builder $q2) {
                $q2->whereRaw('LOWER(status) LIKE ?', ['%reprov%'])
                    ->where(function (Builder $q3) {
                        $q3->whereRaw('LOWER(status) LIKE ?', ['%orc%'])
                            ->orWhereRaw('LOWER(status) LIKE ?', ['%orç%'])
                            ->orWhereRaw('LOWER(status) LIKE ?', ['%orÃ§%']);
                    });
            });
        });
    }
}

