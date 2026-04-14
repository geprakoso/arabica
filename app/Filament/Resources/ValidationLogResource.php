<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationLogResource\Pages;
use App\Models\ValidationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ValidationLogResource extends Resource
{
    protected static ?string $model = ValidationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Validation Logs';

    protected static ?string $pluralLabel = 'Validation Logs';

    protected static ?string $label = 'Validation Log';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Error Information')
                    ->schema([
                        Forms\Components\TextInput::make('source_type')
                            ->label('Source')
                            ->disabled(),
                        Forms\Components\TextInput::make('source_action')
                            ->label('Action')
                            ->disabled(),
                        Forms\Components\TextInput::make('validation_type')
                            ->label('Validation Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('field_name')
                            ->label('Field')
                            ->disabled(),
                        Forms\Components\TextInput::make('error_code')
                            ->label('Error Code')
                            ->disabled(),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Input Data')
                    ->schema([
                        Forms\Components\KeyValue::make('input_data')
                            ->label('Input Data (JSON)')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Context')
                    ->schema([
                        Forms\Components\TextInput::make('user_name')
                            ->label('User')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        Forms\Components\TextInput::make('method')
                            ->label('HTTP Method')
                            ->disabled(),
                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->disabled()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Resolution')
                    ->schema([
                        Forms\Components\Toggle::make('is_resolved')
                            ->label('Resolved')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Resolved At')
                            ->disabled(),
                        Forms\Components\TextInput::make('resolved_by')
                            ->label('Resolved By (User ID)')
                            ->disabled(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Waktu'),

                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'info' => 'info',
                        'warning' => 'warning',
                        'danger' => fn ($state) => in_array($state, ['error', 'critical']),
                    ])
                    ->label('Severity'),

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('validation_type')
                    ->label('Type')
                    ->badge()
                    ->color('secondary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->searchable()
                    ->label('Error'),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('User')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->boolean()
                    ->label('Resolved'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Source')
                    ->options([
                        'Penjualan' => 'Penjualan',
                        'Pembelian' => 'Pembelian',
                        'TukarTambah' => 'Tukar Tambah',
                    ]),

                Tables\Filters\SelectFilter::make('validation_type')
                    ->label('Validation Type')
                    ->options([
                        'duplicate' => 'Duplikat',
                        'stock' => 'Stok',
                        'required' => 'Wajib Diisi',
                        'format' => 'Format',
                        'business_rule' => 'Aturan Bisnis',
                        'minimum_items' => 'Minimum Items',
                        'batch_not_found' => 'Batch Not Found',
                    ]),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severity')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'critical' => 'Critical',
                    ]),

                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Resolved')
                    ->falseLabel('Unresolved'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('created_at', '<=', $data['to']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('resolve')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Resolved')
                    ->modalDescription('Are you sure you want to mark this validation error as resolved?')
                    ->visible(fn ($record) => ! $record->is_resolved)
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->placeholder('Optional notes about how this was resolved...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsResolved($data['resolution_notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Validation log marked as resolved')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolve')
                        ->label('Mark Resolved')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->is_resolved) {
                                    $record->markAsResolved('Bulk resolved');
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("{$count} validation logs marked as resolved")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidationLogs::route('/'),
            'view' => Pages\ViewValidationLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
