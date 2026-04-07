<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PenjualanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Support\Enums\Alignment;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    public array $editBlockedPenjualanReferences = [];
    public array $deleteBlockedPenjualanReferences = [];
    public ?string $editBlockedMessage = null;
    public ?string $deleteBlockedMessage = null;

    // Force Delete properties (Godmode)
    public ?int $forceDeleteRecordId = null;
    public array $forceDeleteAffectedNotas = [];

    protected function getHeaderActions(): array
    {
        return [
                Actions\CreateAction::make()
                    ->label('Pembelian')
                    ->icon('heroicon-s-plus'),
        ];
    }

    protected function bulkDeleteBlockedAction(): Action
    {
        return Action::make('bulkDeleteBlocked')
            ->modalHeading('Sebagian gagal dihapus')
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

    // Force Delete Step 2: Show affected Penjualan
    public function forceDeleteStep2Action(): Action
    {
        return Action::make('forceDeleteStep2')
            ->modalHeading('⚠️ Penjualan yang Terpengaruh')
            ->modalDescription(function () {
                $record = \App\Models\Pembelian::find($this->forceDeleteRecordId);
                $isTukarTambah = $record?->tukarTambah()->exists();
                
                $desc = "";
                
                if ($isTukarTambah) {
                    $ttKode = $record->tukarTambah?->kode ?? 'TT-XXXXX';
                    $desc .= "**⚠️ PERINGATAN: Pembelian ini bagian dari Tukar Tambah ({$ttKode}).** Link ke Tukar Tambah akan diputus.\n\n";
                }

                if (empty($this->forceDeleteAffectedNotas)) {
                    $desc .= "Tidak ada Penjualan yang terpengaruh. Lanjutkan untuk konfirmasi password.";
                } else {
                    $notaList = implode(', ', $this->forceDeleteAffectedNotas);
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
                $desc = "Masukkan password Anda untuk mengkonfirmasi penghapusan permanen.\n\n";
                if (!empty($this->forceDeleteAffectedNotas)) {
                    $notaList = implode(', ', $this->forceDeleteAffectedNotas);
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
                if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();
                    return;
                }

                // Fetch the record by ID
                $record = \App\Models\Pembelian::find($this->forceDeleteRecordId);

                if (!$record) {
                    \Filament\Notifications\Notification::make()
                        ->title('Error')
                        ->body('Record tidak ditemukan.')
                        ->danger()
                        ->send();
                    return;
                }

                // Execute force delete
                $result = $record->forceDeleteWithCascade();

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

                // Reset state
                $this->forceDeleteRecordId = null;
                $this->forceDeleteAffectedNotas = [];
            })
            ->color('danger');
    }

    protected function buildPenjualanFooterActions(array $references): array
    {
        return collect($references)
            ->filter(fn (array $reference) => ! empty($reference['id']))
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
