# Godmode Feature Documentation

**Date:** 2026-01-23
**Feature:** Godmode Role (Unrestricted Access)

## Overview
The "Godmode" role is a specialized, high-privilege role designed to bypass all authorization checks within the application. Unlike standard roles (e.g., `super_admin`) which may rely on specific permissions or policies, the Godmode role grants universal access via a `Gate::before` interceptor.

## Security Architecture: Double Verification
To prevent exploitation (e.g., if a database dump is compromised or an unauthorized admin grants themselves the role), this feature uses a **Double Verification Strategy**:

1.  **Database Role Check**: The user must have the `godmode` role assigned in the database.
2.  **Server-Side Whitelist Check**: The user's email address MUST match an entry in the server's environment configuration.

**Access is DENIED unless BOTH conditions are met.**

## Setup Guide

### 1. Environment Configuration
You must whitelist the email addresses allowed to use Godmode in your `.env` file.

**Single User:**
```env
GODMODE_EMAILS=owner@example.com
```

**Multiple Users (Comma-separated):**
```env
GODMODE_EMAILS=owner@example.com,developer@example.com
```

### 2. Role Assignment
The role is managed via `Spatie\Permission`. You can assign it via the Filament Shield interface or artisan commands.

```bash
# Example via Tinker
php artisan tinker
> $user = User::where('email', 'owner@example.com')->first();
> $user->assignRole('godmode');
```

## Technical Implementation

### AuthServiceProvider
The core logic resides in `app/Providers/AuthServiceProvider.php`:

```php
Gate::before(function ($user, $ability) {
    // Godmode: Double Verification (Role + Email Whitelist)
    if ($user->hasRole('godmode')) {
        $godmodeEmailsEnv = config('godmode.emails');
        // Handle comma-separated string if provided
        $godmodeEmails = is_array($godmodeEmailsEnv) 
            ? $godmodeEmailsEnv 
            : array_filter(array_map('trim', explode(',', $godmodeEmailsEnv ?? '')));
        
        if (in_array($user->email, $godmodeEmails)) {
            return true; // Bypass all checks
        }
    }

    // ... Standard Super Admin check ...
});
```

## Godmode Integration in Custom Authorization

Places in the codebase that have custom authorization logic (e.g., Livewire components, action visibility closures) need to explicitly check for the godmode/super_admin role. Example:

```php
$isSuperRole = $user->hasRole(['godmode', 'super_admin']);
$isAuthorized = $isSuperRole || $someOtherCondition;
```

**Already integrated in:**
- `app/Livewire/TaskComments.php` - Allows godmode users to comment on any task
- `app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php` - Allows godmode users to edit all tasks


### Config
Configuration is loaded from `config/godmode.php`:
```php
return [
    'emails' => env('GODMODE_EMAILS'), 
];
```
