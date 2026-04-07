# WooCommerce Inventory Sync - Implementation Plan

## Overview
Auto-sync product stock from Arabica inventory to WooCommerce using a hybrid approach (event-driven + scheduled reconciliation).

## Requirements
- **Sync**: Stock quantity only (no images)
- **Match**: By SKU (`md_produk.sku`)
- **Trigger**: Hybrid (immediate queue job + 5-min reconciliation)
- **Out of Stock**: Mark as `outofstock` (keep published)

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         STOCK CHANGE EVENT                          │
│                                                                     │
│  tb_pembelian_item (qty_sisa changes)                               │
│           │                                                         │
│           ▼                                                         │
│  ┌─────────────────┐     ┌─────────────────────────────────────┐   │
│  │PembelianItem    │────►│ 1. Immediate: Dispatch SyncStockJob │   │
│  │Observer         │     │    to queue (async, non-blocking)    │   │
│  └─────────────────┘     └─────────────────────────────────────┘   │
│                                    │                                 │
│                                    ▼                                 │
│                           ┌────────────────┐                        │
│                           │ Queue Worker   │                        │
│                           │ (background)   │                        │
│                           └────────────────┘                        │
│                                    │                                 │
│                                    ▼                                 │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │                    WooCommerce REST API                          ││
│  └─────────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     PERIODIC RECONCILIATION                         │
│                                                                     │
│  ┌──────────────┐      ┌─────────────────────────────────────────┐ │
│  │ Laravel      │─────►│ ScheduledCommand: SyncWooCommerce     │ │
│  │ Scheduler    │      │ Runs every 5 minutes                    │ │
│  │ (Cron)       │      │ - Fetches all products with stock      │ │
│  └──────────────┘      │ - Compares with WooCommerce             │ │
│                        │ - Fixes any discrepancies              │ │
│                        └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow

### Immediate Sync (Event-driven)
1. Stock changes in `tb_pembelian_item` (qty_sisa)
2. `PembelianItemObserver` detects via Eloquent events
3. Dispatch `SyncStockToWooCommerce` job to queue
4. Queue worker syncs to WooCommerce in background

### Periodic Reconciliation (Scheduled)
1. Laravel Scheduler runs `sync:woocommerce` every 5 minutes
2. Command fetches all products with `qty_sisa > 0`
3. Compares with WooCommerce product states
4. Fixes discrepancies, logs mismatches

## Sync Rules

| Condition | WooCommerce Action |
|-----------|-------------------|
| `qty_sisa > 0` (new stock) | Create or update product, set `stock_quantity` |
| `qty_sisa > 0` (existing) | Update `stock_quantity` only |
| `qty_sisa = 0` | Set `stock_status = 'outofstock'` |
| Product soft-deleted | Set WC product `status = 'draft'` |

## Files to Create

| # | File Path | Purpose |
|---|-----------|---------|
| 1 | `config/woocommerce.php` | WooCommerce API configuration |
| 2 | `app/Services/WooCommerce/WooCommerceService.php` | WooCommerce API client wrapper |
| 3 | `app/Jobs/SyncStockToWooCommerce.php` | Queue job for stock sync |
| 4 | `app/Observers/PembelianItemObserver.php` | Event listener for stock changes |
| 5 | `app/Console/Commands/SyncWooCommerceInventory.php` | Reconciliation artisan command |
| 6 | `app/Console/Commands/SyncWooCommerceTest.php` | Test connection command |
| 7 | `database/migrations/..._create_woocommerce_sync_logs_table.php` | Sync history table |
| 8 | `app/Models/WooCommerceSyncLog.php` | Sync log model |
| 9 | `app/Providers/WooCommerceServiceProvider.php` | Service provider |

## Files to Modify

| # | File Path | Change |
|---|-----------|--------|
| 1 | `.env` | Add WOOCOMMERCE_* environment variables |
| 2 | `app/Providers/AppServiceProvider.php` | Register PembelianItemObserver |
| 3 | `routes/console.php` | Schedule reconciliation command (every 5 mins) |

## Configuration (.env)

```env
# WooCommerce API Configuration
WOOCOMMERCE_STORE_URL=https://your-woo-store.com
WOOCOMMERCE_CONSUMER_KEY=ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WOOCOMMERCE_CONSUMER_SECRET=cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Implementation Order

1. **Env & Config** - Add WOOCOMMERCE_* to .env and create config/woocommerce.php
2. **WooCommerceService** - API client with connect(), getProductBySku(), createProduct(), updateProduct(), updateStock()
3. **SyncStockJob** - Queue job with timeout, retry, and backoff
4. **PembelianItemObserver** - Listen to updated() and created() events on PembelianItem
5. **Reconciliation Command** - Artisan command for periodic sync
6. **Sync Logs** - Track sync history and failures
7. **AppServiceProvider** - Register observer
8. **Console Kernel** - Schedule reconciliation job

## Queue Configuration (config/queue.php)

```php
'woocommerce' => [
    'driver' => 'database', // or redis
    'queue' => 'woocommerce',
    'retry_after' => 90,
],
```

## Scheduler Setup (routes/console.php)

```php
$schedule->command('sync:woocommerce')->everyFiveMinutes();
```

## Model: WooCommerceSyncLog

| Field | Type | Purpose |
|-------|------|---------|
| id | bigint | Primary key |
| produk_id | bigint | FK to md_produk |
| woo_product_id | bigint | WooCommerce product ID |
| action | enum | 'created', 'updated', 'stock_synced', 'failed' |
| request_payload | json | Data sent to WooCommerce |
| response_payload | json | Response from WooCommerce |
| error_message | text | Error details if failed |
| synced_at | timestamp | When sync occurred |

## API Endpoints Used

| Action | WooCommerce Endpoint |
|--------|---------------------|
| Get product by SKU | GET /wp-json/wc/v3/products?sku={sku} |
| Create product | POST /wp-json/wc/v3/products |
| Update product | PUT /wp-json/wc/v3/products/{id} |
| Update stock | POST /wp-json/wc/v3/products/{id}</ |

## Performance Considerations

- **Queued jobs** = no network latency blocking the app
- **Event-driven** = immediate sync for critical stock changes
- **Batch reconciliation** = catches any missed updates every 5 mins
- **Rate limiting** = WooCommerce API allows 4 requests/second
- **Exponential backoff** = job retries on failure with increasing delays

## Testing Checklist

- [ ] WooCommerce connection test (`sync:woocommerce:test`)
- [ ] Create product sync
- [ ] Update stock sync
- [ ] Out-of-stock sync
- [ ] Queue job retry behavior
- [ ] Reconciliation command
- [ ] Sync log recording
