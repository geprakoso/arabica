<?php

namespace App\Filament\Actions;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use Illuminate\Support\Collection;

class SummaryExportHeaderAction extends FilamentExportHeaderAction
{
    protected ?\Closure $summaryResolver = null;

    protected ?Collection $cachedRecords = null;

    public function summaryResolver(callable $resolver): static
    {
        $this->summaryResolver = $resolver instanceof \Closure ? $resolver : \Closure::fromCallable($resolver);

        return $this;
    }

    public function getRecords(): Collection
    {
        $this->cachedRecords = parent::getRecords();

        return $this->cachedRecords;
    }

    public function getExtraViewData(): array
    {
        return array_merge($this->extraViewData, [
            'summary' => $this->resolveSummary(),
        ]);
    }

    protected function resolveSummary(): array
    {
        if (! $this->summaryResolver) {
            return [];
        }

        $records = $this->cachedRecords ?? $this->getTableQuery()->get();

        return (array) ($this->summaryResolver)(
            $this->getTableQuery(),
            $records,
            $this,
        );
    }
}
