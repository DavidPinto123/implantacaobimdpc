<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

final class FilamentDemoNavigation
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROLES = ['super_admin', 'gestor', 'Gestor'];

    /**
     * @return array<int, NavigationItem>
     */
    public static function items(): array
    {
        $items = [];

        foreach (self::structure() as $group => $config) {
            $resolvedGroup = $config['navigationGroup'] ?? $group;
            $items[] = self::makeItem(
                label: $config['label'] ?? $group,
                group: $resolvedGroup,
                icon: $config['icon'] ?? self::defaultGroupIcon($group),
                sort: $config['sort'] ?? 1,
                url: $config['url'] ?? null,
            );

            foreach ($config['children'] ?? [] as $index => $child) {
                $items[] = self::makeItem(
                    label: $child['label'],
                    group: $resolvedGroup,
                    parentItem: $child['parent'] ?? ($config['label'] ?? $group),
                    icon: $child['icon'],
                    sort: $child['sort'] ?? ($index + 1),
                    url: $child['url'] ?? '#',
                );
            }
        }

        return $items;
    }

    private static function makeItem(
        string $label,
        string $group,
        string $icon,
        int $sort,
        ?string $url,
        ?string $parentItem = null,
    ): NavigationItem {
        $item = NavigationItem::make($label)
            ->group($group)
            ->icon($icon)
            ->sort($sort)
            ->badge('.', 'danger')
            ->visible(fn (): bool => self::canView())
            ->url($url);

        if ($parentItem) {
            $item->parentItem($parentItem);
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private static function structure(): array
    {
        return [
            'Expansão' => [
                'label' => 'Expansão',
                'icon' => 'heroicon-o-folder',
                'url' => null,
                'children' => [
                    ...self::expansionChildren('Dashboard', [
                        ['label' => 'Geral', 'icon' => 'heroicon-o-home'],
                        ['label' => 'Meta', 'icon' => 'heroicon-o-flag'],
                        ['label' => 'PIPE (Caixas / Gráficos)', 'icon' => 'heroicon-o-chart-bar'],
                        ['label' => 'Agenda Geral', 'icon' => 'heroicon-o-calendar-days', 'url' => '/admin/agenda-geral'],
                        ['label' => 'Entrega Contratual', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => '/admin/entregas-contratuais'],
                        ['label' => 'Fluxograma', 'icon' => 'heroicon-o-share'],
                    ]),
                    ...self::expansionChildren('PMO', [
                        ['label' => 'Status Geral', 'icon' => 'heroicon-o-presentation-chart-line'],
                        ['label' => 'FUP', 'icon' => 'heroicon-o-arrow-path'],
                        ['label' => 'Ata de Briefing', 'icon' => 'heroicon-o-document-text'],
                        ['label' => 'PIPE (Email)', 'icon' => 'heroicon-o-envelope'],
                        ['label' => 'Status Implantação', 'icon' => 'heroicon-o-signal'],
                        ['label' => 'Check de List Implantação', 'icon' => 'heroicon-o-clipboard-document-check'],
                    ]),
                    ...self::expansionChildren('Comercial', [
                        ['label' => 'Planilha PIPE / Land', 'icon' => 'heroicon-o-table-cells'],
                    ]),
                    ...self::expansionChildren('Arquitetura', [
                        ['label' => 'Status Geral', 'icon' => 'heroicon-o-building-library'],
                        ['label' => 'Ata de Briefing', 'icon' => 'heroicon-o-document-text'],
                        ['label' => 'Demanda Equipe', 'icon' => 'heroicon-o-users'],
                    ]),
                    ...self::expansionChildren('Legalização', [
                        ['label' => 'Status Geral', 'icon' => 'heroicon-o-scale'],
                        ['label' => 'Consulta Prévia / EVTL', 'icon' => 'heroicon-o-magnifying-glass'],
                        ['label' => 'Demanda Equipe', 'icon' => 'heroicon-o-users'],
                    ]),
                    ...self::expansionChildren('Engenharia', [
                        ['label' => 'Status Geral', 'icon' => 'heroicon-o-wrench-screwdriver'],
                        ['label' => 'Agenda Equipe', 'icon' => 'heroicon-o-calendar-days'],
                        ['label' => 'Acompanhamento de BTS', 'icon' => 'heroicon-o-signal'],
                        ['label' => 'Check List Pré Implantação', 'icon' => 'heroicon-o-clipboard-document-check'],
                        ['label' => 'Cronograma de Implantação', 'icon' => 'heroicon-o-calendar'],
                        ['label' => 'Book de Obras', 'icon' => 'heroicon-o-book-open'],
                        ['label' => 'Financeiro', 'icon' => 'heroicon-o-banknotes'],
                    ]),
                    ...self::expansionChildren('Orçamentos', [
                        ['label' => 'Status Geral', 'icon' => 'heroicon-o-calculator'],
                        ['label' => 'AS', 'icon' => 'heroicon-o-document'],
                        ['label' => 'ASA', 'icon' => 'heroicon-o-document-check'],
                    ]),
                ],
            ],

            'Retrofit / Ampliação' => [
                'navigationGroup' => 'Outros',
                'label' => 'Retrofit / Ampliação',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'sort' => 1,
                'url' => null,
                'children' => [
                    [
                        'label' => 'Controle de Pedidos',
                        'icon' => 'heroicon-o-table-cells',
                        'sort' => 1,
                        'url' => '/admin/controle-pedidos-retrofit',
                    ],
                ],
            ],

            'Telão' => [
                'navigationGroup' => 'Outros',
                'label' => 'Telão',
                'icon' => 'heroicon-o-tv',
                'sort' => 1,
                'url' => '#',
            ],

            'Painel Global' => [
                'navigationGroup' => 'Outros',
                'label' => 'Painel Global',
                'icon' => 'heroicon-o-globe-alt',
                'sort' => 1,
                'url' => null,
                'children' => [
                    [
                        'label' => 'Base',
                        'icon' => 'heroicon-o-circle-stack',
                        'sort' => 1,
                        'url' => '#',
                    ],
                    [
                        'label' => 'Dashboard',
                        'icon' => 'heroicon-o-squares-2x2',
                        'sort' => 2,
                        'url' => '#',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, icon: string, sort?: int|null, url?: string|null}>  $children
     * @return array<int, array{label: string, icon: string, sort: int|null, url: string|null, parent: string}>
     */
    private static function expansionChildren(string $parent, array $children): array
    {
        return array_map(
            fn (array $child): array => [
                'label' => $child['label'],
                'icon' => $child['icon'],
                'sort' => $child['sort'] ?? null,
                'url' => $child['url'] ?? '#',
                'parent' => $parent,
            ],
            $children,
        );
    }

    private static function canView(): bool
    {
        $user = Filament::auth()->user();

        return Filament::auth()->check()
            && $user instanceof User
            && $user->hasAnyRole(self::ALLOWED_ROLES);
    }

    private static function defaultGroupIcon(string $group): string
    {
        return match ($group) {
            'Expansão' => 'heroicon-o-folder',
            'Painel Global' => 'heroicon-o-globe-alt',
            'Retrofit / Ampliação' => 'heroicon-o-wrench-screwdriver',
            'Telão' => 'heroicon-o-tv',
            default => 'heroicon-o-x-circle',
        };
    }
}
