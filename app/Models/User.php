<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

// User Model
class User extends Authenticatable implements HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'avatar_url', // Actual column, also mapped to karyawan.image_url
        'messenger_color',
        'dark_mode',
        'active_status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [];

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
            'avatar_url' => 'string',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $superAdminRole = config('filament-shield.super_admin.name', 'super_admin');
        $panelUserRole = config('filament-shield.panel_user.name', 'panel_user');

        // Izinkan akses jika punya salah satu role yang diizinkan (super_admin, panel_user, kasir, petugas)
        // atau punya role lain apa pun (fallback lama).
        return $this->hasAnyRole([$superAdminRole, $panelUserRole, 'kasir', 'petugas'])
            || $this->roles()->exists();
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

    public function getFilamentAvatarUrl(): ?string
    {
        $path = $this->avatar_url;

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            // Check if avatar_url was changed
            if ($user->wasChanged('avatar_url')) {
                // Sync to karyawan record
                if (! $user->karyawan) {
                    $user->karyawan()->create([
                        'nama_karyawan' => $user->name,
                        'slug' => \Illuminate\Support\Str::slug($user->name),
                        'image_url' => $user->avatar_url,
                        'is_active' => true,
                    ]);
                    // Refresh the relationship
                    $user->load('karyawan');
                } else {
                    $user->karyawan->image_url = $user->avatar_url;
                    $user->karyawan->save();
                }
            }
        });
    }
}
