<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockAdjustment;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Filament\Resources\StockAdjustmentResource\RelationManagers\ItemsRelationManager;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    // protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Penyesuaian Stok';

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationParentItem = 'Inventory';
    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detail Penyesuaian')
                ->schema([
                    TextInput::make('kode')
                        ->label('Kode')
                        ->disabled()
                        ->default(fn() => StockAdjustment::generateKode())
                        ->dehydrated(),
                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->native(false),
                    Select::make('gudang_id')
                        ->label('Gudang')
                        ->relationship('gudang', 'nama_gudang')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Textarea::make('catatan')
                        ->label('Catatan')
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
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'posted',
                    ]),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jumlah Item'),
            ])
            ->filters([])
            ->actions([
                Action::make('post')
                    ->label('Posting')
                    ->visible(fn(StockAdjustment $record) => ! $record->isPosted())
                    ->requiresConfirmation()
                    ->action(fn(StockAdjustment $record) => $record->post(Auth::user()))
                    ->successNotificationTitle('Penyesuaian stok berhasil diposting.'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(StockAdjustment $record) => ! $record->isPosted()),
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
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
