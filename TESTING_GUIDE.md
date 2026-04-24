# 🧪 FASE 4: TESTING GUIDE
## Purchase Module Policy - Hybrid Testing Approach

---

## 📋 Overview

Fase 4 menggunakan **Hybrid Testing** dengan 2 pendekatan:

| Jenis | Tools | Coverage | Kecepatan |
|-------|-------|----------|-----------|
| **Unit & Feature Tests** | Pest (PHP) | R01-R17 (Logic) | ⚡ Fast |
| **E2E Browser Tests** | Puppeteer | R16 (UI Flow) | 🐢 Medium |

---

## 🚀 Cara Menjalankan Tests

### **1. Pest Tests (PHP)**

#### Jalankan Semua Tests:
```bash
cd /Applications/MAMP/htdocs/arabica
./vendor/bin/pest tests/Feature/PurchaseModule tests/Unit/PurchaseModule
```

#### Jalankan Specific Test:
```bash
./vendor/bin/pest tests/Feature/PurchaseModule/PembelianPolicyTest.php
```

#### Jalankan dengan Coverage:
```bash
./vendor/bin/pest --coverage
```

#### Jalankan Filtered:
```bash
./vendor/bin/pest --filter="R16"
```

### **2. Puppeteer E2E Tests**

#### Install Dependencies:
```bash
cd tests/Browser
npm install
```

#### Jalankan E2E Tests:
```bash
npm test
```

#### Jalankan dengan Browser Visible:
```bash
npm run test:headless
```

#### Set Environment Variable:
```bash
APP_URL=http://localhost:8000 npm test
```

---

## 📊 Generate Test Report

### **Generate HTML & PDF Report:**
```bash
php scripts/generate-test-report.php
```

### **Generate dengan Menjalankan Tests:**
```bash
php scripts/generate-test-report.php --run-tests
```

### **Output Reports:**
- `storage/reports/test-report-{timestamp}.html`
- `storage/reports/test-report-{timestamp}.pdf`

---

## 📝 Test Coverage by Rule

| Rule | Pest Tests | Puppeteer | Status |
|------|:----------:|:---------:|:------:|
| **R01** | StockBatch auto-create | - | ✅ |
| **R02** | Duplicate produk+kondisi | - | ✅ |
| **R03** | Subtotal auto-calculate | Form validation | ✅ |
| **R04** | Serials column removed | - | ✅ |
| **R05** | Empty items allowed | Form submit | ✅ |
| **R06-R07** | Status TEMPO/LUNAS | - | ✅ |
| **R08** | Kelebihan calculation | - | ✅ |
| **R09-R10** | Edit restrictions | - | ✅ |
| **R11** | Grand total stored | - | ✅ |
| **R12** | Delete blocked (sales) | - | ✅ |
| **R13** | Delete blocked (NO TT) | Button disabled | ✅ |
| **R14** | Qty consistency | - | ✅ |
| **R15** | File management | - | ⬜ |
| **R16** | Lock Final logic | Lock flow, UI | ✅ |
| **R17** | Pessimistic locking | - | ✅ |

---

## 🔧 Troubleshooting

### **Pest Tests Gagal:**
```bash
# Reset database
php artisan migrate:fresh --seed

# Jalankan lagi
./vendor/bin/pest
```

### **Puppeteer Gagal:**
```bash
# Install Chrome/Chromium
npm install puppeteer

# Cek dengan
npx puppeteer browsers install chrome
```

### **Permission Denied:**
```bash
chmod +x scripts/generate-test-report.php
chmod +x vendor/bin/pest
```

---

## 📁 File Structure

```
tests/
├── Feature/
│   └── PurchaseModule/
│       └── PembelianPolicyTest.php    # 17 Rules Tests
├── Unit/
│   └── PurchaseModule/
│       └── StockBatchTest.php         # R01, R14, R17
├── Browser/
│   ├── PurchaseModule/
│   │   └── purchase-policy.e2e.test.js  # E2E Tests
│   ├── package.json
│   └── setup.js
scripts/
└── generate-test-report.php           # Report Generator
```

---

## ✨ Continuous Integration

### **GitHub Actions Example:**
```yaml
name: Test Purchase Module

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install Dependencies
      run: composer install
      
    - name: Run Pest Tests
      run: ./vendor/bin/pest tests/Feature/PurchaseModule tests/Unit/PurchaseModule
      
    - name: Generate Report
      run: php scripts/generate-test-report.php
      
    - name: Upload Report
      uses: actions/upload-artifact@v3
      with:
        name: test-report
        path: storage/reports/
```

---

## 📞 Support

Jika ada issue dengan testing:
1. Cek `.env.testing` configuration
2. Pastikan database SQLite/MySQL bisa diakses
3. Jalankan `php artisan optimize:clear`

---

**Ready to Test! 🚀**
