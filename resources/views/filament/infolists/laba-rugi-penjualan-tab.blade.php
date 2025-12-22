@php
    $record = $getRecord();
@endphp

@if ($record)
    <livewire:laba-rugi-penjualan-table :month-key="$record->month_key" lazy />
@endif
