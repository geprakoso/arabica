<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers\ItemsRelationManager;
use App\Models\StockOpname;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockOpnameResource extends BaseResource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stock Opname';

    // protected static ?string $navigationParentItem = 'Inventory & Stock';
    protected static ?string $navigationGroup = 'Inventori';
    // protected static ?string $navigationParentItem = 'Inventory';

    protected static ?string $pluralLabel = 'Stock Opname';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'gudang.nama_gudang', 'status'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Utama')
                ->description('Detail pelaksanaan stock opname gudang')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    TextInput::make('kode')
                        ->label('Kode Referensi')
                        ->prefixIcon('heroicon-o-tag')
                        ->disabled()
                        ->default(fn() => StockOpname::generateKode())
                        ->dehydrated()
                        ->helperText('Kode akan digenerate otomatis oleh sistem'),
                    DatePicker::make('tanggal')
                        ->default(now())
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->displayFormat('d F Y')
                        ->label('Tanggal Opname')
                        ->required()
                        ->native(false),
                    Select::make('gudang_id')
                        ->label('Lokasi Gudang')
                        ->relationship('gudang', 'nama_gudang')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->prefixIcon('heroicon-o-building-storefront')
                        ->placeholder('Pilih Gudang'),
                    Textarea::make('catatan')
                        ->label('Catatan Tambahan')
                        ->placeholder('Tuliskan catatan jika diperlukan...')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->icon('heroicon-o-tag')
                    ->copyable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),
                TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->weight('medium')
                    ->icon('heroicon-o-building-storefront')
                    ->placeholder('-'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->label('Jml Item'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn(string $state): string => match ($state) {
                        'draft' => 'heroicon-m-pencil-square',
                        'posted' => 'heroicon-m-check-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'posted',
                    ]),
            ])
            ->filters([])
            ->actions([
                Action::make('post')
                    ->button()
                    ->color('success')
                    ->visible(fn(StockOpname $record) => ! $record->isPosted())
                    ->icon('heroicon-m-paper-airplane')
                    ->label('Posting')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Posting Stock Opname')
                    ->modalDescription(function (StockOpname $record): string {
                        $summary = $record->getSummary();
                        return "Total Item: {$summary['total_items']}\n" .
                               "Selisih Positif (+): {$summary['total_selisih_positif']}\n" .
                               "Selisih Negatif (-): {$summary['total_selisih_negatif']}\n" .
                               "Tanpa Selisih: {$summary['total_tanpa_selisih']}\n\n" .
                               "Posting akan mengubah stok secara permanen. Lanjutkan?";
                    })
                    ->action(function (StockOpname $record) {
                        try {
                            $record->post(Auth::user());

                            Notification::make()
                                ->title('Stock Opname Berhasil Diposting')
                                ->body("Kode {$record->kode} berhasil diposting. Stok telah diperbarui.")
                                ->success()
                                ->send();

                        } catch (\Illuminate\Validation\ValidationException $e) {
                            $errors = $e->validator->errors()->all();
                            
                            Notification::make()
                                ->title('Validasi Gagal')
                                ->body(implode('\n', $errors))
                                ->danger()
                                ->persistent()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Memposting Stock Opname')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->label('Hapus')
                    ->visible(fn(StockOpname $record) => ! $record->isPosted()),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\ViewAction::make(),
                ])
                    ->label('Aksi')
                    ->visible(fn(StockOpname $record) => ! $record->isPosted())
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Menu Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }
}
