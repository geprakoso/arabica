<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

use App\Enums\Severity;
use App\Filament\Resources\ValidationLogResource;
use App\Models\ValidationLog;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListValidationLogs extends ListRecords
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $total = ValidationLog::count();
        $unresolved = ValidationLog::where('is_resolved', false)->count();
        $resolved = $total - $unresolved;
        $critical = ValidationLog::whereIn('severity', [Severity::Error->value, Severity::Critical->value])
            ->where('is_resolved', false)
            ->count();

        return [
            'semua' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge($total)
                ->badgeColor('gray'),

            'belum_selesai' => Tab::make('Belum Selesai')
                ->icon('heroicon-o-clock')
                ->badge($unresolved)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', false)),

            'selesai' => Tab::make('Sudah Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge($resolved)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', true)),

            'kritis' => Tab::make('Kritis & Error')
                ->icon('heroicon-o-fire')
                ->badge($critical)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('severity', [Severity::Error->value, Severity::Critical->value])->where('is_resolved', false)),
        ];
    }
}