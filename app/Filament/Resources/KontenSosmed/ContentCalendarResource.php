<?php

namespace App\Filament\Resources\KontenSosmed;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;
use App\Models\ContentCalendar;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Section::make('Informasi Konten')
                    ->schema([
                        Forms\Components\TextInput::make('judul')
                            ->label('Judul Konten')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('tanggal_publish')
                            ->label('Tanggal Publish')
                            ->native(false)
                            ->seconds(false)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => '📝 Draft',
                                'waiting' => '⏳ Waiting Approval',
                                'scheduled' => '📅 Scheduled',
                                'published' => '✅ Published',
                            ])
                            ->default('draft')
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('content_pillar')
                            ->label('Content Pillar')
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
                            ->label('Tipe Konten')
                            ->options([
                                'feed' => 'Feed',
                                'story' => 'Story',
                                'reels' => 'Reels',
                                'carousel' => 'Carousel',
                                'video' => 'Video',
                                'talking_head' => 'Talking Head',
                            ])
                            ->native(false),
                        Forms\Components\CheckboxList::make('platform')
                            ->label('Platform')
                            ->options([
                                'instagram' => 'Instagram',
                                'tiktok' => 'TikTok',
                                'facebook' => 'Facebook',
                                'twitter' => 'Twitter / X',
                                'youtube' => 'YouTube',
                                'linkedin' => 'LinkedIn',
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Konten')
                    ->schema([
                        Forms\Components\RichEditor::make('caption')
                            ->label('Caption')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('hashtag')
                            ->label('Hashtag')
                            ->placeholder('#arabica #kopi #promo')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('visual')
                            ->label('Visual / Attachment')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes(['image/*', 'video/*'])
                            ->directory('content-calendar')
                            ->maxFiles(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Penugasan')
                    ->schema([
                        Forms\Components\Select::make('pic')
                            ->label('PIC / Assigned To')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Internal')
                            ->rows(3),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => Filament::auth()->id())
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_publish')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->description(fn($record) => $record->tanggal_publish?->diffForHumans()),
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul Konten')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->judul),
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
                        'facebook' => '📘 FB',
                        'twitter' => '𝕏 Twitter',
                        'youtube' => '▶️ YT',
                        'linkedin' => '💼 LinkedIn',
                        default => $state,
                    })
                    ->color('gray'),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('PIC')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                Infolists\Components\Section::make('Informasi Konten')
                    ->schema([
                        Infolists\Components\TextEntry::make('judul')
                            ->label('Judul')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('tanggal_publish')
                            ->label('Jadwal Publish')
                            ->dateTime('d M Y H:i'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => ucfirst($state))
                            ->icon(fn(string $state) => ContentCalendar::statusIcon($state))
                            ->color(fn(string $state) => ContentCalendar::statusColor($state)),
                        Infolists\Components\TextEntry::make('content_pillar')
                            ->label('Content Pillar')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => ucfirst($state))
                            ->color(fn(string $state) => ContentCalendar::pillarColor($state)),
                        Infolists\Components\TextEntry::make('tipe_konten')
                            ->label('Tipe Konten')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn(?string $state) => ucfirst(str_replace('_', ' ', $state ?? '-'))),
                        Infolists\Components\TextEntry::make('platform')
                            ->label('Platform')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn(string $state) => ucfirst($state)),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Caption & Hashtag')
                    ->schema([
                        Infolists\Components\TextEntry::make('caption')
                            ->label('Caption')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('hashtag')
                            ->label('Hashtag')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                Infolists\Components\Section::make('Penugasan')
                    ->schema([
                        Infolists\Components\TextEntry::make('assignee.name')
                            ->label('PIC')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        Infolists\Components\TextEntry::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentCalendars::route('/'),
            'create' => Pages\CreateContentCalendar::route('/create'),
            'view' => Pages\ViewContentCalendar::route('/{record}'),
            'edit' => Pages\EditContentCalendar::route('/{record}/edit'),
        ];
    }
}
