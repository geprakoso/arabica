<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Karyawan;
use App\Models\ChatGroup;


class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**aC6aC6
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'messenger_color',
        'dark_mode',
        'active_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dark_mode' => 'boolean',
            'active_status' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
	// Mode darurat agar semua bisa masuk
	return true;

	/*
        $superAdminRole = config('filament-shield.super_admin.name', 'super_admin');
        $panelUserRole = config('filament-shield.panel_user.name', 'panel_user');

        // Izinkan akses jika punya salah satu role yang diizinkan (super_admin, panel_user, kasir, petugas)
        // atau punya role lain apa pun (fallback lama).
        return $this->hasAnyRole([$superAdminRole, $panelUserRole, 'kasir', 'petugas'])
            || $this->roles()->exists();
	*/
    }

    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'ch_group_user', 'user_id', 'group_id')
            ->withPivot('role')
            ->withTimestamps(); // Provide quick access to groups this user belongs to.
    }

    public function karyawan(): HasOne
    {
        return $this->hasOne(Karyawan::class, 'user_id'); // Link user to their employee profile if one exists.
    }
}
