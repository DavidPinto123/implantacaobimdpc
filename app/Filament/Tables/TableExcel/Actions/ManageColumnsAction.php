<?php

namespace App\Filament\Tables\TableExcel\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Modal unificado de "Configurar Colunas" para o modo Page do Table Excel.
 *
 * Substitui o antigo painel inline `gs-table-excel__col-manager` e a action
 * `ManageFrozenColumnsAction`. Traz abas para visibilidade, congelamento e
 * reorganização, operando sobre o mesmo conjunto de colunas da tabela.
 *
 * Requer que a Page use HasTableExcelPage (métodos salvarColunasVisiveis,
 * salvarFrozenColumns e salvarOrdemColunas propagam para Livewire e refresham
 * o tbody).
 */
final class ManageColumnsAction
{
    /**
     * @param  array<string, string>  $columns  Map de key => label (prefixado pelo grupo).
     */
    public static function make(string $tableKey, array $columns, ?string $name = null): Action
    {
        $allKeys = array_keys($columns);
        $groups = self::groupColumns($columns);

        return Action::make($name ?? 'manageColumns')
            ->label('Configurar colunas')
            ->icon('heroicon-o-view-columns')
            ->color('gray')
            ->modalHeading('Configurar Colunas')
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Aplicar')
            ->modalCancelActionLabel('Fechar')
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->extraModalWindowAttributes(['class' => 'gs-te-config-modal gs-te-config-modal--columns'])
            ->fillForm(function ($livewire) use ($allKeys, $columns, $groups): array {
                $hidden = is_object($livewire) && property_exists($livewire, 'colunasOcultas')
                    ? (array) $livewire->colunasOcultas
                    : [];
                $frozen = is_object($livewire) && property_exists($livewire, 'frozenColumns')
                    ? (array) $livewire->frozenColumns
                    : [];
                $order = is_object($livewire) && property_exists($livewire, 'ordemColunas')
                    ? (array) $livewire->ordemColunas
                    : [];

                return [
                    'visible' => array_values(array_diff($allKeys, $hidden)),
                    'frozen' => array_values($frozen),
                    'visible_groups' => self::buildGroupedSelection($groups, array_values(array_diff($allKeys, $hidden))),
                    'frozen_groups' => self::buildGroupedSelection($groups, array_values($frozen)),
                    'order' => self::buildOrderItems($columns, $order),
                ];
            })
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Tabs::make('tabs')
                    ->extraAttributes(['class' => 'gs-te-config-tabs'])
                    ->tabs([
                        Tab::make('Visibilidade')
                            ->icon('heroicon-o-eye')
                            ->columns(1)
                            ->schema(self::buildGroupedCheckboxes('visible_groups', $groups)),
                        Tab::make('Congelar')
                            ->icon('heroicon-o-lock-closed')
                            ->columns(1)
                            ->schema(self::buildGroupedCheckboxes('frozen_groups', $groups, 'Colunas congeladas ficam fixas à esquerda durante a rolagem horizontal.')),
                        Tab::make('Reorganizar')
                            ->icon('heroicon-o-arrows-up-down')
                            ->columns(1)
                            ->schema([
                                Repeater::make('order')
                                    ->hiddenLabel()
                                    ->helperText('Arraste as colunas ou use as setas para definir a ordem horizontal. A coluna de ações continua fixa no início.')
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable()
                                    ->reorderableWithButtons()
                                    ->reorderableWithDragAndDrop()
                                    ->moveUpAction(fn (Action $action): Action => $action->label('Mover para cima'))
                                    ->moveDownAction(fn (Action $action): Action => $action->label('Mover para baixo'))
                                    ->reorderAction(fn (Action $action): Action => $action->label('Reorganizar'))
                                    ->defaultItems(0)
                                    ->itemLabel(fn (?array $state): string => (string) ($state['label'] ?? 'Coluna'))
                                    ->schema([
                                        Hidden::make('key'),
                                        Hidden::make('label'),
                                    ])
                                    ->extraAttributes(['class' => 'gs-te-column-order']),
                            ]),
                    ]),
            ]))
            ->action(function (array $data, $livewire) use ($allKeys): void {
                $visible = self::normalizeSelection(
                    (array) ($data['visible_groups'] ?? ($data['visible'] ?? [])),
                    $allKeys,
                );
                $frozen = self::normalizeSelection(
                    (array) ($data['frozen_groups'] ?? ($data['frozen'] ?? [])),
                    $allKeys,
                );
                $order = self::normalizeOrderItems((array) ($data['order'] ?? []), $allKeys);

                $hidden = array_values(array_diff($allKeys, $visible));

                if (is_object($livewire) && method_exists($livewire, 'salvarColunasVisiveis')) {
                    $livewire->salvarColunasVisiveis($hidden);
                }
                if (is_object($livewire) && method_exists($livewire, 'salvarFrozenColumns')) {
                    $livewire->salvarFrozenColumns($frozen);
                }
                if (is_object($livewire) && method_exists($livewire, 'salvarOrdemColunas')) {
                    $livewire->salvarOrdemColunas($order);
                }

                Notification::make()
                    ->title('Colunas atualizadas')
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string, string>  $columns
     * @param  array<int, string>  $currentOrder
     * @return array<int, array{key: string, label: string}>
     */
    protected static function buildOrderItems(array $columns, array $currentOrder): array
    {
        $keys = self::normalizeOrderItems($currentOrder, array_keys($columns));

        return array_map(
            fn (string $key): array => [
                'key' => $key,
                'label' => $columns[$key],
            ],
            $keys,
        );
    }

    /**
     * @param  array<string, string>  $columns
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    protected static function groupColumns(array $columns): array
    {
        $groups = [];

        foreach ($columns as $key => $label) {
            [$group, $columnLabel] = str_contains($label, ' · ')
                ? explode(' · ', $label, 2)
                : ['Colunas', $label];
            $groupKey = md5($group);

            $groups[$groupKey] ??= [
                'label' => $group,
                'options' => [],
            ];
            $groups[$groupKey]['options'][$key] = $columnLabel;
        }

        return $groups;
    }

    /**
     * @param  array<string, array{label: string, options: array<string, string>}>  $groups
     * @return array<int, Fieldset>
     */
    protected static function buildGroupedCheckboxes(string $statePath, array $groups, ?string $helperText = null): array
    {
        $components = [];

        foreach ($groups as $groupKey => $group) {
            $checkboxList = CheckboxList::make("{$statePath}.{$groupKey}")
                ->hiddenLabel()
                ->options($group['options'])
                ->columns(2)
                ->bulkToggleable()
                ->selectAllAction(fn (Action $action): Action => $action->label('Selecionar tudo'))
                ->deselectAllAction(fn (Action $action): Action => $action->label('Desmarcar tudo'))
                ->extraAttributes(['class' => 'gs-te-columns-checklist']);

            if ($helperText !== null && $components === []) {
                $checkboxList->helperText($helperText);
            }

            $components[] = Fieldset::make($group['label'])
                ->columns(1)
                ->schema([$checkboxList])
                ->extraAttributes(['class' => 'gs-te-columns-group']);
        }

        return $components;
    }

    /**
     * @param  array<string, array{label: string, options: array<string, string>}>  $groups
     * @param  array<int, string>  $selected
     * @return array<string, array<int, string>>
     */
    protected static function buildGroupedSelection(array $groups, array $selected): array
    {
        $state = [];

        foreach ($groups as $groupKey => $group) {
            $state[$groupKey] = array_values(array_intersect(array_keys($group['options']), $selected));
        }

        return $state;
    }

    /**
     * @param  array<int|string, mixed>  $selected
     * @param  array<int, string>  $allKeys
     * @return array<int, string>
     */
    protected static function normalizeSelection(array $selected, array $allKeys): array
    {
        $values = [];

        foreach ($selected as $item) {
            foreach ((array) $item as $key) {
                if (! is_string($key) || ! in_array($key, $allKeys, true) || in_array($key, $values, true)) {
                    continue;
                }

                $values[] = $key;
            }
        }

        return $values;
    }

    /**
     * @param  array<int, mixed>  $items
     * @param  array<int, string>  $allKeys
     * @return array<int, string>
     */
    protected static function normalizeOrderItems(array $items, array $allKeys): array
    {
        $keys = [];

        foreach ($items as $item) {
            $key = is_array($item) ? ($item['key'] ?? null) : $item;

            if (! is_string($key) || ! in_array($key, $allKeys, true) || in_array($key, $keys, true)) {
                continue;
            }

            $keys[] = $key;
        }

        foreach ($allKeys as $key) {
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
