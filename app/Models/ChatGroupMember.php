<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMember extends Model
{
    use HasFactory; // Enable factory usage if needed later.

    /** @var array<int, string> */
    protected $fillable = [
        'group_id', // Owning group reference.
        'user_id', // Member user reference.
        'role', // Member role (owner/admin/member).
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class); // Each membership belongs to a group.
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // Each membership belongs to a user.
    }
}
