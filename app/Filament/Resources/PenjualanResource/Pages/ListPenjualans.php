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

    public $bulkDeleteRecordIds = [];

    public function bulkDeleteStep2Action(): Actions\Action
    {
        return Actions\Action::make('bulkDeleteStep2')
            ->modalHeading('⚠️ Dampak Penghapusan Massal')
            ->modalDescription(function () {
                $records = \App\Models\Penjualan::whereIn('id_penjualan', $this->bulkDeleteRecordIds)->get();
                $ttCount = $records->filter(fn ($r) => $r->sumber_transaksi === 'tukar_tambah' || $r->tukarTambah()->exists())->count();
                
                $desc = "**Anda akan menghapus {$records->count()} penjualan.**\n\n";
                
                if ($ttCount > 0) {
                    $desc .= "**⚠️ PERINGATAN:** {$ttCount} data adalah bagian dari Tukar Tambah.\n";
                    $desc .= "Menghapus akan memutus relasi dengan data Tukar Tambah.\n\n";
                }

                $totalItems = $records->sum(fn ($r) => $r->items()->count());
                $totalJasa = $records->sum(fn ($r) => $r->jasaItems()->count());
                
                if ($totalItems > 0) {
                    $desc .= "- Total item barang: {$totalItems}\n";
                }
                if ($totalJasa > 0) {
                    $desc .= "- Total item jasa: {$totalJasa}\n";
                }

                $desc .= "\nApakah Anda yakin ingin melanjutkan?";

                return $desc;
            })
            ->modalSubmitActionLabel('Lanjut ke Password')
            ->modalWidth('md')
            ->action(function () {
                $this->replaceMountedAction('bulkDeleteStep3');
            });
    }

    public function bulkDeleteStep3Action(): Actions\Action
    {
        return Actions\Action::make('bulkDeleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan. Tindakan ini tidak dapat dibatalkan.')
            ->modalIcon('heroicon-o-lock-closed')
            ->form([
                \Filament\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->placeholder('Masukkan password akun Anda'),
            ])
            ->modalSubmitActionLabel('🔥 Hapus Data')
            ->action(function (array $data) {
                $user = auth()->user();
                if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                    
                    $records = \App\Models\Penjualan::whereIn('id_penjualan', $this->bulkDeleteRecordIds)->get();
                    $records->each->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title("{$records->count()} penjualan dihapus")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal menghapus')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                    $this->bulkDeleteRecordIds = [];
                }
            });
    }

    public $forceDeleteRecordId;

    public function forceDeleteStep2Action(): Actions\Action
    {
        return Actions\Action::make('forceDeleteStep2')
            ->modalHeading('⚠️ Dampak Penghapusan Permanen')
            ->modalDescription(function () {
                $record = \App\Models\Penjualan::withTrashed()->find($this->forceDeleteRecordId);
                if (! $record) {
                    return 'Data tidak ditemukan.';
                }

                $desc = "**Anda akan MENGHAPUS PERMANEN penjualan ini.**\n\n";
                $desc .= "**No Nota:** {$record->no_nota}\n\n";

                if ($record->sumber_transaksi === 'tukar_tambah' || $record->tukarTambah()->exists()) {
                    $ttKode = $record->tukarTambah?->kode ?? 'TT-XXXX';
                    $desc .= "**⚠️ PERINGATAN:** Transaksi ini adalah Tukar Tambah ({$ttKode}).\n";
                    $desc .= "Menghapus akan memutus relasi permanen.\n\n";
                }

                $itemsCount = $record->items()->count();
                $jasaCount = $record->jasaItems()->count();
                
                if ($itemsCount > 0) {
                    $desc .= "- Item barang: {$itemsCount}\n";
                }
                if ($jasaCount > 0) {
                    $desc .= "- Item jasa: {$jasaCount}\n";
                }

                $desc .= "\n**Data ini TIDAK DAPAT DIPULIHKAN!**";

                return $desc;
            })
            ->modalSubmitActionLabel('Lanjut ke Password')
            ->modalWidth('md')
            ->action(function () {
                $this->replaceMountedAction('forceDeleteStep3');
            });
    }

    public function forceDeleteStep3Action(): Actions\Action
    {
        return Actions\Action::make('forceDeleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan permanen. Tindakan ini TIDAK DAPAT DIBATALKAN.')
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
                $user = auth()->user();
                if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                    
                    $record = \App\Models\Penjualan::withTrashed()->find($this->forceDeleteRecordId);
                    if ($record) {
                        $record->forceDelete();
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Penjualan dihapus permanen')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal menghapus')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                    $this->forceDeleteRecordId = null;
                }
            });
    }

    public $bulkForceDeleteRecordIds = [];

    public function bulkForceDeleteStep2Action(): Actions\Action
    {
        return Actions\Action::make('bulkForceDeleteStep2')
            ->modalHeading('⚠️ Dampak Penghapusan Permanen Massal')
            ->modalDescription(function () {
                $records = \App\Models\Penjualan::withTrashed()->whereIn('id_penjualan', $this->bulkForceDeleteRecordIds)->get();
                $ttCount = $records->filter(fn ($r) => $r->sumber_transaksi === 'tukar_tambah' || $r->tukarTambah()->exists())->count();
                
                $desc = "**Anda akan MENGHAPUS PERMANEN {$records->count()} penjualan.**\n\n";
                
                if ($ttCount > 0) {
                    $desc .= "**⚠️ PERINGATAN:** {$ttCount} data adalah bagian dari Tukar Tambah.\n";
                    $desc .= "Menghapus akan memutus relasi permanen.\n\n";
                }

                $totalItems = $records->sum(fn ($r) => $r->items()->count());
                $totalJasa = $records->sum(fn ($r) => $r->jasaItems()->count());
                
                if ($totalItems > 0) {
                    $desc .= "- Total item barang: {$totalItems}\n";
                }
                if ($totalJasa > 0) {
                    $desc .= "- Total item jasa: {$totalJasa}\n";
                }

                $desc .= "\n**Data ini TIDAK DAPAT DIPULIHKAN!**";

                return $desc;
            })
            ->modalSubmitActionLabel('Lanjut ke Password')
            ->modalWidth('md')
            ->action(function () {
                $this->replaceMountedAction('bulkForceDeleteStep3');
            });
    }

    public function bulkForceDeleteStep3Action(): Actions\Action
    {
        return Actions\Action::make('bulkForceDeleteStep3')
            ->modalHeading('🔐 Konfirmasi Password')
            ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan permanen. Tindakan ini TIDAK DAPAT DIBATALKAN.')
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
                $user = auth()->user();
                if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password salah')
                        ->body('Password yang Anda masukkan tidak sesuai.')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                    
                    $records = \App\Models\Penjualan::withTrashed()->whereIn('id_penjualan', $this->bulkForceDeleteRecordIds)->get();
                    $records->each->forceDelete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title("{$records->count()} penjualan dihapus permanen")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal menghapus')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                    $this->bulkForceDeleteRecordIds = [];
                }
            });
    }
}
