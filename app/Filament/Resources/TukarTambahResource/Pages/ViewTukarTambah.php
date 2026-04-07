<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Validation\ValidationException;

class ViewTukarTambah extends ViewRecord
{
    protected static string $resource = TukarTambahResource::class;

    public function getRelationManagers(): array
    {
        return [];
    }
    public array $editBlockedPenjualanReferences = [];
    public array $deleteBlockedPenjualanReferences = [];
    public ?string $editBlockedMessage = null;
    public ?string $deleteBlockedMessage = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil')
                ->action(function (): void {
                    // if ($this->record->isEditLocked()) {
                    //     $this->editBlockedMessage = $this->record->getEditBlockedMessage();
                    //     $this->editBlockedPenjualanReferences = $this->record->getExternalPenjualanReferences()->all();
                    //     $this->replaceMountedAction('editBlocked');
                    //     return;
                    // }
                    $this->redirect(TukarTambahResource::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Tukar Tambah')
                ->modalDescription('Tukar tambah yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();

                        Notification::make()
                            ->title('Tukar tambah dihapus')
                            ->success()
                            ->send();

                        $this->redirect(TukarTambahResource::getUrl('index'));
                    } catch (ValidationException $exception) {
                        $messages = collect($exception->errors())
                            ->flatten()
                            ->implode(' ');

                        $this->deleteBlockedMessage = $messages ?: 'Gagal menghapus tukar tambah.';
                        $this->deleteBlockedPenjualanReferences = $this->record->getExternalPenjualanReferences()->all();
                        $this->replaceMountedAction('deleteBlocked');
                    }
                }),
            Action::make('invoice')
                ->label('Invoice')
                ->icon('heroicon-m-printer')
                ->url(fn() => route('tukar-tambah.invoice', $this->record))
                ->openUrlInNewTab(),
            Action::make('invoice_simple')
                ->label('Invoice Simple')
                ->icon('heroicon-m-document-text')
                ->color('warning')
                ->url(fn() => route('tukar-tambah.invoice.simple', $this->record))
                ->openUrlInNewTab(),
            Action::make('upload_dokumen')
                ->label('Upload Foto')
                ->icon('heroicon-m-camera')
                ->color('success')
                ->modalHeading('Upload Foto Dokumentasi')
                ->modalDescription('Upload foto nota, invoice, atau bukti penerimaan barang.')
                ->modalWidth('md')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('foto')
                        ->label('Foto Dokumentasi')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1080')
                        ->disk('public')
                        ->directory('tukar-tambah/dokumentasi')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->saveUploadedFileUsing(function (\Filament\Forms\Components\BaseFileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                            return \App\Support\WebpUpload::store($component, $file, 80);
                        }),
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
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function editBlockedAction(): Action
    {
        return Action::make('editBlocked')
            ->modalHeading('Tidak bisa edit')
            ->modalDescription(fn() => $this->editBlockedMessage ?? 'Tukar tambah tidak bisa diedit.')
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn() => $this->buildPenjualanFooterActions($this->editBlockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function deleteBlockedAction(): Action
    {
        return Action::make('deleteBlocked')
            ->modalHeading('Gagal menghapus')
            ->modalDescription(fn() => $this->deleteBlockedMessage ?? 'Gagal menghapus tukar tambah.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalWidth('md')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActions(fn() => $this->buildPenjualanFooterActions($this->deleteBlockedPenjualanReferences))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
            ->color('danger');
    }

    protected function buildPenjualanFooterActions(array $references): array
    {
        return collect($references)
            ->filter(fn(array $reference) => ! empty($reference['id']))
            ->map(function (array $reference, int $index) {
                $nota = $reference['nota'] ?? null;
                $label = $nota ? 'Lihat ' . $nota : 'Lihat Penjualan';

                return StaticAction::make('viewPenjualan' . $index)
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
