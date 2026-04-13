# Redis Setup Guide for Production

## ⚡ Status: Optional

Redis di Arabica bersifat **optional**. Aplikasi tetap berjalan normal tanpa Redis.

---

## 🚦 Mode Operasi

### Mode 1: Tanpa Redis (Default)
```env
CACHE_DRIVER=file
```
- ✅ Aplikasi berjalan normal
- ✅ Tidak ada error
- ⚠️  Cache disimpan di file (lebih lambat dari Redis)

### Mode 2: Dengan Redis (Recommended)
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
- ✅ Performa lebih cepat
- ✅ Cache antar request lebih efisien
- ✅ Support cache tags

---

## 🛡️ Safety Features

Kode sudah include **auto-fallback**:

1. **Auto-detect Redis**: Kalau Redis tidak tersedia, otomatis pakai driver lain
2. **Error handling**: Kalau cache error, aplikasi tetap jalan (tanpa cache)
3. **No breaking changes**: Deploy kode baru aman meski belum ada Redis

---

## 📋 Checklist Deploy ke Production

### Jika Belum Ada Redis:
```bash
# 1. Pastikan .env pakai file driver
CACHE_DRIVER=file

# 2. Clear config
php artisan config:clear

# 3. Deploy
# (kode baru aman di-deploy)
```

### Jika Sudah Ada Redis:
```bash
# 1. Pastikan .env pakai redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# 2. Clear config
php artisan config:clear

# 3. Deploy
```

---

## 🔧 Install Redis (Kalau Mau)

### Docker Compose (Tambahkan ke docker-compose.yml):
```yaml
redis:
  image: redis:7-alpine
  command: redis-server --requirepass your_secure_password --appendonly yes
  volumes:
    - redis_data:/data
  ports:
    - "6379:6379"

volumes:
  redis_data:
```

### Ubuntu/Debian Server:
```bash
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## 🧪 Testing

```bash
# Test cache helper
php artisan arabica:clear-cache

# Expected output kalau Redis tidak tersedia:
# 📊 Cache Info:
#   • driver: file
#   • status: fallback_mode
#   • message: Redis not available, using file
```

---

## ⚠️ Catatan Penting

1. **Cache Tags**: Tidak support di driver `file` dan `database`. Cache tags hanya work di Redis.
   - Impact: Cache invalidation tidak se-granular Redis, tapi aplikasi tetap jalan.

2. **Performance**: Tanpa Redis, cache masih work tapi lebih lambat.

3. **Deploy Aman**: Kode bisa di-deploy kapan saja, dengan atau tanpa Redis.

---

## 📞 Support

Kalau ada error setelah deploy:
1. Cek `.env`: `CACHE_DRIVER` harus `file` kalau belum ada Redis
2. Cek log: `storage/logs/laravel.log`
3. Clear cache: `php artisan cache:clear`
