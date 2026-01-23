<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use App\Support\WebpUpload;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    protected static ?string $title = 'Detail Pembelian';

    public array $editBlockedPenjualanReferences = [];

    public array $deleteBlockedPenjualanReferences = [];

    public ?string $editBlockedMessage = null;

    public ?string $deleteBlockedMessage = null;

    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return static::getModel()::with([
            'supplier',
            'karyawan',
            'tukarTambah',
            'requestOrders',
            'items.produk',
            'jasaItems.jasa',
            'pembayaran.akunTransaksi',
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
                ->modalDescription('Upload foto nota, invoice, atau bukti penerimaan barang.')
                ->modalWidth('md')
                ->form([
                    FileUpload::make('foto')
                        ->label('Foto Dokumentasi')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('pembelian/dokumentasi')
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
                    $existingPhotos = $this->record->foto_dokumen ?? [];
                    $newPhotos = $data['foto'] ?? [];

                    // Merge existing photos with new ones
                    $allPhotos = array_merge($existingPhotos, $newPhotos);

                    $this->record->update([
                        'foto_dokumen' => $allPhotos,
                    ]);

                    Notification::make()
                        ->title('Foto berhasil diupload')
                        ->body(count($newPhotos).' foto ditambahkan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['foto_dokumen']);
                }),
            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square')
                ->action(function (): void {
                    // if ($this->record->isEditLocked()) {
                    //     $this->editBlockedMessage = $this->record->getEditBlockedMessage();
                    //     $this->editBlockedPenjualanReferences = $this->record->getBlockedPenjualanReferences()->all();
                    //     $this->replaceMountedAction('editBlocked');
                    //     return;
                    // }
                    $this->redirect(PembelianResource::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Pembelian')
                ->modalDescription('Pembelian yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();
                    } catch (ValidationException $exception) {
                        $messages = collect($exception->errors())
                            ->flatten()
                            ->implode(' ');

                        $this->deleteBlockedMessage = $messages ?: 'Gagal menghapus pembelian.';
                        $this->deleteBlockedPenjualanReferences = $this->record->getBlockedPenjualanReferences()->all();
                        $this->replaceMountedAction('deleteBlocked');
                        $this->halt(true);
                    }

                    $this->redirect(PembelianResource::getUrl('index'));
                }),
        ];
    }

    protected function deleteBlockedAction(): Action
    {
        return Action::make('deleteBlocked')
            ->modalHeading('Gagal menghapus')
            ->modalDescription(fn () => $this->deleteBlockedMessage ?? 'Gagal menghapus pembelian.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->deleteBlockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function editBlockedAction(): Action
    {
        return Action::make('editBlocked')
            ->modalHeading('Tidak bisa edit')
            ->modalDescription(fn () => $this->editBlockedMessage ?? 'Pembelian tidak bisa diedit.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn () => $this->buildPenjualanFooterActions($this->editBlockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function buildPenjualanFooterActions(array $references): array
    {
        return collect($references)
            ->filter(fn (array $reference) => ! empty($reference['id']))
            ->map(function (array $reference, int $index) {
                $nota = $reference['nota'] ?? null;
                $label = $nota ? 'Lihat '.$nota : 'Lihat Penjualan';

                return StaticAction::make('viewPenjualan'.$index)
                    ->button()
                    ->label($label)
                    ->url(PenjualanResource::getUrl('view', ['record' => $reference['id'] ?? 0]))
                    ->openUrlInNewTab()
                    ->color('danger');
            })
            ->values()
            ->all();
    }
}
