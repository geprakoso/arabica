@php
    $state = $getState();
    $buktiPembayaran = $state['bukti_pembayaran'] ?? [];
    $fotoDokumen = $state['foto_dokumen'] ?? [];
    $allPhotos = array_merge($buktiPembayaran, $fotoDokumen);
@endphp

<div class="flex flex-wrap gap-3">
    @forelse($allPhotos as $index => $foto)
        <a href="{{ Storage::url($foto) }}" target="_blank">
            <img 
                src="{{ Storage::url($foto) }}" 
                alt="Foto {{ $index + 1 }}"
                class="rounded-md shadow-sm border border-gray-200 dark:border-gray-700 object-cover cursor-pointer hover:shadow-md transition-shadow"
                style="width: 100px; height: 100px; aspect-ratio: 1/1;"
            />
        </a>
    @empty
        <p class="text-gray-500">Tidak ada foto</p>
    @endforelse
</div>
