<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjualans extends ListRecords
{
    protected static string $resource = PenjualanResource::class;

    public $deleteRecordId;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Penjualan')
                ->icon('hugeicons-plus-sign'),
        ];
    }

    public function deleteStep2Action(): Actions\Action
    {
        return Actions\Action::make('deleteStep2')
            ->modalHeading('⚠️ Dampak Penghapusan')
            ->modalDescription(function () {
                $record = \App\Models\Penjualan::find($this->deleteRecordId);
                if (! $record) {
                    return 'Data tidak ditemukan.';
                }

                $desc = "**Anda akan menghapus penjualan ini.**\n\n";

                // Items Impact
                $itemsCount = $record->items()->count();
                if ($itemsCount > 0) {
                    $desc .= "**Item yang akan dihapus ({$itemsCount}):**\n";
                    foreach ($record->items->take(5) as $item) {
                        $productName = $item->produk->nama_produk ?? 'Unknown Product';
                        $desc .= "- {$productName} (Qty: {$item->qty})\n";
                    }
                    if ($itemsCount > 5) {
                        $desc .= '- dan '.($itemsCount - 5)." item lainnya...\n";
                    }
                    $desc .= "\n";
                }

                // Payments Impact
                $paymentsCount = $record->pembayaran()->count();
                if ($paymentsCount > 0) {
                    $totalPayment = number_format($record->pembayaran()->sum('jumlah'), 0, ',', '.');
                    $desc .= "**Data Pembayaran yang akan dihapus ({$paymentsCount}):**\n";
                    $desc .= "Total: Rp {$totalPayment}\n\n";
                }

                // Tukar Tambah Impact
                if ($record->tukarTambah()->exists()) {
                    $ttKode = $record->tukarTambah->kode ?? 'TT-XXXX';
                    $desc .= "**⚠️ PERINGATAN: Transaksi ini adalah Tukar Tambah ({$ttKode}).**\n";
                    $desc .= "Menghapus penjualan ini akan MENGHAPUS data Tukar Tambah secara permanen.\n\n";
                }

                $desc .= 'Apakah Anda yakin ingin melanjutkan?';

                return $desc;
            })
            ->modalSubmitActionLabel('Lanjut ke Password')
            ->modalWidth('md')
            ->action(function () {
                $this->replaceMountedAction('deleteStep3');
            });
    }

    public function deleteStep3Action(): Actions\Action
    {
        return Actions\Action::make('deleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan permanen. Tindakan ini tidak dapat dibatalkan.')
            ->modalIcon('heroicon-o-lock-closed')
            ->form([
                \Filament\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->placeholder('Masukkan password akun Anda'),
            ])
            ->modalSubmitActionLabel('🔥 Hapus Permanen')
            ->action(function (array $data) {
                $record = \App\Models\Penjualan::find($this->deleteRecordId);
                if (! $record) {
                    \Filament\Notifications\Notification::make()->title('Data tidak ditemukan')->danger()->send();

                    return;
                }

                $user = auth()->user();
                if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();

                    // Keep modal open or reopen step 3?
                    // Filament closes modal on action. We need to halt or re-open.
                    // Ideally halt, but simple way is just notify and let user try again.
                    return;
                }

                try {
                    // Allow deletion of Tukar Tambah references
                    \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                    $record->delete();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal menghapus')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                } finally {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                }

                \Filament\Notifications\Notification::make()
                    ->title('Penjualan dihapus')
                    ->success()
                    ->send();
            });
    }
}
