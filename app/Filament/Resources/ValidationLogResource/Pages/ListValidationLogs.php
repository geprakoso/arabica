<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

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
        return [
            // Tidak ada aksi create — log dibuat otomatis
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(ValidationLog::count())
                ->badgeColor('gray'),

            'belum_selesai' => Tab::make('Belum Selesai')
                ->icon('heroicon-o-clock')
                ->badge(ValidationLog::where('is_resolved', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', false)),

            'selesai' => Tab::make('Sudah Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(ValidationLog::where('is_resolved', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', true)),

            'kritis' => Tab::make('Kritis & Error')
                ->icon('heroicon-o-fire')
                ->badge(ValidationLog::whereIn('severity', ['error', 'critical'])->where('is_resolved', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('severity', ['error', 'critical'])->where('is_resolved', false)),
        ];
    }
}
