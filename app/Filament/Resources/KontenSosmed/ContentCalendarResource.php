<?php

namespace App\Filament\Resources\KontenSosmed;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Infolists;
use Filament\Forms\Form;
use Actions\CreateAction;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\ContentCalendar;
use Filament\Infolists\Infolist;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

class ContentCalendarResource extends BaseResource
{
    protected static ?string $model = ContentCalendar::class;

    protected static ?string $navigationGroup = 'Konten Sosmed';

    protected static ?string $navigationLabel = 'Kalender Konten';

    protected static ?string $title = 'Kalender Konten';

    protected static ?string $pluralModelLabel = 'Kalender Konten';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'judul';

    public static function getGloballySearchableAttributes(): array
    {
        return ['judul', 'caption', 'hashtag'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Draft Konten')
                        ->description('Masukkan detail utama dari konten yang akan dipublikasikan.')
                        ->schema([
                            Forms\Components\TextInput::make('judul')
                                ->label('Judul Konten')
                                ->placeholder('Contoh: Promo Spesial Akhir Tahun')
                                ->prefixIcon('heroicon-m-document-text')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('caption')
                                ->label('Caption / Body Teks')
                                ->placeholder('Tuliskan isi caption konten di sini...')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'strike',
                                    'link',
                                    'h2',
                                    'h3',
                                    'bulletList',
                                    'orderedList',
                                    'redo',
                                    'undo',
                                ])
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('hashtag')
                                ->label('Hashtag')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->placeholder('arabica, kopi, promo (pisahkan dengan koma atau spasi)')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ]),
                    Forms\Components\Section::make('Aset Visual')
                        ->description('Unggah gambar atau video terkait konten ini.')
                        ->collapsible()
                        ->schema([
                            Forms\Components\FileUpload::make('visual')
                                ->hiddenLabel()
                                ->multiple()
                                ->image()
                                ->acceptedFileTypes(['image/*', 'video/*'])
                                ->directory('content-calendar')
                                ->maxFiles(5)
                                ->panelLayout('grid')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Penayangan')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Status Konten')
                                ->options([
                                    'draft' => '📝 Draft',
                                    'waiting' => '⏳ Waiting Approval',
                                    'scheduled' => '📅 Scheduled',
                                    'published' => '✅ Published',
                                ])
                                ->default('draft')
                                ->native(false)
                                ->required(),
                            Forms\Components\DateTimePicker::make('tanggal_publish')
                                ->label('Jadwal Tayang')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('d M Y, H:i')
                                ->prefixIcon('heroicon-m-calendar-days')
                                ->required(),
                            Forms\Components\CheckboxList::make('akun')
                                ->label('Akun')
                                ->options([
                                    'Haen Komputer' => 'Haen Komputer',
                                    'Haen Teknologi Kudus' => 'Haen Teknologi Kudus',
                                    'Haen Teknologi' => 'Haen Teknologi Pati',
                                    'Haen Teknologi Nusantara' => 'Haen Teknologi Nusantara',
                                    'Haen Software' => 'Haen Software',
                                ])
                                ->columns(1),
                            Forms\Components\CheckboxList::make('platform')
                                ->label('Platform Sosial Media')
                                ->options([
                                    'instagram' => 'Instagram',
                                    'tiktok' => 'TikTok',
                                    'whatsapp' => 'WhatsApp',
                                    'facebook' => 'Facebook',
                                    'twitter' => 'Twitter / X',
                                    'youtube' => 'YouTube',
                                    'linkedin' => 'LinkedIn',
                                ])
                                ->columns(2)
                                ->gridDirection('row'),
                        ]),
                    Forms\Components\Section::make('Kategori')
                        ->schema([
                            Forms\Components\Select::make('content_pillar')
                                ->label('Pillar Konten')
                                ->options([
                                    'edukasi' => '📚 Edukasi',
                                    'promo' => '🔥 Promo',
                                    'branding' => '🏷️ Branding',
                                    'engagement' => '💬 Engagement',
                                    'testimoni' => '⭐ Testimoni',
                                ])
                                ->native(false)
                                ->required(),
                            Forms\Components\Select::make('tipe_konten')
                                ->label('Tipe / Format')
                                ->options([
                                    'feed' => 'Feed',
                                    'story' => 'Story',
                                    'short' => 'Short',
                                    'reels' => 'Reels',
                                    'carousel' => 'Carousel',
                                    'video' => 'Video',
                                    'talking_head' => 'Talking Head',
                                ])
                                ->native(false),
                        ]),
                    Forms\Components\Section::make('Penugasan')
                        ->schema([
                            Forms\Components\Select::make('pic')
                                ->label('PIC / Kreator')
                                ->relationship('assignee', 'name')
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-user')
                                ->native(false),
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan Internal')
                                ->placeholder('Masukkan catatan khusus untuk tim...')
                                ->rows(3),
                            Forms\Components\Hidden::make('created_by')
                                ->default(fn() => Filament::auth()->id())
                                ->dehydrated(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(['lg' => 3]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction('view')
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul Konten')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn($state) => str($state)->title())
                    ->limit(40)
                    ->description(fn($record) => str($record->caption ?? '')->stripTags()->limit(40) ?: 'Tidak ada caption')
                    ->tooltip(fn($record) => $record->judul),
                Tables\Columns\TextColumn::make('tanggal_publish')
                    ->label('Jadwal Tayang')
                    ->dateTime('d M Y • H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar-days')
                    ->iconColor('primary')
                    ->description(fn($record) => $record->tanggal_publish?->diffForHumans()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'draft' => 'Draft',
                        'waiting' => 'Waiting',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                        default => $state,
                    })
                    ->icon(fn(string $state) => ContentCalendar::statusIcon($state))
                    ->color(fn(string $state) => ContentCalendar::statusColor($state)),
                Tables\Columns\TextColumn::make('content_pillar')
                    ->label('Pillar')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->color(fn(string $state) => ContentCalendar::pillarColor($state)),
                Tables\Columns\TextColumn::make('tipe_konten')
                    ->label('Tipe')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'feed' => 'Feed',
                        'story' => 'Story',
                        'short' => 'Short',
                        'reels' => 'Reels',
                        'carousel' => 'Carousel',
                        'video' => 'Video',
                        'talking_head' => 'Talking Head',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'instagram' => '📷 IG',
                        'tiktok' => '🎵 TikTok',
                        'whatsapp' => '📱 WhatsApp',
                        'facebook' => '📘 FB',
                        'twitter' => '𝕏 Twitter',
                        'youtube' => '▶️ YT',
                        'linkedin' => '💼 LinkedIn',
                        default => $state,
                    })
                    ->color('info'),
                Tables\Columns\TextColumn::make('akun')
                    ->label('Akun')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'Haen Komputer' => 'Haen Komputer',
                        'Haen Teknologi Kudus' => 'Haen Teknologi Kudus',
                        'Haen Teknologi' => 'Haen Teknologi Pati',
                        'Haen Teknologi Nusantara' => 'Haen Teknologi Nusantara',
                        'Haen Software' => 'Haen Software',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_publish', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'waiting' => 'Waiting',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                    ]),
                Tables\Filters\SelectFilter::make('content_pillar')
                    ->label('Content Pillar')
                    ->options([
                        'edukasi' => 'Edukasi',
                        'promo' => 'Promo',
                        'branding' => 'Branding',
                        'engagement' => 'Engagement',
                        'testimoni' => 'Testimoni',
                    ]),
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'instagram' => 'Instagram',
                        'tiktok' => 'TikTok',
                        'facebook' => 'Facebook',
                        'twitter' => 'Twitter / X',
                        'youtube' => 'YouTube',
                        'linkedin' => 'LinkedIn',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereJsonContains('platform', $data['value']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('pic')
                    ->label('PIC')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Konten')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->modalHeading('Buat Konten Baru'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
                Infolists\Components\Split::make([
                    Infolists\Components\Grid::make(1)
                        ->schema([
                            Infolists\Components\Section::make('Konten Utama')
                                ->schema([
                                    Infolists\Components\TextEntry::make('judul')
                                        ->hiddenLabel()
                                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                        ->columnSpanFull(),
                                    Infolists\Components\TextEntry::make('caption')
                                        ->label('Caption')
                                        ->html()
                                        ->prose()
                                        ->placeholder('Belum ada caption.')
                                        ->columnSpanFull(),
                                    Infolists\Components\TextEntry::make('hashtag')
                                        ->label('Hashtag')
                                        ->color('primary')
                                        ->placeholder('Tidak ada hashtag.')
                                        ->columnSpanFull(),
                                    Infolists\Components\ImageEntry::make('visual')
                                        ->label('Media Visual')
                                        ->columnSpanFull()
                                        ->placeholder('Belum ada unggahan visual.'),
                                    Infolists\Components\TextEntry::make('catatan')
                                        ->label('Catatan Khusus')
                                        ->color('gray')
                                        ->placeholder('-')
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->grow(),

                    Infolists\Components\Section::make('Atribut Penayangan')
                        ->schema([
                            Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn(string $state) => ucfirst($state))
                                ->icon(fn(string $state) => ContentCalendar::statusIcon($state))
                                ->color(fn(string $state) => ContentCalendar::statusColor($state)),
                            Infolists\Components\TextEntry::make('tanggal_publish')
                                ->label('Jadwal Tayang')
                                ->icon('heroicon-m-calendar-days')
                                ->dateTime('d M Y • H:i')
                                ->color('primary'),
                            Infolists\Components\TextEntry::make('assignee.name')
                                ->label('PIC / Kreator')
                                ->icon('heroicon-m-user-circle')
                                ->placeholder('Belum ditugaskan'),
                            Infolists\Components\TextEntry::make('platform')
                                ->label('Platform')
                                ->badge()
                                ->color('gray')
                                ->formatStateUsing(fn(string $state) => match ($state) {
                                    'instagram' => '📷 IG',
                                    'tiktok' => '🎵 TikTok',
                                    'facebook' => '📘 FB',
                                    'twitter' => '𝕏 Twitter',
                                    'youtube' => '▶️ YT',
                                    'linkedin' => '💼 LinkedIn',
                                    default => $state,
                                }),
                            Infolists\Components\TextEntry::make('akun')
                                ->label('Akun')
                                ->badge()
                                ->color('success')
                                ->formatStateUsing(fn(string $state) => match ($state) {
                                    'Haen Komputer' => 'Haen Komputer',
                                    'Haen Teknologi Kudus' => 'Haen Teknologi Kudus',
                                    'Haen Teknologi' => 'Haen Teknologi Pati',
                                    'Haen Teknologi Nusantara' => 'Haen Teknologi Nusantara',
                                    'Haen Software' => 'Haen Software',
                                    default => $state,
                                }),
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('content_pillar')
                                        ->label('Pillar')
                                        ->badge()
                                        ->formatStateUsing(fn(string $state) => ucfirst($state))
                                        ->color(fn(string $state) => ContentCalendar::pillarColor($state)),
                                    Infolists\Components\TextEntry::make('tipe_konten')
                                        ->label('Tipe')
                                        ->badge()
                                        ->color('gray')
                                        ->formatStateUsing(fn(?string $state) => ucfirst(str_replace('_', ' ', $state ?? '-'))),
                                ]),
                            Infolists\Components\TextEntry::make('creator.name')
                                ->label('Dibuat Oleh')
                                ->icon('heroicon-m-pencil-square')
                                ->color('gray'),
                        ])
                        ->grow(false),
                ])->from('md')
            ])->columns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentCalendars::route('/'),
            'create' => Pages\CreateContentCalendar::route('/create'),
            'edit' => Pages\EditContentCalendar::route('/{record}/edit'),
        ];
    }
}
