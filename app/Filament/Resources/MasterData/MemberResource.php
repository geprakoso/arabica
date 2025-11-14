<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Member';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Data Member')
                    ->schema([
                        TextInput::make('nama_member')
                            ->label('Nama Member')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('no_hp')
                            ->label('No. HP')
                            ->required()
                            ->unique(ignoreRecord: true),
                        FileUpload::make('image_url')
                            ->label('Gambar Produk')
                            ->image()
                            ->disk('public')
                            ->directory(fn () => 'produks/' . now()->format('Y/m/d'))
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                $datePrefix = now()->format('ymd');
                                $slug = Str::slug($get('nama_produk') ?? 'produk');
                                $extension = $file->getClientOriginalExtension();
                                return "{$datePrefix}-{$slug}.{$extension}";
                            })
                            ->preserveFilenames()
                            ->nullable(),
                    ]),
                Section::make('Alamat Member')
                    ->schema([
                        TextInput::make('alamat')
                            ->label('Alamat'),
                        TextInput::make('provinsi')
                            ->label('Provinsi'),
                        TextInput::make('kota')
                            ->label('Kota'),
                        TextInput::make('kecamatan')
                            ->label('Kecamatan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_member')
                    ->label('Nama Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->sortable(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}
