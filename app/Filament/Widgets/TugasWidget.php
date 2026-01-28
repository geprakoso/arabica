<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use App\Models\PenjadwalanTugas;
use App\Enums\StatusTugas;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;

class TugasWidget extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected ?string $placeholderHeight = '18rem';
    protected int|string|array $columnSpan = '1/2';
    protected int|string|array $contentHeight = 'auto';
    protected int|string|array $recordCount = 5;
    protected static ?int $sort = 9;

    protected static ?string $icon = 'hugeicons-sticky-note-02';
    protected static ?string $heading = 'Tugas Terbaru';
    protected static ?string $iconColor = 'primary';
    protected static ?string $description = 'Daftar tugas terbaru yang sedang berjalan.';

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query(
                PenjadwalanTugas::query()
                    ->latest('deadline')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->limit(5),
                Tables\Columns\TextColumn::make('karyawan.name')
                    ->label('Karyawan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getLabel() : (filled($state) ? StatusTugas::from($state)->getLabel() : null))
                    ->icon(fn(StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getIcon() : (filled($state) ? StatusTugas::from($state)->getIcon() : null))
                    ->color(fn(StatusTugas|string|null $state) => $state instanceof StatusTugas ? $state->getColor() : (filled($state) ? StatusTugas::from($state)->getColor() : null)),
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
            ])->paginated(10)
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(false)
                    ->icon(null)
                    ->slideOver()
                    ->modalHeading(fn(PenjadwalanTugas $record) => $record->judul)
                    ->modalWidth('6xl')
                    ->infolist(fn(Infolist $infolist) => PenjadwalanTugasResource::infolist($infolist)),
            ]);
    }
}
