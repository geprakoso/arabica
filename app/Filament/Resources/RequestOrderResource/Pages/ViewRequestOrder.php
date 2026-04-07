<?php

namespace App\Filament\Resources\RequestOrderResource\Pages;

use App\Filament\Resources\RequestOrderResource;
use App\Support\WebpUpload;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewRequestOrder extends ViewRecord
{
    protected static string $resource = RequestOrderResource::class;

    public function resolveRecord(int|string $key): Model
    {
        return static::getModel()::with([
            'karyawan',
            'items.produk.brand',
            'items.produk.kategori',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_dokumen')
                ->label('Upload Foto')
                ->icon('heroicon-m-camera')
                ->color('success')
                ->modalHeading('Upload Foto Dokumentasi')
                ->modalDescription('Upload foto dokumentasi request order.')
                ->modalWidth('md')
                ->form([
                    FileUpload::make('foto')
                        ->label('Foto Dokumentasi')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->default(fn () => $this->record->foto_dokumen ?? [])
                        ->panelLayout('grid')
                        ->panelAspectRatio('1:1')
                        ->imagePreviewHeight('100')
                        ->disk('public')
                        ->visibility('public')
                        ->directory('request-order/dokumentasi')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1080')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                            return WebpUpload::store($component, $file, 80);
                        })
                        ->required()
                        ->openable()
                        ->downloadable(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'foto_dokumen' => $data['foto'] ?? [],
                    ]);

                    Notification::make()
                        ->title('Foto berhasil diupload')
                        ->body(count($data['foto'] ?? []).' foto tersimpan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['foto_dokumen']);
                }),
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return RequestOrderResource::infolist($infolist);
    }
}
