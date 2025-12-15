<style>
@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden !important;
    }

    /* Show only Filament main content */
    .fi-main,
    .fi-main * {
        visibility: visible !important;
    }

    /* Make main content occupy the printable area */
    .fi-main {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
