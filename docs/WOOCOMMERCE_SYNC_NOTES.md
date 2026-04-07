# WooCommerce Sync - Implementation Notes

## Status Summary

### ✅ Completed Phases

| Phase | Component | Status | Notes |
|-------|-----------|--------|-------|
| 1 | Environment & Config | ✅ Working | `.env` has real credentials |
| 2 | WooCommerceService | ✅ Working | Fixed for Laravel 12 (using `withOptions`) |
| 3 | SyncStockJob | ✅ Ready | Queue job created |
| 4 | PembelianItemObserver | ✅ Ready | Observer registered |
| 6 | Test Command | ✅ Working | Connection verified! |
| 9 | Observer Registration | ✅ Working | Registered in AppServiceProvider |

### ⏳ Pending Phases

| Phase | Component | Status |
|-------|-----------|--------|
| 5 | Reconciliation Command | `SyncWooCommerceInventory.php` not created |
| 7 | Sync Logs | Migration + Model not created |
| 8 | Service Provider | `WooCommerceServiceProvider.php` not created |
| 10 | Console Kernel | Schedule not configured |

---

## Configuration

### .env (Current)
```env
WOOCOMMERCE_STORE_URL=https://store.haen.co.id
WOOCOMMERCE_CONSUMER_KEY=ck_757a25b...
WOOCOMMERCE_CONSUMER_SECRET=cs_9d922fc...
```

### config/woocommerce.php
- Store URL, Consumer Key/Secret from env
- API version: `wc/v3`
- Timeout: 30s
- SSL verification: enabled

---

## Files Created

### Core Files
- `config/woocommerce.php` - WooCommerce configuration
- `app/Services/WooCommerce/WooCommerceService.php` - API client
- `app/Jobs/SyncStockToWooCommerce.php` - Queue job
- `app/Observers/PembelianItemObserver.php` - Event observer
- `app/Console/Commands/SyncWooCommerceTest.php` - Test command

### Modified Files
- `.env` - Added WOOCOMMERCE_* variables
- `app/Providers/AppServiceProvider.php` - Registered observer

---

## How to Start

### 1. Run Queue Worker (separate terminal)
```bash
php artisan queue:work --queue=woocommerce
```

### 2. Test Connection
```bash
php artisan sync:woocommerce:test
```

---

## Important Fixes Applied

### Laravel 12 HTTP Client Fix
In `app/Services/WooCommerce/WooCommerceService.php`, Laravel 12 uses `withOptions()` instead of `verify()`:

```php
// OLD (Laravel 10/11)
->verify($this->verifySsl)

// NEW (Laravel 12)
->withOptions(['verify' => $this->verifySsl])
```

---

## Pending Implementation Details

### Reconciliation Command (Phase 5)
Should:
- Fetch all products with `qty_sisa > 0`
- Compare with WooCommerce product states
- Fix discrepancies, log mismatches
- Run every 5 minutes

### Sync Logs (Phase 7)
Need to create:
- Migration for `woocommerce_sync_logs` table
- `WooCommerceSyncLog` model
- Integrate logging into job and command

### Scheduler (Phase 10)
In `routes/console.php`:
```php
$schedule->command('sync:woocommerce')->everyFiveMinutes();
```
