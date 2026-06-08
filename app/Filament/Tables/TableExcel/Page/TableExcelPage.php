<?php

namespace App\Filament\Tables\TableExcel\Page;

use App\Filament\Tables\TableExcel\Page\Columns\Column;
use App\Filament\Tables\TableExcel\Page\Filters\Filter;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Config builder declarativo do modo Page do Table Excel.
 * Usado pela trait HasTableExcelPage dentro de Filament\Pages\Page.
 */
class TableExcelPage
{
    protected Closure|Builder|null $querySource = null;

    /** @var array<int, Column> */
    protected array $columns = [];

    /** @var array<int, Filter> */
    protected array $filters = [];

    protected ?string $searchProperty = null;

    protected string $searchPlaceholder = 'Buscar...';

    /** @var array<int, string> */
    protected array $searchFields = [];

    protected int $perPage = 50;

    /** @var array<int, int> */
    protected array $perPageOptions = [25, 50, 100, 200];

    protected string $recordKey = 'id';

    protected ?Closure $rowUrl = null;

    protected ?string $emptyStateHeading = 'Nenhum registro encontrado';

    protected ?string $emptyStateDescription = null;

    protected ?string $tableKey = null;

    protected bool $bulkEnabled = false;

    protected ?Closure $bulkDeleteAuthorizeUsing = null;

    protected bool $stickyHeader = false;

    protected bool $stickyActions = false;

    protected bool $dense = false;

    protected bool $striped = false;

    protected bool $freezable = false;

    protected bool $resizable = false;

    public static function make(): self
    {
        return new self;
    }

    public function query(Closure|Builder $source): self
    {
        $this->querySource = $source;

        return $this;
    }

    /**
     * @param  array<int, Column>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * @param  array<int, Filter>  $filters
     */
    public function filters(array $filters): self
    {
        $this->filters = array_values($filters);

        return $this;
    }

    /**
     * @param  array<int, string>  $fields  colunas pesquisadas via LIKE
     */
    public function search(string $property = 'busca', string $placeholder = 'Buscar...', array $fields = []): self
    {
        $this->searchProperty = $property;
        $this->searchPlaceholder = $placeholder;
        $this->searchFields = $fields;

        return $this;
    }

    public function perPage(int $n): self
    {
        $this->perPage = max(1, $n);

        return $this;
    }

    /**
     * @param  array<int, int>  $options
     */
    public function perPageOptions(array $options): self
    {
        $this->perPageOptions = array_values(array_unique(array_map(fn ($n) => max(1, (int) $n), $options)));

        return $this;
    }

    /**
     * @return array<int, int>
     */
    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    public function recordKey(string $attribute): self
    {
        $this->recordKey = $attribute;

        return $this;
    }

    public function rowUrl(Closure $callback): self
    {
        $this->rowUrl = $callback;

        return $this;
    }

    public function emptyState(string $heading, ?string $description = null): self
    {
        $this->emptyStateHeading = $heading;
        $this->emptyStateDescription = $description;

        return $this;
    }

    public function tableKey(?string $key): self
    {
        $this->tableKey = $key;

        return $this;
    }

    public function getTableKey(): ?string
    {
        return $this->tableKey;
    }

    public function bulkDelete(?Closure $authorize = null): self
    {
        $this->bulkEnabled = true;
        $this->bulkDeleteAuthorizeUsing = $authorize;

        return $this;
    }

    public function stickyHeader(bool $value = true): self
    {
        $this->stickyHeader = $value;

        return $this;
    }

    public function stickyActions(bool $value = true): self
    {
        $this->stickyActions = $value;

        return $this;
    }

    public function dense(bool $value = true): self
    {
        $this->dense = $value;

        return $this;
    }

    public function striped(bool $value = true): self
    {
        $this->striped = $value;

        return $this;
    }

    public function isStickyHeader(): bool { return $this->stickyHeader; }
    public function isStickyActions(): bool { return $this->stickyActions; }
    public function isDense(): bool { return $this->dense; }
    public function isStriped(): bool { return $this->striped; }

    public function freezable(bool $value = true): self
    {
        $this->freezable = $value;

        return $this;
    }

    public function resizable(bool $value = true): self
    {
        $this->resizable = $value;

        return $this;
    }

    public function isFreezable(): bool { return $this->freezable; }
    public function isResizable(): bool { return $this->resizable; }

    public function isBulkEnabled(): bool
    {
        return $this->bulkEnabled;
    }

    public function getBulkDeleteAuthorize(): ?Closure
    {
        return $this->bulkDeleteAuthorizeUsing;
    }

    // --- getters ---

    /** @return array<int, Column> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnByKey(string $key): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }

    /** @return array<int, Filter> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFilterByKey(string $key): ?Filter
    {
        foreach ($this->filters as $filter) {
            if ($filter->key === $key) {
                return $filter;
            }
        }

        return null;
    }

    public function getSearchProperty(): ?string
    {
        return $this->searchProperty;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder;
    }

    /** @return array<int, string> */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function hasSearch(): bool
    {
        return $this->searchProperty !== null;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getRecordKey(): string
    {
        return $this->recordKey;
    }

    public function resolveRowUrl(Model $record): ?string
    {
        if ($this->rowUrl === null) {
            return null;
        }

        $url = ($this->rowUrl)($record);

        return filled($url) ? (string) $url : null;
    }

    public function getEmptyStateHeading(): ?string
    {
        return $this->emptyStateHeading;
    }

    public function getEmptyStateDescription(): ?string
    {
        return $this->emptyStateDescription;
    }

    /**
     * Resolve um Builder novo a cada chamada (query fresca, sem estado compartilhado).
     */
    public function buildQuery(): Builder
    {
        $source = $this->querySource;

        if ($source instanceof Closure) {
            $source = ($source)();
        }

        if (! $source instanceof Builder) {
            throw new \RuntimeException(
                'TableExcelPage::query() deve receber um Closure retornando Builder ou um Builder direto.',
            );
        }

        // Clona para evitar que chamadas subsequentes herdem where/orderBy da anterior
        return clone $source;
    }
}
