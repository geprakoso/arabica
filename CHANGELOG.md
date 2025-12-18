# Changelog

All notable changes to this project, reconstructed from git history. Versioning now follows CalVer (`YYYY.MM.DD`) while the app is pre-1.0. Entries are chronological with the latest changes first.

## 2025.12.18
- Improved mobile UX for infolist tables (horizontal scroll) and table-style layouts for Pembelian, Penjualan, and Request Order item lists.
- Added Penjualan infolist with computed totals; totals now auto-recalculate from items on create/update/delete.
- Added inline “Tambah Member” creation modal from the Penjualan member select.
- Refined Request Order form repeater (kategori → produk dependent select, placeholders, and auto-filled HPP/harga jual display from latest batch pricing).
- Fixed several Filament-related 500s (import conflicts, invalid enums/classes) and moved Pembelian create/edit actions to the page header.

## 2025.12.16
- Added Pembelian infolist for purchase records.
- Fixed filament-export column formatting compatibility.
- Corrected plural labels and add-button text; repaired absensi widgets and miscellaneous bugs.

## 2025.12.15
- Added Docker configuration and MySQL port adjustments.
- Introduced modal helper view support.
- Fixed widgets (including stock adjustment), infolist downloads, export buttons, plural labels, and panel provider wiring.
- Merged ongoing keuangan and Laporan-Cuti branches.

## 2025.12.14
- Fixed accounting export formatting and merged keuangan updates.

## 2025.12.13
- Updated financial navigation.
- Added pengajuan cuti (leave request) flow and export fixes; merged Laporan-Cuti changes.

## 2025.12.11
- Minor fixes before switching to brand master data; refined master data return handling.
- Continued work on laporan keuangan (financial reports).

## 2025.12.10
- Fixed tabs in pengaturan keuangan and infolist issues.
- Addressed navigation for users and role-access (403) errors; stabilized role handling after revert.

## 2025.12.09
- Added jenis akun and kode akun (account types/codes) to keuangan.
- Marked keuangan module as functionally complete pending breadcrumb fixes.

## 2025.12.08
- Continued work on jenis akun/kode akun for financial settings.

## 2025.12.07
- Built dashboard views and prepared for role policy changes.
- Enforced view policy for active attendance records; fixed role handling in main and related widgets.

## 2025.12.05
- UI adjustments and polish.

## 2025.12.04
- Fixed Filament Shield roles; refined POS dashboard and karyawan widgets.
- Added weather widget; merged reporting branch.

## 2025.12.03
- Set updated UI look and feel; aligned master data navigation.
- Merged main branch changes.

## 2025.12.01
- Added documentation and layout tweaks (navigation, layout adjustments through lembur).
- Updated column styles; refined POS return handling and totals.
- Improved navigation bar (including draggable sidebar), modernized navigation, icons, and minor infolist/product fixes.
- Merged POS and pengaturan-navigasi branches.

## 2025.11.30
- Updated navigation bar with draggable sidebar navigation.

## 2025.11.29
- Improved absensi wizard and check-in logic.

## 2025.11.28
- Added service scheduling, shipping enhancements, and multi-app POS improvements.
- Merged POS branch back to main.

## 2025.11.27
- Prep commits before branching for task scheduling; final push for existing work.

## 2025.11.26
- Removed committed npm cache.
- Added lembur (overtime) handling.
- Merged inventory, sales, and purchasing workstreams with absensi updates.

## 2025.11.24
- Added absensi-libur-cuti features and reordered leave/holiday reasons.
- Improved desktop sidebar collapse behaviour.

## 2025.11.23
- Added photo capture for attendance and attendance infolist view.

## 2025.11.22
- Upgraded to Filament Shield for roles/permissions.
- Merged Absensi branch; adjusted stock opname; auto-marked alpha for late attendance.

## 2025.11.21
- Added currency plugin and company profile page.
- Auto-selected logged-in employee, time, and date with UTC+7 handling.

## 2025.11.20
- Renamed POS module; merged POS branch to main.
- Added sales and purchase reports; auto-fetch longitude/latitude.

## 2025.11.19
- Added penjualan (sales) creation and flow.
- Enhanced Chatify group chat with member list and avatars; installed media manager prepping for POS.

## 2025.11.18
- Added pembelian and inventory features; adjusted akun transaksi and master data alignment.
- Continued inventory improvements and introduced chat room (Chatify) prototype.

## 2025.11.16
- Implemented akun transaksi processing.

## 2025.11.15
- Added roles & permissions setup, user registration, and karyawan login/role selection page.
- Introduced request order flow.
- Merged master-data and inventory branches.

## 2025.11.14
- Added member, supplier, and agent relationship management.
- Added gudang (warehouse) master data and unified table layouts with profile pictures.
- Introduced role/permission/authentication foundation.

## 2025.11.13
- Completed core master data for brand, jasa, kategori, and produk.

## 2025.11.12
- Added initial master data scaffolding.

## 2025.11.11
- Created migrations for produk, jasa, brand, and kategori tables (project start).
