@php
    $state = $getState();
    $fotoDokumen = $state['foto_dokumen'] ?? [];
    $buktiPenjualan = $state['bukti_penjualan'] ?? [];
    $buktiPembelian = $state['bukti_pembelian'] ?? [];
    
    // Merge all photos into a single array
    $allPhotos = array_merge($buktiPenjualan, $buktiPembelian, $fotoDokumen);
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
