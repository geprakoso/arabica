<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockOpname;
use Filament\Resources\Resource;
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

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationParentItem = 'Inventory';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detail Opname')
                ->schema([
                    TextInput::make('kode')
                        ->label('Kode')
                        ->disabled()
                        ->default(fn () => StockOpname::generateKode())
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
                    ->label('Status')
                    ->badge()
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
                    ->requiresConfirmation()
                    ->visible(fn (StockOpname $record) => ! $record->isPosted())
                    ->action(fn (StockOpname $record) => $record->post(Auth::user()))
                    ->successNotificationTitle('Stock opname berhasil diposting.'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (StockOpname $record) => ! $record->isPosted()),
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
