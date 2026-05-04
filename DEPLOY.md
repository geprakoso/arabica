# 🚀 Panduan Deploy Production — Arabica

## Prasyarat

- Akses SSH ke server production
- Backup database **sebelum** deploy
- Pastikan tidak ada transaksi sedang berjalan saat deploy

---

## Langkah Deploy Standar

### 1. Backup Database

```bash
mysqldump -u [user] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Pull Kode Terbaru

```bash
cd /path/to/arabica
git pull origin main
```

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 4. Jalankan Migrasi Database

```bash
php artisan migrate --force
```

### 5. Cache Konfigurasi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icon:cache
php artisan filament:cache-components
```

### 6. Restart Queue (jika pakai)

```bash
php artisan queue:restart
```

---

## Perintah Khusus per Rilis

> Tambahkan perintah khusus di sini setiap kali ada rilis yang butuh langkah tambahan.
> Hapus setelah dijalankan di production.

### Rilis: Fix Sinkronisasi Stok (Mei 2026)

**Setelah Step 5**, jalankan:

```bash
# Preview perubahan (tidak mengubah data)
php artisan stock:sync-qty-sisa --dry-run

# Jalankan sync
php artisan stock:sync-qty-sisa
```

**Verifikasi:**
```bash
php artisan tinker --execute="
\$col = \App\Models\PembelianItem::qtySisaColumn();
\$mismatch = 0;
foreach(\App\Models\StockBatch::with('pembelianItem')->get() as \$b) {
    \$pi = \$b->pembelianItem;
    if (!\$pi) continue;
    if ((int)\$pi->{\$col} !== (int)\$b->qty_available) \$mismatch++;
}
echo \$mismatch === 0 ? '✅ Semua sinkron!' : '⚠️ ' . \$mismatch . ' tidak sinkron';
"
```

---

## Rollback

Jika terjadi masalah setelah deploy:

```bash
# 1. Kembalikan kode
git revert HEAD
# atau
git reset --hard [commit_sebelumnya]

# 2. Restore database
mysql -u [user] -p [database_name] < backup_[tanggal].sql

# 3. Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## Checklist Post-Deploy

- [ ] Halaman Inventory menampilkan stok yang benar
- [ ] Create Penjualan → stok berkurang
- [ ] Edit Penjualan tanpa ubah item → stok tidak berubah
- [ ] Edit Tukar Tambah tanpa ubah item → stok tidak berubah
- [ ] Produk stok habis tidak muncul di Inventory
- [ ] WooCommerce sync berjalan normal (jika aktif)
