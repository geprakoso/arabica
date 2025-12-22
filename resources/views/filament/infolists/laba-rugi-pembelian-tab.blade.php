@php
    $record = $getRecord();
@endphp

@if ($record)
    <livewire:laba-rugi-pembelian-table :month-key="$record->month_key" lazy />
@endif
