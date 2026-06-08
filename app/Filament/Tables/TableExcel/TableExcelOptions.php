<?php

namespace App\Filament\Tables\TableExcel;

final class TableExcelOptions
{
    public bool $dense = false;

    public bool $excelStyle = false;

    public bool $filtersModal = false;

    public bool $columnManager = false;

    public bool $stickyHeader = false;

    public bool $stickyActionsColumn = false;

    public bool $groupedColumns = false;

    public bool $freezable = false;

    public bool $resizable = false;

    public ?string $tableKey = null;

    public static function make(): self
    {
        return new self;
    }

    public function dense(bool $value = true): self
    {
        $this->dense = $value;

        return $this;
    }

    public function excelStyle(bool $value = true): self
    {
        $this->excelStyle = $value;

        return $this;
    }

    public function filtersModal(bool $value = true): self
    {
        $this->filtersModal = $value;

        return $this;
    }

    public function columnManager(bool $value = true): self
    {
        $this->columnManager = $value;

        return $this;
    }

    public function stickyHeader(bool $value = true): self
    {
        $this->stickyHeader = $value;

        return $this;
    }

    public function stickyActionsColumn(bool $value = true): self
    {
        $this->stickyActionsColumn = $value;

        return $this;
    }

    public function groupedColumns(bool $value = true): self
    {
        $this->groupedColumns = $value;

        return $this;
    }

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

    public function tableKey(?string $key): self
    {
        $this->tableKey = $key;

        return $this;
    }
}
