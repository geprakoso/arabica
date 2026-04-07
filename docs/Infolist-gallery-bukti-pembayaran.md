# Bukti Pembayaran Infolist Component

## Overview
A reusable infolist pattern for displaying payment proof images in a gallery grid format. Images are shown as small square thumbnails that open full-size when clicked.

## Implementation

### Prerequisites
```php
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\Storage;
```

### Code Template
```php
InfoSection::make('Bukti Pembayaran')
    ->visible(fn($record) => $record->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty())
    ->schema([
        RepeatableEntry::make('bukti_transfers')
            ->hiddenLabel()
            ->state(fn($record) => $record->pembayaran->whereNotNull('bukti_transfer')->values()->toArray())
            ->schema([
                ImageEntry::make('bukti_transfer')
                    ->hiddenLabel()
                    ->disk('public')
                    ->visibility('public')
                    ->width(100)
                    ->height(100)
                    ->extraImgAttributes([
                        'class' => 'rounded-md shadow-sm border border-gray-200 dark:border-gray-700 object-cover cursor-pointer',
                        'style' => 'aspect-ratio: 1/1;',
                    ])
                    ->url(fn ($state) => Storage::url($state))
                    ->openUrlInNewTab(),
            ])
            ->grid(10) // Adjust for desired columns
            ->contained(false),
    ]),
```

## Key Configuration

| Property | Value | Description |
|----------|-------|-------------|
| `->width(100)` | 100px | Thumbnail width |
| `->height(100)` | 100px | Thumbnail height |
| `aspect-ratio: 1/1` | 1:1 | Forces square shape |
| `object-cover` | CSS | Crops image to fit square |
| `cursor-pointer` | CSS | Shows clickable cursor |
| `->grid(10)` | 10 cols | Gallery columns (adjustable) |
| `->contained(false)` | - | Removes container padding |

## Adapting for Other Resources

### Step 1: Update the relationship path
Replace `pembayaran` with your payment relationship name:
```php
->state(fn($record) => $record->yourPaymentRelation->whereNotNull('image_field')->values()->toArray())
```

### Step 2: Update the image field name
Replace `bukti_transfer` with your image column name:
```php
ImageEntry::make('your_image_field')
```

### Step 3: Adjust visibility condition
```php
->visible(fn($record) => $record->yourPaymentRelation->whereNotNull('your_image_field')->isNotEmpty())
```

## Example: Penjualan Resource
```php
InfoSection::make('Bukti Pembayaran')
    ->visible(fn(Penjualan $record) => $record->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty())
    ->schema([
        RepeatableEntry::make('bukti_transfers')
            ->hiddenLabel()
            ->state(fn(Penjualan $record) => $record->pembayaran->whereNotNull('bukti_transfer')->values()->toArray())
            ->schema([
                ImageEntry::make('bukti_transfer')
                    // ... same configuration
            ])
            ->grid(10)
            ->contained(false),
    ]),
```

## Notes
- Images must be stored on `public` disk
- The `->values()->toArray()` is required for RepeatableEntry to iterate correctly
- Clicking thumbnail opens full image in new browser tab
