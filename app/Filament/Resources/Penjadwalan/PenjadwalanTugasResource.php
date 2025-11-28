<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Enums\StatusTugas;
use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;
use App\Models\PenjadwalanTugas;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Get;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontWeight;


class PenjadwalanTugasResource extends Resource
{
    protected static ?string $model = PenjadwalanTugas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Penjadwalan';
    protected static ?string $navigationLabel = 'Tugas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // tambahkan field sesuai kebutuhan
                FormsGrid::make(3) // Membagi layar menjadi 3 kolom grid
                ->schema([
                    
                    // --- KOLOM KIRI (UTAMA) ---
                    // Mengambil 2 bagian dari 3 kolom (2/3 layar)
                    FormsGroup::make()
                        ->schema([
                            FormsSection::make('Detail Tugas')
                                ->description('Informasi utama mengenai tugas yang diberikan.')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    TextInput::make('judul')
                                        ->label('Judul Tugas')
                                        ->placeholder('Contoh: Perbaikan Stok Gudang A')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    RichEditor::make('deskripsi')
                                        ->label('Deskripsi Lengkap')
                                        ->toolbarButtons([
                                            'bold', 'italic', 'bulletList', 'orderedList', 'link', 'h2', 'h3'
                                        ])
                                        ->required()
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]), 

                    // --- KOLOM KANAN (SIDEBAR) ---
                    // Mengambil 1 bagian dari 3 kolom (1/3 layar)
                    FormsGroup::make()
                        ->schema([
                            
                            // Section 1: Status & Prioritas (Paling sering diubah)
                            FormsSection::make('Status & Urgensi')
                                ->icon('heroicon-o-flag')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status Pengerjaan')
                                        ->options(StatusTugas::class) // Pastikan Enum ini sudah ada
                                        ->default(StatusTugas::Pending)
                                        ->required()
                                        ->native(false),

                                    ToggleButtons::make('prioritas')
                                        ->label('Prioritas')
                                        ->options([
                                            'rendah' => 'Rendah',
                                            'sedang' => 'Sedang',
                                            'tinggi' => 'Tinggi',
                                        ])
                                        ->colors([
                                            'rendah' => 'success',
                                            'sedang' => 'info',
                                            'tinggi' => 'danger', // Warning biasanya kuning, Danger merah (lebih cocok untuk tinggi)
                                        ])
                                        ->icons([
                                            'rendah' => 'heroicon-o-arrow-down',
                                            'sedang' => 'heroicon-o-minus',
                                            'tinggi' => 'heroicon-o-arrow-up',
                                        ])
                                        ->inline()
                                        ->required(),
                                ]),

                            // Section 2: Penugasan (Orang)
                            FormsSection::make('Penugasan')
                                ->icon('heroicon-o-users')
                                ->schema([
                                    Select::make('karyawan_id')
                                        ->label('Ditugaskan Kepada')
                                        ->relationship(
                                            name: 'karyawan', // Pastikan relasi di model Task bernama 'karyawan'
                                            titleAttribute: 'name',
                                            modifyQueryUsing: fn ($query) => $query->whereHas('roles', function ($q) {
                                                // Opsional: Filter user yang punya role tertentu saja jika perlu
                                                // $q->where('name', 'staff');
                                            })
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Select::make('created_by')
                                        ->label('Pemberi Tugas')
                                        ->relationship('creator', 'name')
                                        ->default(fn () => Filament::auth()->id())
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                ]),

                            // Section 3: Waktu (Tanggal)
                            FormsSection::make('Durasi Pengerjaan')
                                ->icon('heroicon-o-calendar')
                                ->schema([
                                    DatePicker::make('tanggal_mulai')
                                        ->label('Tanggal Mulai')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->required(),
                                    DatePicker::make('deadline')
                                        ->label('Tenggat Waktu')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->minDate(fn (Get $get) => $get('tanggal_mulai')) // Validasi UX: Deadline tidak boleh sebelum tanggal mulai
                                        ->required(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                            ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('karyawan.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('judul')
                    ->label('Judul')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas
                        ? $state->getLabel()
                        : (filled($state) ? StatusTugas::from($state)->getLabel() : null))
                    ->icon(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas
                        ? $state->getIcon()
                        : (filled($state) ? StatusTugas::from($state)->getIcon() : null))
                    ->color(fn (StatusTugas|string|null $state) => $state instanceof StatusTugas
                        ? $state->getColor()
                        : (filled($state) ? StatusTugas::from($state)->getColor() : null))
                    ->sortable(),
                TextColumn::make('prioritas')
                    ->badge()
                    ->colors([
                        'rendah' => 'success',
                        'sedang' => 'info',
                        'tinggi' => 'warning',
                    ])
                    ->sortable(),
                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusTugas::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistGrid::make(3)
                    ->schema([
                        // --- KOLOM KIRI (KONTEN UTAMA - 2/3) ---
                        InfolistGroup::make()
                            ->schema([
                                InfolistSection::make()
                                    ->schema([
                                        TextEntry::make('judul')
                                            ->hiddenLabel() // Label disembunyikan agar judul terlihat seperti header
                                            ->weight(FontWeight::Bold)
                                            ->size(TextEntrySize::Large)
                                            ->icon('heroicon-m-document-text'),
                                        
                                        TextEntry::make('deskripsi')
                                            ->hiddenLabel()
                                            ->html() // Penting karena inputnya RichEditor
                                            ->prose() // Agar styling teks (list, bold, h1) terlihat rapi
                                            ->markdown(), // Opsional, jaga-jaga jika tersimpan sebagai markdown
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // --- KOLOM KANAN (SIDEBAR - 1/3) ---
                        InfolistGroup::make()
                            ->schema([
                                
                                // Section 1: Status (Card Kecil)
                                InfolistSection::make('Status & Urgensi')
                                    ->icon('heroicon-m-flag')
                                    ->compact() // Agar padding lebih tipis (minimalis)
                                    ->schema([
                                        TextEntry::make('status')
                                            ->label('Status Saat Ini')
                                            ->badge()
                                            // Asumsi Enum StatusTugas punya method getLabel/getColor/getIcon
                                            // Jika tidak, Anda bisa mapping manual seperti di Table
                                            ->formatStateUsing(fn ($state) => $state instanceof StatusTugas ? $state->getLabel() : $state)
                                            ->color(fn ($state) => $state instanceof StatusTugas ? $state->getColor() : 'gray')
                                            ->icon(fn ($state) => $state instanceof StatusTugas ? $state->getIcon() : null),

                                        TextEntry::make('prioritas')
                                            ->badge()
                                            ->colors([
                                                'rendah' => 'success',
                                                'sedang' => 'info',
                                                'tinggi' => 'danger', // Menggunakan danger agar merah
                                            ])
                                            ->icons([
                                                'rendah' => 'heroicon-m-arrow-down',
                                                'sedang' => 'heroicon-m-minus',
                                                'tinggi' => 'heroicon-m-arrow-up',
                                            ]),
                                    ]),

                                // Section 2: Personil
                                InfolistSection::make('Tim Terlibat')
                                    ->icon('heroicon-m-users')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('karyawan.name')
                                            ->label('Ditugaskan Ke')
                                            ->icon('heroicon-m-user-circle')
                                            ->weight(FontWeight::Medium),
                                            
                                        TextEntry::make('creator.name')
                                            ->label('Pemberi Tugas')
                                            ->icon('heroicon-m-check-badge')
                                            ->color('gray'),
                                    ]),

                                // Section 3: Timeline
                                InfolistSection::make('Jadwal')
                                    ->icon('heroicon-m-calendar')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('tanggal_mulai')
                                            ->label('Mulai')
                                            ->date('d M Y')
                                            ->icon('heroicon-m-play'),

                                        TextEntry::make('deadline')
                                            ->label('Tenggat Waktu')
                                            ->date('d M Y')
                                            ->icon('heroicon-m-stop')
                                            ->color('danger'), // Merah biar eye-catching
                                            
                                        TextEntry::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->since() // Tampil "2 hours ago"
                                            ->size(TextEntry\TextEntrySize::Small)
                                            ->color('gray')
                                            ->separator(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjadwalanTugas::route('/'),
            'create' => Pages\CreatePenjadwalanTugas::route('/create'),
            'view' => Pages\ViewPenjadwalanTugas::route('/{record}'),
            'edit' => Pages\EditPenjadwalanTugas::route('/{record}/edit'),
        ];
    }
}
