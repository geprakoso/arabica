<?php

use App\Models\User;

// Laravel Feature Tests

test('environment is testing', function () {
    expect(app()->environment())->toBe('testing');
});

test('config can be accessed', function () {
    expect(config('app.name'))->toBeString();
});

test('user model exists', function () {
    expect(class_exists(User::class))->toBeTrue();
});

test('user model has fillable attributes', function () {
    $user = new User();

    expect($user->getFillable())
        ->toBeArray()
        ->toContain('name')
        ->toContain('email');
});

test('user model has hidden attributes', function () {
    $user = new User();

    expect($user->getHidden())
        ->toBeArray()
        ->toContain('password');
});
