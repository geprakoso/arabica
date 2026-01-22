<div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-3">
    @forelse($getState() ?? [] as $index => $foto)
        <div class="relative group">
            <a href="{{ Storage::url($foto) }}" target="_blank">
                <img 
                    src="{{ Storage::url($foto) }}" 
                    alt="Foto {{ $index + 1 }}"
                    class="w-full aspect-square object-cover rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer"
                />
            </a>
        </div>
    @empty
        <p class="text-gray-500 col-span-full">Tidak ada foto dokumentasi</p>
    @endforelse
</div>
