<?php

namespace App\Filament\Tables\TableExcel\Page\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Valor-objeto que representa um ícone-botão por linha numa ActionsColumn.
 *
 * Dois modos de operação:
 *  - ->url(fn) renderiza uma âncora e navega ao clicar.
 *  - ->onClickUsing(fn) renderiza um botão; o clique cai em
 *    HasTableExcelPage::executarAcaoLinha(), que executa o callback.
 */
class RowAction
{
    public string $key;

    public string $label;

    public string $icon = 'heroicon-o-cog-6-tooth';

    public ?string $color = null;

    public ?Closure $url = null;

    public ?Closure $onClickUsing = null;

    public ?Closure $authorizeUsing = null;

    public ?string $confirmMessage = null;

    public ?string $mountsActionName = null;

    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public static function make(string $key, string $label = ''): self
    {
        return new self($key, $label !== '' ? $label : $key);
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function url(Closure $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function onClickUsing(Closure $callback): self
    {
        $this->onClickUsing = $callback;

        return $this;
    }

    public function authorizeUsing(Closure $callback): self
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(Model $record): bool
    {
        if ($this->authorizeUsing === null) {
            return true;
        }

        return (bool) ($this->authorizeUsing)($record, auth()->user());
    }

    public function confirm(string $message): self
    {
        $this->confirmMessage = $message;

        return $this;
    }

    public function mountsAction(string $actionName): self
    {
        $this->mountsActionName = $actionName;

        return $this;
    }

    public function hasMountsAction(): bool
    {
        return $this->mountsActionName !== null;
    }

    public function resolveUrl(Model $record): ?string
    {
        if ($this->url === null) {
            return null;
        }

        $url = ($this->url)($record);

        return filled($url) ? (string) $url : null;
    }

    public function hasHandler(): bool
    {
        return $this->onClickUsing !== null;
    }
}
