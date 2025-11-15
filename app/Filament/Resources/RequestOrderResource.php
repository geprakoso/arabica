<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestOrderResource\Pages;
use App\Models\RequestOrder;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class RequestOrderResource extends Resource
{
    protected static ?string $model = RequestOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Request Order';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Permintaan')
                    ->schema([
                        Forms\Components\TextInput::make('no_ro')
                            ->label('No. RO')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(fn () => self::getKaryawanOptions())
                            ->getSearchResultsUsing(fn (string $search) => self::getKaryawanOptions($search))
                            ->getOptionLabelUsing(fn ($value) => self::getKaryawanLabelById($value))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\RichEditor::make('catatan')
                            ->label('Catatan')
                            ->nullable(),
                    ])->columns(2),

                Section::make('Daftar Produk')
                    ->schema([
                        Repeater::make('items')
                            ->label('Produk Diminta')
                            ->relationship('items')
                            ->minItems(1)
                            ->schema([
                                Forms\Components\Select::make('produk_id')
                                    ->label('Produk')
                                    ->relationship('produk', 'nama_produk' , )
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                            ])
                            ->reorderable(false)
                            ->columns(1),
                    ])->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ro')
                    ->label('No. RO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('karyawan.name')
                    ->label('Karyawan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->options(fn () => self::getKaryawanOptions(limit: null))
                    ->native(false),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestOrders::route('/'),
            'create' => Pages\CreateRequestOrder::route('/create'),
            'view' => Pages\ViewRequestOrder::route('/{record}'),
            'edit' => Pages\EditRequestOrder::route('/{record}/edit'),
        ];
    }

    protected static function getKaryawanOptions(?string $search = null, ?int $limit = 50): array
    {
        $query = self::karyawanBaseQuery();

        if ($search) {
            $userTable = (new User())->getTable();
            $query->where("{$userTable}.name", 'like', "%{$search}%");
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $userTable = (new User())->getTable();

        return $query
            ->orderBy("{$userTable}.name")
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => self::formatKaryawanLabel($user->name, $user->role_names)])
            ->toArray();
    }

    protected static function getKaryawanLabelById($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $userTable = (new User())->getTable();

        $record = self::karyawanBaseQuery()
            ->where("{$userTable}.id", $value)
            ->first();

        if (! $record) {
            return null;
        }

        return self::formatKaryawanLabel($record->name, $record->role_names);
    }

    protected static function karyawanBaseQuery()
    {
        $userTable = (new User())->getTable();
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolesTable = config('permission.table_names.roles', 'roles');

        return User::query()
            ->select([
                "{$userTable}.id",
                "{$userTable}.name",
                DB::raw("GROUP_CONCAT(DISTINCT {$rolesTable}.name) as role_names"),
            ])
            ->join($pivotTable, function ($join) use ($pivotTable, $userTable) {
                $join->on("{$pivotTable}.model_id", '=', "{$userTable}.id")
                    ->where("{$pivotTable}.model_type", '=', User::class);
            })
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivotTable}.role_id")
            ->groupBy("{$userTable}.id", "{$userTable}.name");
    }

    protected static function formatKaryawanLabel(string $name, ?string $roles): string
    {
        $roleLabel = self::formatRoleNames($roles);

        return $roleLabel ? "{$name} - {$roleLabel}" : $name;
    }

    protected static function formatRoleNames(?string $roles): ?string
    {
        if ($roles === null) {
            return null;
        }

        $parts = array_filter(array_map('trim', explode(',', $roles)));

        if (empty($parts)) {
            return null;
        }

        $unique = array_values(array_unique($parts));

        return implode(', ', $unique);
    }
}
