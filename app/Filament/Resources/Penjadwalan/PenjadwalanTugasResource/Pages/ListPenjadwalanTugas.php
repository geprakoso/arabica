<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjadwalanTugas extends ListRecords
{
    protected static string $resource = PenjadwalanTugasResource::class;
    protected static ?string $title = 'Penjadwalan Tugas';

    public ?string $activeTab = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah')
                ->icon('hugeicons-add-01'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'proses' => \Filament\Resources\Components\Tab::make('Proses') // Pending & Proses
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('status', [
                    \App\Enums\StatusTugas::Pending, 
                    \App\Enums\StatusTugas::Proses
                ])),
            'selesai' => \Filament\Resources\Components\Tab::make('Selesai')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', \App\Enums\StatusTugas::Selesai)),
            'batal' => \Filament\Resources\Components\Tab::make('Batal')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', \App\Enums\StatusTugas::Batal)),
            'all' => \Filament\Resources\Components\Tab::make('Semua')
                ->icon('heroicon-m-list-bullet'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'proses'; // Set default tab to 'proses'
    }
}
