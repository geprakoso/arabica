<?php

namespace App\Filament\Resources\RmaResource\Pages;

use App\Filament\Resources\RmaResource;
use App\Support\WebpUpload;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewRma extends ViewRecord
{
    protected static string $resource = RmaResource::class;

    protected static ?string $title = 'Detail RMA';

    protected function getHeaderActions(): array
    {
        $statusActions = collect([
            'di_packing' => ['Di Packing', 'warning', 'heroicon-m-archive-box-arrow-down'],
            'proses_klaim' => ['Proses Klaim', 'info', 'heroicon-m-arrow-path'],
            'selesai' => ['Selesai', 'success', 'heroicon-m-check-circle'],
        ])->map(function (array $meta, string $status): Actions\Action {
            return Actions\Action::make('status_' . $status)
                ->label($meta[0])
                ->color($meta[1])
                ->icon($meta[2])
                ->visible(fn () => $this->getRecord()->status_garansi !== $status)
                ->action(function () use ($status): void {
                    $record = $this->getRecord();
                    $record->update(['status_garansi' => $status]);

                    Notification::make()
                        ->title('Status diperbarui')
                        ->success()
                        ->send();
                });
        })->all();

        return [
            Actions\ActionGroup::make($statusActions)
                ->label('Status')
                ->icon('heroicon-m-adjustments-horizontal')
                ->tooltip('Ubah status cepat')
                ->button(),
            Action::make('upload_dokumen')
                ->label('Upload Foto')
                ->icon('heroicon-m-camera')
                ->color('success')
                ->modalHeading('Upload Foto Dokumentasi')
                ->modalDescription('Upload foto barang atau kondisi RMA.')
                ->modalWidth('md')
                ->form([
                    FileUpload::make('foto')
                        ->label('Foto Dokumentasi')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->default(fn() => $this->record->foto_dokumen ?? [])
                        ->panelLayout('grid')
                        ->panelAspectRatio('1:1')
                        ->imagePreviewHeight('100')
                        ->disk('public')
                        ->visibility('public')
                        ->directory('rma/dokumentasi')
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
                        ->body(count($data['foto'] ?? []) . ' foto ditambahkan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['foto_dokumen']);
                }),
        ];
    }
}
