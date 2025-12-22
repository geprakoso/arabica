@php
    $record = $getRecord();
@endphp

@if ($record)
    <livewire:laba-rugi-beban-table :month-key="$record->month_key" lazy />
@endif
