<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PenjadwalanService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ServiceWidget extends BaseWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Service Terbaru';
    protected int|string|array $columnSpan = '1/2';
    protected static ?int $sort = 4;
    protected int|string|array $contentHeight = 'auto';
    protected int|string|array $recordCount = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PenjadwalanService::query()
                    ->latest('created_at')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('no_resi')
                    ->label('No. Resi')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->limit(25)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Antrian',
                        'diagnosa' => 'Diagnosa',
                        'waiting_part' => 'Wait Part',
                        'progress' => 'Proses',
                        'done' => 'Selesai',
                        'cancel' => 'Batal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'diagnosa' => 'info',
                        'waiting_part' => 'warning',
                        'progress' => 'info',
                        'done' => 'success',
                        'cancel' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimasi_selesai')
                    ->label('Estimasi')
                    ->date('d M')
                    ->sortable(),
            ]);
    }
}
