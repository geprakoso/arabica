# PEST Testing Guide

## 📋 Overview

PEST adalah testing framework modern untuk PHP yang dibangun di atas PHPUnit. PEST menyediakan syntax yang lebih ekspresif dan mudah dibaca.

## 🚀 Quick Start

### Menjalankan Semua Test
```bash
./vendor/bin/pest
```

### Menjalankan Test Spesifik
```bash
# Jalankan test di folder Feature
./vendor/bin/pest tests/Feature

# Jalankan test di folder Unit
./vendor/bin/pest tests/Unit

# Jalankan file test tertentu
./vendor/bin/pest tests/Feature/ExampleTest.php
```

### Menjalankan Test dengan Filter
```bash
# Jalankan test yang namanya mengandung "user"
./vendor/bin/pest --filter=user

# Jalankan test dengan coverage
./vendor/bin/pest --coverage
```

## 📝 Menulis Test

### Basic Test
```php
test('basic example', function () {
    expect(true)->toBeTrue();
});
```

### Test dengan Description
```php
it('can add two numbers', function () {
    $result = 1 + 1;
    expect($result)->toBe(2);
});
```

### Test dengan Setup
```php
beforeEach(function () {
    // Setup sebelum setiap test
    $this->user = User::factory()->create();
});

test('user has name', function () {
    expect($this->user->name)->toBeString();
});
```

## 🎯 Expectations

PEST menggunakan `expect()` untuk assertions:

```php
// Equality
expect($value)->toBe(1);
expect($value)->toEqual([1, 2, 3]);

// Types
expect($value)->toBeString();
expect($value)->toBeInt();
expect($value)->toBeBool();
expect($value)->toBeArray();
expect($value)->toBeNull();

// Truthiness
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeTruthy();
expect($value)->toBeFalsy();

// Arrays & Collections
expect($array)->toHaveKey('name');
expect($array)->toHaveCount(3);
expect($collection)->toContain('value');

// Strings
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');

// Numbers
expect($number)->toBeGreaterThan(5);
expect($number)->toBeLessThan(10);
expect($number)->toBeGreaterThanOrEqual(5);
expect($number)->toBeLessThanOrEqual(10);

// Chaining
expect($value)
    ->toBeString()
    ->toContain('test')
    ->and($otherValue)->toBe(123);
```

## 🔧 Laravel Specific

### HTTP Tests
```php
test('homepage returns 200', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});

test('can create user', function () {
    $response = $this->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $response->assertStatus(201);
});
```

### Database Tests
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create user in database', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    expect($user->id)->toBeInt();
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});
```

### Authentication
```php
test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
});
```

## 📁 Struktur Folder

```
tests/
├── Feature/          # Integration tests
│   └── ExampleTest.php
├── Unit/             # Unit tests
│   └── ExampleTest.php
├── Pest.php          # Konfigurasi global PEST
└── TestCase.php      # Base test case
```

## 🎨 Tips & Best Practices

### 1. Gunakan Descriptive Names
```php
// ❌ Bad
test('test 1', function () { ... });

// ✅ Good
test('user can register with valid email', function () { ... });
```

### 2. Group Related Tests
```php
describe('User Registration', function () {
    test('can register with valid data', function () { ... });
    test('cannot register with invalid email', function () { ... });
    test('cannot register with duplicate email', function () { ... });
});
```

### 3. Use Datasets untuk Testing Multiple Scenarios
```php
test('email validation', function ($email, $valid) {
    $result = validateEmail($email);
    expect($result)->toBe($valid);
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['test@', false],
]);
```

### 4. Gunakan Helper Functions
Di `tests/Pest.php`, Anda bisa menambahkan helper functions:

```php
function actingAsUser(?User $user = null): User
{
    $user = $user ?? User::factory()->create();
    test()->actingAs($user);
    return $user;
}
```

Kemudian gunakan di test:
```php
test('authenticated user can view profile', function () {
    $user = actingAsUser();
    
    $response = $this->get('/profile');
    $response->assertStatus(200);
});
```

## 🔌 Plugins yang Tersedia

- **Laravel Plugin**: Sudah terinstall (`pestphp/pest-plugin-laravel`)
- **Livewire Plugin**: `composer require pestphp/pest-plugin-livewire --dev`
- **Faker Plugin**: `composer require pestphp/pest-plugin-faker --dev`
- **Watch Plugin**: `composer require pestphp/pest-plugin-watch --dev`

## 📚 Resources

- [PEST Documentation](https://pestphp.com)
- [PEST Laravel Plugin](https://pestphp.com/docs/plugins/laravel)
- [Expectations API](https://pestphp.com/docs/expectations)

## 🎯 Next Steps

1. Perbaiki migrations agar kompatibel dengan SQLite untuk testing
2. Tambahkan test untuk models, controllers, dan services
3. Setup CI/CD untuk menjalankan test otomatis
4. Tambahkan code coverage reporting

---

**Happy Testing! 🧪**
