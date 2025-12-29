@php
    $items = $getState() ?? [];
@endphp

<div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
    @forelse ($items as $item)
        @php
            $url = $item['url'] ?? '';
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $isPdf = $ext === 'pdf';
        @endphp
        <a
            href="{{ $url ?: '#' }}"
            target="_blank"
            rel="noopener noreferrer"
            class="block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex h-32 w-full items-center justify-center bg-gray-50">
                @if ($isPdf)
                    <div class="text-xs font-semibold text-gray-500">PDF</div>
                @else
                    <img
                        src="{{ $url }}"
                        alt="{{ $item['name'] ?? 'Bukti Transaksi' }}"
                        class="h-28 w-full object-contain"
                    />
                @endif
            </div>
            <div class="px-3 py-2 text-xs font-medium text-gray-700 line-clamp-2">
                {{ $item['name'] ?? 'Bukti Transaksi' }}
            </div>
        </a>
    @empty
        <div class="text-sm text-gray-500">Tidak ada bukti yang diunggah.</div>
    @endforelse
</div>
