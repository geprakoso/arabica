<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockOpname;
use App\Filament\Resources\BaseResource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers\ItemsRelationManager;

class StockOpnameResource extends BaseResource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stock Opname';
    // protected static ?string $navigationParentItem = 'Inventory & Stock';
    protected static ?string $navigationGroup = 'Inventory';
    // protected static ?string $navigationParentItem = 'Inventory';

    protected static ?string $pluralLabel = 'Stock Opname';

    protected static ?int $navigationSort = 50;

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
                        'draft' => 'heroicon-o-pencil-square',
                        'posted' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'posted',
                    ]),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('post')
                        ->label('Posting')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        // ->visible(fn(StockOpname $record) => ! $record->isPosted())
                        ->action(fn(StockOpname $record) => $record->post(Auth::user()))
                        ->successNotificationTitle('Stock opname berhasil diposting.')
                        ->color('success'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    // ->visible(fn(StockOpname $record) => ! $record->isPosted()),
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
