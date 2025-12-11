<?php

namespace App\Filament\Resources\MasterData\MemberResource\Pages;

use App\Filament\Resources\MasterData\MemberResource;
use App\Models\User; // Asumsi admin adalah User
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        // 1. Ambil data member yang baru dibuat
        $member = $this->record;

        // 2. Kirim Notifikasi Database ke semua Admin (User)
        // Catatan: Pastikan logic untuk filter admin sesuai aplikasi kamu
        $admins = User::all();

        if ($admins->isNotEmpty()) {
            Notification::make()
                ->title('Member Baru Bergabung')
                ->icon('hugeicons-user-add')
                ->body("**{$member->nama_member}** baru saja ditambahkan.")
                ->actions([
                    Action::make('Lihat')
                        ->url(MemberResource::getUrl('view', ['record' => $member])),
                ])
                ->sendToDatabase($admins);
        }

        // 3. Return notifikasi biasa untuk pembuat (User saat ini)
        return Notification::make()
            ->success()
            ->title('Member berhasil didaftarkan')
            ->body('Data member telah disimpan ke database.');
    }
}
