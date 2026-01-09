<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockAdjustment;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\PenjualanResource;
use App\Filament\Resources\StockAdjustmentResource\Pages;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class StockAdjustmentResource extends BaseResource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Penyesuaian Stok';
    protected static ?string $navigationGroup = 'Logistik & Inventori';
    protected static ?int $navigationSort = 51;

    protected static ?string $pluralLabel = 'Penyesuaian Stok';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)
                ->schema([
                    Group::make()
                        ->schema([
                            Section::make('Informasi Umum')
                                ->description('Detail data penyesuaian stok')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    Select::make('gudang_id')
                                        ->label('Gudang')
                                        ->relationship('gudang', 'nama_gudang')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(1),
                                    DatePicker::make('tanggal')
                                        ->label('Tanggal')
                                        ->prefixIcon('heroicon-o-calendar')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->columnSpan(1),
                                    Textarea::make('catatan')
                                        ->label('Catatan')
                                        ->placeholder('Tambahkan catatan jika diperlukan...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make('Item Penyesuaian')
                                ->description('Daftar item yang akan disesuaikan stoknya')
                                ->icon('heroicon-o-cube')
                                ->headerActions([])
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship('items')
                                        ->label('Daftar Item')
                                        ->schema([
                                            Select::make('produk_id')
                                                ->label('Produk')
                                                ->options(fn() => PenjualanResource::getAvailableProductOptions())
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->reactive()
                                                ->native(false)
                                                ->afterStateUpdated(fn(Set $set) => $set('pembelian_item_id', null))
                                                ->columnSpan(2),
                                            Select::make('pembelian_item_id')
                                                ->label('Batch')
                                                ->options(fn(Get $get) => PenjualanResource::getBatchOptions($get('produk_id') ? (int) $get('produk_id') : null))
                                                ->required()
                                                ->native(false)
                                                ->disabled(fn(Get $get) => ! $get('produk_id'))
                                                ->columnSpan(1),
                                            TextInput::make('qty')
                                                ->label('Qty (+/-)')
                                                ->numeric()
                                                ->required()
                                                ->placeholder('Contoh: -5')
                                                ->helperText('Gunakan (-) untuk mengurangi.')
                                                ->columnSpan(1),
                                            TextInput::make('keterangan')
                                                ->label('Keterangan Item')
                                                ->placeholder('Alasan penyesuaian item ini')
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(4)
                                        ->defaultItems(1)
                                        ->addActionLabel('Tambah Item')
                                        ->collapseAllAction(
                                            fn($action) => $action->label('Tutup Semua'),
                                        )
                                        ->itemLabel(fn(array $state): ?string => $state['produk_id'] ?? null ? (PenjualanResource::getAvailableProductOptions()[$state['produk_id']] ?? 'Item') : 'Item Baru'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    Group::make()
                        ->schema([
                            Section::make('Status & Info')
                                ->icon('heroicon-o-cube')
                                ->description('Informasi dokumen penyesuaian stok')
                                ->schema([
                                    TextInput::make('kode')
                                        ->label('Kode Referensi')
                                        ->prefixIcon('heroicon-o-tag')
                                        ->disabled()
                                        ->default(fn() => StockAdjustment::generateKode())
                                        ->dehydrated()
                                        ->readOnly(),

                                    TextInput::make('status')
                                        ->label('Status Dokumen')
                                        ->prefixIcon('heroicon-o-pencil')
                                        ->default('draft')
                                        ->disabled()
                                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                                    // Placeholder for created_at if we want to show it on edit
                                    Placeholder::make('created_at')
                                        ->label('Dibuat Pada')
                                        ->visible(fn($record) => $record !== null)
                                        ->content(fn($record) => $record?->created_at?->toFormattedDateString()),
                                ])
                                ->columnSpan(['lg' => 1]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->color('primary')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag')
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-o-calendar')
                    ->sortable(),
                TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->icon('heroicon-o-home')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'posted',
                    ])
                    ->icon(fn(string $state): string => match ($state) {
                        'draft' => 'heroicon-m-pencil',
                        'posted' => 'heroicon-m-check-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Item')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gudang_id')
                    ->relationship('gudang', 'nama_gudang')
                    ->label('Filter Gudang'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                    ]),
            ])
            ->actions([
                Action::make('post')
                    ->label('Posting')
                    ->button()
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->visible(fn(StockAdjustment $record) => ! $record->isPosted())
                    ->requiresConfirmation()
                    ->modalHeading('Posting Penyesuaian Stok')
                    ->modalDescription('Apakah Anda yakin ingin memposting penyesuaian ini? Stok akan diperbarui secara permanen.')
                    ->modalSubmitActionLabel('Ya, Posting')
                    ->action(fn(StockAdjustment $record) => $record->post(Auth::user()))
                    ->successNotificationTitle('Penyesuaian stok berhasil diposting.'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->button()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->visible(fn(StockAdjustment $record) => ! $record->isPosted()),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('primary'),
                    Tables\Actions\ViewAction::make(),
                ])
                    ->label('Aksi')
                    ->tooltip('Menu Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->visible(fn(StockAdjustment $record) => ! $record->isPosted()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // Relations have been moved to the main form for a better UX
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
