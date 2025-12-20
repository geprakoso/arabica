<?php

namespace App\Filament\Widgets;

use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use App\Models\PenjadwalanService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ServiceWidget extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected ?string $placeholderHeight = '16rem';
    protected static ?int $sort = 8;

    protected static ?string $icon = 'hugeicons-wrench-01';
    protected static ?string $heading = 'Service Pending';
    protected static ?string $iconColor = 'warning';
    protected static ?string $description = 'Daftar service yang sedang menunggu proses.';

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
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
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->limit(25),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state ?? 'unknown') {
                        'pending' => 'Antrian',
                        'diagnosa' => 'Diagnosa',
                        'waiting_part' => 'Wait Part',
                        'progress' => 'Proses',
                        'done' => 'Selesai',
                        'cancel' => 'Batal',
                        'unknown' => 'Tidak diketahui',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state ?? 'unknown') {
                        'pending' => 'gray',
                        'diagnosa' => 'info',
                        'waiting_part' => 'warning',
                        'progress' => 'info',
                        'done' => 'success',
                        'cancel' => 'danger',
                        'unknown' => 'gray',
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
            ])
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(false)
                    ->icon(null)
                    ->slideOver()
                    ->modalHeading(fn (PenjadwalanService $record) => $record->no_resi)
                    ->modalWidth('6xl')
                    ->infolist(fn (Infolist $infolist) => PenjadwalanServiceResource::infolist($infolist)),
            ]);
    }
}
