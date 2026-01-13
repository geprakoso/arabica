<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Enums\StatusTugas;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;
use App\Models\PenjadwalanTugas;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PenjadwalanTugasResource extends BaseResource
{
    protected static ?string $model = PenjadwalanTugas::class;

    protected static ?string $navigationIcon = 'hugeicons-task-daily-01';

    protected static ?string $navigationGroup = 'Tugas';

    protected static ?string $navigationLabel = 'Tugas Harian';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withCount('comments')
            ->with(['latestComment', 'currentUserView']);
    }

    protected static ?array $badgeCounts = null;

    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        if (static::$badgeCounts === null) {
            $baseQuery = static::getEloquentQuery()
                ->where(function ($query) use ($userId) {
                    $query->where('created_by', $userId)
                        ->orWhereHas('karyawan', fn ($q) => $q->where('users.id', $userId));
                });

            $newRecords = (clone $baseQuery)
                ->whereDoesntHave('views', fn ($q) => $q->where('user_id', $userId))
                ->count();

            $unreadComments = (clone $baseQuery)
                ->whereHas('comments', function ($q) use ($userId) {
                    $q->where('created_at', '>', function ($sub) use ($userId) {
                        $sub->select('last_viewed_at')
                            ->from('task_views')
                            ->whereColumn('task_views.penjadwalan_tugas_id', 'penjadwalan_tugas.id')
                            ->where('task_views.user_id', $userId)
                            ->limit(1);
                    });
                })
                ->count();

            static::$badgeCounts = [
                'new' => $newRecords,
                'comments' => $unreadComments,
            ];
        }

        $new = static::$badgeCounts['new'];
        $comments = static::$badgeCounts['comments'];

        if ($new > 0 && $comments > 0) {
            return "{$new} 🆕 | {$comments} 💬";
        }

        if ($new > 0) {
            return "{$new} 🆕";
        }
        if ($comments > 0) {
            return "{$comments} 💬";
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Trigger calculation if not done yet
        if (static::$badgeCounts === null) {
            static::getNavigationBadge();
        }

        $new = static::$badgeCounts['new'] ?? 0;
        $comments = static::$badgeCounts['comments'] ?? 0;

        if ($new > 0) {
            return 'info'; // Blue for New Records
        }

        if ($comments > 0) {
            return 'success'; // Green for New Comments
        }

        return null;
    }

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
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Contoh: Perbaikan Bug Login')
                                            ->columnSpanFull(),

                                        RichEditor::make('deskripsi')
                                            ->label('Deskripsi Tugas')
                                            ->required()
                                            ->columnSpanFull(),

                                        Select::make('durasi_pengerjaan')
                                            ->label('Durasi')
                                            ->options([
                                                '1' => '1 Hari (Hari Ini)',
                                                '2' => '2 Hari',
                                                '3' => '3 Hari',
                                                'custom' => 'Lainnya (Manual)',
                                            ])
                                            ->default('1')
                                            ->required()
                                            ->live()
                                            ->afterStateHydrated(function ($component, $state, $record, $set) {
                                                if (! $record) {
                                                    return;
                                                }

                                                $start = $record->tanggal_mulai;
                                                $end = $record->deadline;

                                                if (! $start || ! $end) {
                                                    $set('durasi_pengerjaan', 'custom');

                                                    return;
                                                }

                                                $start = \Illuminate\Support\Carbon::parse($start);
                                                $end = \Illuminate\Support\Carbon::parse($end);

                                                $diff = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
                                                $isToday = $start->format('Y-m-d') === now()->format('Y-m-d');

                                                if ($diff === 1 && $isToday) {
                                                    $set('durasi_pengerjaan', '1');
                                                } elseif ($diff === 2) {
                                                    $set('durasi_pengerjaan', '2');
                                                } elseif ($diff === 3) {
                                                    $set('durasi_pengerjaan', '3');
                                                } else {
                                                    $set('durasi_pengerjaan', 'custom');
                                                }
                                            })
                                            ->dehydrated(),

                                        DatePicker::make('tanggal_mulai')
                                            ->label('Tanggal Mulai')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required()
                                            ->default(now())
                                            ->hidden(fn (Get $get) => $get('durasi_pengerjaan') !== 'custom')
                                            ->dehydrated(),

                                        DatePicker::make('deadline')
                                            ->label('Tenggat Waktu')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->minDate(fn (Get $get) => $get('tanggal_mulai'))
                                            ->required()
                                            ->hidden(fn (Get $get) => $get('durasi_pengerjaan') !== 'custom')
                                            ->dehydrated(),
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
                                            ->default('rendah')
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
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Prioritas belum dipilih',
                                            ]),
                                    ]),

                                // Section 2: Penugasan (Orang)
                                FormsSection::make('Penugasan')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Select::make('karyawan')
                                            ->label('Ditugaskan Kepada')
                                            ->relationship(
                                                name: 'karyawan',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->whereHas('roles', function ($q) {
                                                    // Optional filter
                                                })
                                            )
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Karyawan belum dipilih',
                                            ]),

                                        Select::make('created_by')
                                            ->label('Pemberi Tugas')
                                            ->relationship('creator', 'name')
                                            ->default(fn () => Filament::auth()->id())
                                            ->disabled()
                                            ->visible(false)
                                            ->dehydrated()
                                            ->required(),
                                    ]),

                                // Section 3: Waktu (Tanggal)
                                FormsSection::make('Durasi Pengerjaan')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Select::make('durasi_pengerjaan')
                                            ->label('Durasi')
                                            ->options([
                                                '1' => '1 Hari (Hari Ini)',
                                                '2' => '2 Hari',
                                                '3' => '3 Hari',
                                                'custom' => 'Lainnya (Manual)',
                                            ])
                                            ->default('1')
                                            ->required()
                                            ->live()
                                            ->afterStateHydrated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                $start = $record->tanggal_mulai;
                                                $end = $record->deadline;

                                                if (! $start || ! $end) {
                                                    $set('durasi_pengerjaan', 'custom');

                                                    return;
                                                }

                                                // Ensure Carbon instances
                                                $start = \Carbon\Carbon::parse($start);
                                                $end = \Carbon\Carbon::parse($end);

                                                // Calculate difference (inclusive)
                                                // Using startOfDay to ignore time components
                                                $diff = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

                                                // Robust Check: Start Date must be Today
                                                $isToday = $start->format('Y-m-d') === now()->format('Y-m-d');

                                                if (in_array($diff, [1, 2, 3]) && $isToday) {
                                                    $set('durasi_pengerjaan', (string) $diff);
                                                } else {
                                                    $set('durasi_pengerjaan', 'custom');
                                                }
                                            })
                                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                                if ($state === 'custom') {
                                                    return;
                                                }

                                                $days = (int) $state;
                                                if ($days > 0) {
                                                    $set('tanggal_mulai', now()->toDateString()); // Use Carbon instance or string
                                                    $set('deadline', now()->addDays($days - 1)->toDateString());
                                                }
                                            }),

                                        DatePicker::make('tanggal_mulai')
                                            ->label('Tanggal Mulai')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required()
                                            ->default(now())
                                            ->hidden(fn (Get $get) => $get('durasi_pengerjaan') !== 'custom')
                                            ->dehydrated(),

                                        DatePicker::make('deadline')
                                            ->label('Tenggat Waktu')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->minDate(fn (Get $get) => $get('tanggal_mulai'))
                                            ->required()
                                            ->hidden(fn (Get $get) => $get('durasi_pengerjaan') !== 'custom')
                                            ->dehydrated(),
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
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
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
                TextColumn::make('unread_comments')
                    ->label('Diskusi')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->state(fn (?PenjadwalanTugas $record) => ($record?->comments_count ?? 0) > 0 ? $record->comments_count : null)
                    ->badge()
                    ->color(function (?PenjadwalanTugas $record) {
                        if (! $record) {
                            return 'gray';
                        }

                        $lastComment = $record->latestComment;

                        if (! $lastComment) {
                            return 'gray';
                        }

                        // Check when User last viewed this task (Eager Loaded)
                        $lastView = $record->currentUserView;

                        if (! $lastView) {
                            // Never viewed but has content -> New
                            return 'success';
                        }

                        // If new comment is newer than last view
                        return $lastComment->created_at->gt($lastView->last_viewed_at) ? 'success' : 'gray';
                    }),
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
                        // ... existing grid ...
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
                                \Filament\Infolists\Components\ViewEntry::make('comments')
                                    ->view('filament.infolists.entries.task-comments'),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // --- KOLOM KANAN (SIDEBAR - 1/3) ---
                        InfolistGroup::make()
                            ->schema([
                                // ... existing sidebar ...
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
                                            ->badge()
                                            ->separator(',')
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
