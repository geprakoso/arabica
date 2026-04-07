<?php

/**
 * Advanced PEST Examples
 * 
 * This file demonstrates more advanced PEST features
 */

// Using datasets for parameterized testing
test('email validation works with multiple inputs', function ($email, $expected) {
    $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    expect($isValid)->toBe($expected);
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['user@domain.co.id', true],
    ['@example.com', false],
    ['test@', false],
]);

// Using describe for grouping tests
describe('String Utilities', function () {
    test('can convert to uppercase', function () {
        $result = strtoupper('hello');
        expect($result)->toBe('HELLO');
    });

    test('can convert to lowercase', function () {
        $result = strtolower('WORLD');
        expect($result)->toBe('world');
    });

    test('can trim whitespace', function () {
        $result = trim('  hello  ');
        expect($result)->toBe('hello');
    });
});

// Using beforeEach and afterEach
describe('Array Operations', function () {
    beforeEach(function () {
        $this->array = [1, 2, 3, 4, 5];
    });

    test('can count array elements', function () {
        expect(count($this->array))->toBe(5);
    });

    test('can check if value exists', function () {
        expect(in_array(3, $this->array))->toBeTrue();
    });

    test('can filter array', function () {
        $filtered = array_filter($this->array, fn($n) => $n > 3);
        expect($filtered)->toHaveCount(2);
    });
});

// Chaining expectations
test('complex chaining example', function () {
    $data = [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
        ],
        'active' => true,
    ];

    expect($data)
        ->toBeArray()
        ->toHaveKey('user')
        ->toHaveKey('active')
        ->and($data['user']['name'])->toBe('John Doe')
        ->and($data['user']['age'])->toBeGreaterThan(18)
        ->and($data['active'])->toBeTrue();
});

// Testing exceptions
test('division by zero throws exception', function () {
    expect(fn() => 1 / 0)->toThrow(DivisionByZeroError::class);
});

// Using it() for more readable test names
it('can calculate fibonacci numbers', function () {
    $fibonacci = function ($n) use (&$fibonacci) {
        if ($n <= 1) return $n;
        return $fibonacci($n - 1) + $fibonacci($n - 2);
    };

    expect($fibonacci(0))->toBe(0)
        ->and($fibonacci(1))->toBe(1)
        ->and($fibonacci(5))->toBe(5)
        ->and($fibonacci(10))->toBe(55);
});
