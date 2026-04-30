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
                        ->default(fn() => $this->record->foto_dokumen ?? [])
                        ->panelLayout('grid')
                        ->panelAspectRatio('1:1')
                        ->imagePreviewHeight('100')
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

            // R16: Edit button - hidden if locked
            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square')
                ->visible(fn() => ! $this->record->is_locked)
                ->action(function (): void {
                    $this->redirect(PembelianResource::getUrl('edit', ['record' => $this->record]));
                }),

            // R12, R13: Delete button
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalCancelAction(false)
                ->modalHeading(
                    fn(): string => $this->record->canDelete()
                        ? 'Hapus Pembelian'
                        : 'Tidak Bisa Dihapus'
                )
                ->modalIcon(
                    fn(): string => $this->record->canDelete()
                        ? 'heroicon-o-trash'
                        : 'heroicon-o-exclamation-triangle'
                )
                ->modalIconColor(
                    fn(): string => $this->record->canDelete()
                        ? 'danger'
                        : 'warning'
                )
                ->modalDescription(function (): string {
                    if ($this->record->canDelete()) {
                        return 'Apakah Anda yakin ingin menghapus pembelian ' . $this->record->no_po . '? Tindakan ini tidak dapat dibatalkan.';
                    }

                    $reasons = [];

                    // R13: Cek NO TT
                    if (! empty($this->record->no_tt)) {
                        $reasons[] = '• Terikat Tukar Tambah: ' . $this->record->no_tt;
                    }

                    // R12: Cek penjualan
                    $notas = $this->record->getBlockedPenjualanReferences()->pluck('nota')->filter()->values();
                    if ($notas->isNotEmpty()) {
                        $reasons[] = '• Dipakai di penjualan: ' . $notas->implode(', ');
                    }

                    return implode("\n", $reasons) ?: 'Pembelian ini tidak dapat dihapus.';
                })
                ->modalSubmitAction(fn() => $this->record->canDelete() ? null : false)
                ->modalCancelActionLabel('Tutup')
                ->extraModalFooterActions(function (): array {
                    if ($this->record->canDelete()) {
                        return [];
                    }

                    return $this->record->getBlockedPenjualanReferences()
                        ->filter(fn(array $ref) => ! empty($ref['id']))
                        ->map(function (array $ref, int $index) {
                            $nota = $ref['nota'] ?? null;
                            $label = $nota ? 'Lihat ' . $nota : 'Lihat Penjualan';

                            return StaticAction::make('viewPenjualan' . $index)
                                ->button()
                                ->label($label)
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->url(PenjualanResource::getUrl('view', ['record' => $ref['id']]))
                                ->openUrlInNewTab()
                                ->color('warning');
                        })
                        ->values()
                        ->all();
                })
                ->action(function (): void {
                    if (! $this->record->canDelete()) {
                        return;
                    }

                    try {
                        $this->record->delete();
                    } catch (ValidationException $exception) {
                        // Godmode: Start Force Delete Flow (Step 1 -> Step 2)
                        if (auth()->user()?->hasRole('godmode')) {
                            $this->replaceMountedAction('forceDeleteStep2');
                            return;
                        }

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
            ->modalDescription(fn() => $this->deleteBlockedMessage ?? 'Gagal menghapus pembelian.')
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

    // Force Delete Step 2: Show affected Penjualan
    public function forceDeleteStep2Action(): Action
    {
        return Action::make('forceDeleteStep2')
            ->modalHeading('⚠️ Perhatian!')
            ->modalDescription(function () {
                $record = $this->record;
                $isTukarTambah = $record->tukarTambah()->exists();

                $desc = '';

                if ($isTukarTambah) {
                    $ttKode = $record->tukarTambah?->kode ?? 'TT-XXXXX';
                    $desc .= "⚠️ Dengan menghapus pembelian ini, Tukar Tambah ({$ttKode}) akan diputus. dan mungkin akan tidak dapat diakses lagi.\n\n";
                }

                $affectedNotas = $record->getBlockedPenjualanReferences()->pluck('nota')->toArray();
                if (empty($affectedNotas)) {
                    $desc .= 'Tidak ada Penjualan yang terpengaruh. Lanjutkan untuk konfirmasi password.';
                } else {
                    $notaList = implode(', ', $affectedNotas);
                    $desc .= "Penjualan berikut akan ditandai sebagai 'Nerf' (kehilangan referensi batch pembelian):\n\n**{$notaList}**";
                }

                return $desc;
            })
            ->modalIcon('heroicon-o-exclamation-circle')
            ->modalIconColor('warning')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Lanjutkan ke Konfirmasi Password')
            ->action(function (): void {
                $this->replaceMountedAction('forceDeleteStep3');
            })
            ->color('warning');
    }

    // Force Delete Step 3: Password Confirmation + Final List
    public function forceDeleteStep3Action(): Action
    {
        return Action::make('forceDeleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription(function () {
                $affectedNotas = $this->record->getBlockedPenjualanReferences()->pluck('nota')->toArray();

                $desc = "Masukkan password Anda untuk mengkonfirmasi penghapusan permanen.\n\n";
                if (! empty($affectedNotas)) {
                    $notaList = implode(', ', $affectedNotas);
                    $desc .= "**Penjualan yang akan di-Nerf:** {$notaList}";
                }

                return $desc;
            })
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('danger')
            ->modalWidth('md')
            ->form([
                \Filament\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->placeholder('Masukkan password akun Anda'),
            ])
            ->modalSubmitActionLabel('🔥 Hapus Permanen')
            ->action(function (array $data): void {
                $user = auth()->user();

                // Verify password
                if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();

                    return;
                }

                // Execute force delete
                $result = $this->record->forceDeleteWithCascade();

                $affectedCount = $result['affected_penjualan']->count();
                $affectedNotas = $result['affected_penjualan']->pluck('no_nota')->implode(', ');

                $body = 'Pembelian berhasil dihapus.';
                if ($affectedCount > 0) {
                    $body .= " {$affectedCount} Penjualan ditandai sebagai Nerf: {$affectedNotas}";
                }

                \Filament\Notifications\Notification::make()
                    ->title('Force Delete Berhasil')
                    ->body($body)
                    ->success()
                    ->send();

                $this->redirect(PembelianResource::getUrl('index'));
            })
            ->color('danger');
    }

    protected function editBlockedAction(): Action
    {
        return Action::make('editBlocked')
            ->modalHeading('Tidak bisa edit')
            ->modalDescription(fn() => $this->editBlockedMessage ?? 'Pembelian tidak bisa diedit.')
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
