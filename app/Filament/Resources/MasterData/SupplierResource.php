<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\SupplierResource\Pages;
// use App\Filament\Resources\MasterData\SupplierResource\RelationManagers;
use App\Filament\Resources\MasterData\SupplierResource\RelationManagers\AgentsRelationManager;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Resource;
// use Filament\Forms\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Dom\Text;
use Filament\Tables\Columns\TextColumn;
// use Ramsey\Uuid\Type\Time;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-truck';
    // protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'Master Data';
    protected static ?string $navigationLabel = 'Supplier';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Tabs::make('memberTabs')
                        ->tabs([
                            Tab::make('Data Supplier')
                                ->schema([
                                    Forms\Components\TextInput::make('nama_supplier')
                                        ->label('Nama Supplier')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\TextInput::make('no_hp')
                                        ->label('No. HP')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->unique(ignoreRecord: true),
                                ]),
                            

                            Tab::make('Alamat')
                                ->schema([
                                    Forms\Components\TextInput::make('alamat')
                                        ->label('Alamat'),
                                    Forms\Components\TextInput::make('provinsi')
                                        ->label('Provinsi'),
                                    Forms\Components\TextInput::make('kota')
                                        ->label('Kota'),
                                    Forms\Components\TextInput::make('kecamatan')
                                        ->label('Kecamatan'),
                                    ]),
                            ]),
                    ])->from('lg')->columnSpanFull(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_supplier')
                    ->label('Nama Supplier')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),

            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
            AgentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
