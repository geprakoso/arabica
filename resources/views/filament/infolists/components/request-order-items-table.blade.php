@php
    $items = $getState() ?? [];
@endphp

<div class="overflow-x-auto max-w-full text-sm" style="-webkit-overflow-scrolling: touch;">
    <table class="min-w-[46rem] w-full divide-y divide-gray-200 dark:divide-white/10">
        <thead class="bg-gray-50/80 dark:bg-white/5">
            <tr class="text-left text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                <th class="w-[22rem] px-3 py-2">Produk</th>
                <th class="w-[12rem] px-3 py-2">Brand</th>
                <th class="w-[12rem] px-3 py-2">Kategori</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($items as $item)
                <tr class="bg-white dark:bg-transparent hover:bg-gray-50/70 dark:hover:bg-white/5">
                    <td class="px-3 py-3">
                        <div class="max-w-[22rem] truncate font-medium text-gray-900 dark:text-gray-100">
                            {{ strtoupper(data_get($item, 'produk.nama_produk') ?? '-') }}
                        </div>
                    </td>
                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200 whitespace-nowrap">
                        {{ data_get($item, 'produk.brand.nama_brand') ?? '-' }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200 whitespace-nowrap">
                        {{ data_get($item, 'produk.kategori.nama_kategori') ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada produk.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
