<?php

namespace AlperenErsoy\FilamentExport\Actions\Concerns;

trait CanFormatColumns
{
    protected array $columnFormats = [];

    public function columnFormats(array $formats = []): static
    {
        $this->columnFormats = $formats;

        return $this;
    }

    public function getColumnFormats(): array
    {
        return $this->columnFormats;
    }
}
