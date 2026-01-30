<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Penjadwalan\KalenderEventResource\Pages;
use App\Models\KalenderEvent;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KalenderEventResource extends BaseResource
{
    protected static ?string $model = KalenderEvent::class;

    protected static ?string $navigationGroup = 'Kalender';

    protected static ?string $navigationLabel = 'Jadwal Event';

    protected static ?string $title = 'Jadwal Event';

    protected static ?string $pluralModelLabel = 'Jadwal Event';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = -1;

    protected static ?string $recordTitleAttribute = 'judul';

    public static function getGloballySearchableAttributes(): array
    {
        return ['judul', 'deskripsi', 'lokasi'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('judul')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255),
                Select::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'libur' => 'Libur Nasional',
                        'meeting' => 'Meeting',
                        'event' => 'Event',
                        'catatan' => 'Catatan',
                    ])
                    ->native(false)
                    ->required(),
                RichEditor::make('deskripsi')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                TextInput::make('lokasi')
                    ->label('Lokasi')
                    ->maxLength(255),
                Toggle::make('all_day')
                    ->label('Sepanjang Hari'),
                DateTimePicker::make('mulai')
                    ->label('Mulai')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('selesai')
                    ->label('Selesai')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                TextInput::make('created_by')
                    ->label('Dibuat Oleh')
                    ->default(fn() => Filament::auth()->id())
                    ->disabled()
                    ->visible(false)
                    ->dehydrated()
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'libur' => 'danger',
                        'meeting' => 'info',
                        'event' => 'success',
                        'catatan' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('mulai')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('selesai')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                IconColumn::make('all_day')
                    ->label('All Day')
                    ->boolean(),
                TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('mulai', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ringkasan')
                    ->schema([
                        TextEntry::make('judul')
                            ->label('Judul')
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),
                        TextEntry::make('tipe')
                            ->label('Tipe')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'libur' => 'danger',
                                'meeting' => 'info',
                                'event' => 'success',
                                'catatan' => 'warning',
                                default => 'gray',
                            }),
                        IconEntry::make('all_day')
                            ->label('All Day')
                            ->boolean(),
                        TextEntry::make('lokasi')
                            ->label('Lokasi')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Waktu')
                    ->schema([
                        TextEntry::make('mulai')
                            ->label('Mulai')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('selesai')
                            ->label('Selesai')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),
                Section::make('Deskripsi')
                    ->schema([
                        TextEntry::make('deskripsi')
                            ->label('Catatan')
                            ->markdown()
                            ->placeholder('-'),
                    ]),
            ])
            ->columns(2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKalenderEvents::route('/'),
            'create' => Pages\CreateKalenderEvent::route('/create'),
            'view' => Pages\ViewKalenderEvent::route('/{record}'),
            'edit' => Pages\EditKalenderEvent::route('/{record}/edit'),
        ];
    }
}
