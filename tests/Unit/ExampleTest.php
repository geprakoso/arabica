<?php

// Basic PHP Unit Tests (no Laravel dependencies)

test('true is true', function () {
    expect(true)->toBeTrue();
});

test('basic math works', function () {
    expect(1 + 1)->toBe(2);
});

test('arrays can be checked', function () {
    $array = ['name' => 'Arabica', 'type' => 'Laravel App'];

    expect($array)
        ->toHaveKey('name')
        ->toHaveKey('type')
        ->and($array['name'])->toBe('Arabica');
});

test('strings can be manipulated', function () {
    $string = 'Hello World';

    expect($string)
        ->toBeString()
        ->toContain('World')
        ->toStartWith('Hello');
});

test('numbers can be compared', function () {
    $number = 42;

    expect($number)
        ->toBeInt()
        ->toBeGreaterThan(40)
        ->toBeLessThan(50);
});
