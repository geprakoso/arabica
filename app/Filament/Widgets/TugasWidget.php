<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PenjadwalanTugas;
use App\Enums\StatusTugas;
use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;

class TugasWidget extends BaseWidget
{
    protected static ?string $heading = 'Tugas Terbaru';
    protected int|string|array $columnSpan = '1/2';
    protected int|string|array $contentHeight = 'auto';
    protected int|string|array $recordCount = 5;
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PenjadwalanTugas::query()
                    ->latest('deadline')
            )
            ->paginated(false)
            ->recordUrl(fn ($record) => PenjadwalanTugasResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->limit(5),
                Tables\Columns\TextColumn::make('karyawan.name')
                    ->label('Karyawan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getLabel() : (filled($state) ? StatusTugas::from($state)->getLabel() : null))
                    ->icon(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getIcon() : (filled($state) ? StatusTugas::from($state)->getIcon() : null))
                    ->color(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getColor() : (filled($state) ? StatusTugas::from($state)->getColor() : null)),
                Tables\Columns\TextColumn::make('prioritas')
                    ->badge()
                    ->colors([
                        'rendah' => 'success',
                        'sedang' => 'info',
                        'tinggi' => 'warning',
                    ]),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable(),
            ]);
    }
}
