@php
    $memberName = $penjualan->member?->nama_member ?? 'Pelanggan';
    $profileName = $profile?->name ?? 'Haen Komputer';
    $profileAddress = $profile?->address ?? null;
    $profilePhone = $profile?->phone ?? null;
    $note = trim((string) ($messageNote ?? ''));
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Invoice {{ $penjualan->no_nota }}</title>
    </head>
    <body style="font-family: Arial, sans-serif; color: #111827; margin: 0; padding: 0;">
        <div style="max-width: 640px; margin: 0 auto; padding: 24px;">
            <p>Halo {{ $memberName }},</p>
            <p>Terima kasih sudah berbelanja di {{ $profileName }} 🙌</p>
            <p>Invoice pembelian Anda kami lampirkan dalam format PDF.</p>
            <p>Jika ada pertanyaan terkait garansi, instalasi, atau klaim servis, silakan hubungi kami—kami siap bantu.</p>
            @if ($note !== '')
                <p>{{ $note }}</p>
            @endif
            <p>Salam,<br>{{ $profileName }}</p>
            @if ($profilePhone || $profileAddress)
                <p>
                    @if ($profilePhone)
                        {{ $profilePhone }}
                    @endif
                    @if ($profilePhone && $profileAddress)
                        |
                    @endif
                    @if ($profileAddress)
                        {{ $profileAddress }}
                    @endif
                </p>
            @endif
        </div>
    </body>
</html>
