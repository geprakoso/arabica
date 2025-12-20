<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class AppDashboard extends BaseDashboard
{
    /**
     * @return array<class-string<\Filament\Widgets\Widget> | \Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        $widgets = match ($panelId) {
            'pos' => [
                \App\Filament\Widgets\OpenWeatherWidget::class,
                \App\Filament\Widgets\PosSalesStatsOverview::class,
                \App\Filament\Widgets\MonthlyRevenueTrendChart::class,
                \App\Filament\Widgets\ActiveMembersTable::class,
                \App\Filament\Widgets\LowStockProductsTable::class,
                \App\Filament\Widgets\RecentPosTransactionsTable::class,
                \App\Filament\Widgets\TopSellingProductsTable::class,
            ],
            default => [
                \App\Filament\Widgets\OpenWeatherWidget::class,
                \App\Filament\Widgets\WelcomeWeatherWidget::class,
                \App\Filament\Widgets\AbsensiWidget::class,
                \App\Filament\Widgets\ActiveMembersTable::class,
                \App\Filament\Widgets\AdvancedStatsOverviewWidget::class,
                \App\Filament\Widgets\LowStockProductsTable::class,
                \App\Filament\Widgets\MonthlyRevenueTrendChart::class,
                \App\Filament\Widgets\RecentPosTransactionsTable::class,
                \App\Filament\Widgets\ServiceWidget::class,
                \App\Filament\Widgets\TopSellingProductsTable::class,
                \App\Filament\Widgets\TugasWidget::class,
            ],
        };

        return $this->sortWidgetsByOrderNumber($widgets);
    }

    /**
     * Widgets are ordered using each widget's `protected static ?int $sort` value.
     *
     * @param  array<class-string<Widget> | WidgetConfiguration>  $widgets
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function sortWidgetsByOrderNumber(array $widgets): array
    {
        return collect($widgets)
            ->sortBy(fn (string | WidgetConfiguration $widget): int => $this->normalizeWidgetClass($widget)::getSort())
            ->values()
            ->all();
    }
}
