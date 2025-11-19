<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class ChatGroup extends Model
{
    use HasFactory; // Allow factory usage for seeding/test data.

    protected $table = 'ch_groups'; // Explicitly point to Chatify table name.

    /** @var array<string> */
    protected $fillable = [
        'name', // Mass-assignable friendly name.
        'slug', // Slug used for quick lookups.
        'description', // Optional group description.
        'avatar', // Custom avatar path if provided.
        'owner_id', // Owner/creator user reference.
        'settings', // JSON encoded settings blob.
    ];

    /** @var array<string, string> */
    protected $casts = [
        'settings' => 'array', // Always treat settings as PHP array.
    ];

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? basename($value) : null,
            set: fn (?string $value) => $value ? basename($value) : null,
        ); // Always store and return only the filename portion for compatibility with Chatify helpers.
    }

    protected static function booted(): void
    {
        static::creating(function (ChatGroup $group): void {
            if (blank($group->slug)) {
                $group->slug = Str::slug($group->name) . '-' . Str::random(5); // Generate unique slug automatically.
            }
        });

        static::saved(function (ChatGroup $group): void {
            $group->members()->syncWithoutDetaching([
                $group->owner_id => ['role' => 'owner'],
            ]); // Ensure owner always belongs to the group with owner role.
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id'); // Link group to owner.
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ch_group_user', 'group_id', 'user_id')
            ->withPivot(['role'])
            ->withTimestamps(); // Provide pivot info (role + timestamps).
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChMessage::class, 'to_id')
            ->where('conversation_type', 'group'); // Scope messages to this group conversation.
    }
}
