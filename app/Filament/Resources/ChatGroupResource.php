<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatGroupResource\Pages;
use App\Models\ChatGroup;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ChatGroupResource extends BaseResource
{
    protected static ?string $model = ChatGroup::class; // Tell Filament to use the ChatGroup model.

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Represent groups with a people icon.

    protected static ?string $navigationGroup = 'Pengaturan'; // Group resource under a dedicated sidebar section.

    protected static ?string $navigationLabel = 'Chat Groups'; // Sidebar label.

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Details')
                    ->description('Define the group identity and ownership.') // Explain what this section does.
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(120)
                            ->helperText('Displayed inside Chatify.') // Provide guidance for admins.
                            ->columnSpanFull(), // Use full width for better readability.
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(), // Let admins add optional context.
                        Forms\Components\Select::make('owner_id')
                            ->label('Group Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn() => Auth::id())
                            ->required()
                            ->helperText('Owners get elevated permissions when editing groups.'), // Clarify why this matters.
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->directory(config('chatify.user_avatar.folder'))
                            ->disk(config('chatify.storage_disk_name', 'public'))
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(config('chatify.attachments.max_upload_size') * 1024) // Convert MB config to KB for Filament.
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return Str::uuid() . '.' . $file->getClientOriginalExtension(); // Store only the filename so Chatify URLs work.
                            })
                            ->helperText('Optional image shown inside Chatify.')
                            ->columnSpanFull(), // Encourage a cohesive look.
                    ])
                    ->columns(2), // Keep form compact.
                Forms\Components\Section::make('Members')
                    ->description('Choose who participates in this group.') // Clarify purpose.
                    ->schema([
                        Forms\Components\Select::make('members')
                            ->label('Participants')
                            ->relationship('members', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Everyone listed here will see the group in Chatify.'), // Provide guidance.
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Group')
                    ->searchable()
                    ->sortable(), // Main display name.
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(), // Show who manages the group.
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->sortable(), // Quickly gauge group size.
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Updated')
                    ->sortable(), // Track recent changes.
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Allow quick edits.
                Tables\Actions\DeleteAction::make(), // Remove unused groups.
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Batch cleanup support.
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
            'index' => Pages\ListChatGroups::route('/'),
            'create' => Pages\CreateChatGroup::route('/create'),
            'edit' => Pages\EditChatGroup::route('/{record}/edit'),
        ];
    }
}
