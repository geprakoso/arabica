<?php

namespace App\Filament\GlobalSearch;

use Filament\Facades\Filament;
use Filament\GlobalSearch\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\DefaultGlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Str;

class NavigationGlobalSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = (new DefaultGlobalSearchProvider())->getResults($query) ?? GlobalSearchResults::make();
        $needle = Str::of($query)->lower();
        $navResults = [];

        foreach (Filament::getNavigation() as $group) {
            foreach ($group->getItems() as $item) {
                $this->collectNavigationResults($item, $needle, $navResults);
            }
        }

        if ($navResults !== []) {
            $builder->category('Navigation', collect($navResults));
        }

        return $builder;
    }

    /**
     * @param  array<int, GlobalSearchResult>  $results
     */
    private function collectNavigationResults(NavigationItem $item, $needle, array &$results): void
    {
        if (! $item->isVisible()) {
            return;
        }

        $label = $item->getLabel();

        if (Str::of($label)->lower()->contains($needle)) {
            $url = $item->getUrl();

            if ($url) {
                $details = [];
                $group = $item->getGroup();

                if ($group) {
                    $details['Group'] = $group;
                }

                $results[] = new GlobalSearchResult(
                    title: $label,
                    url: $url,
                    details: $details,
                );
            }
        }

        foreach ($item->getChildItems() as $child) {
            $this->collectNavigationResults($child, $needle, $results);
        }
    }
}
