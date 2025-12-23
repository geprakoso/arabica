## 2025-12-23
- Laporan Laba Rugi infolist: added Total Penjualan and Laba Kotor (Pendapatan - HPP) in the summary section, and updated the view title to include the month label.
- Laporan Laba Rugi calculations: aligned total_penjualan with penjualan + jasa totals, and ensured laba_kotor/laba_rugi use the combined total; included jasa-only months in the month set and added penjualan years to the year filter list.
- Laporan Laba Rugi detail tables: added Livewire pagination (25 rows/page) for penjualan, beban, and pembelian tables; totals now compute across the full month instead of only the current page, and pagination controls render when needed.
- Daftar Beban filtering: fixed mismatch by matching beban data via kategori_transaksi or the related kode_akun category, with consistent logic for both the list and monthly aggregation.
- Laporan Input Transaksi widgets: disabled TopExpensesTable via canView() without deleting the widget.
- Input Transaksi Toko list: replaced the simple date range filter with a preset range selector (1m/3m/6m/1y/custom), and added header tabs for kategori (Aktiva, Pasiva, Pendapatan, Beban, Semua).
