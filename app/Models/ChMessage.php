<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Chatify\Traits\UUID;

class ChMessage extends Model
{
    use UUID; // Keep UUID trait for primary keys.

    /** @var array<int, string> */
    protected $fillable = [
        'from_id', // Sender user id.
        'to_id', // Receiver (user or group) id.
        'body', // Message body.
        'attachment', // Any attachment metadata JSON.
        'seen', // Seen flag for direct conversations.
        'conversation_type', // Distinguish user vs group threads.
    ];

    /** @var array<string, string> */
    protected $casts = [
        'seen' => 'boolean', // Always treat seen flag as bool.
    ];
}
