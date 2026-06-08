<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

class ActionsColumn extends Column
{
    /** @var array<int, RowAction> */
    public array $actions = [];

    /**
     * @param  array<int, RowAction>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = array_values($actions);

        return $this;
    }

    public function getActionByKey(string $key): ?RowAction
    {
        foreach ($this->actions as $action) {
            if ($action->key === $key) {
                return $action;
            }
        }

        return null;
    }

    public function getType(): string
    {
        return 'actions';
    }
}
