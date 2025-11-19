<?php

namespace App\Services\Chat;

use App\Models\ChatGroup;
use App\Models\ChMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GroupChatService
{
    public function getUserGroups(User $user): Collection
    {
        return $user->chatGroups()
            ->withCount('members')
            ->orderBy('name')
            ->get(); // Fetch groups the user belongs to.
    }

    public function ensureMember(ChatGroup $group, User $user): void
    {
        if (! $group->members()->whereKey($user->id)->exists()) {
            abort(403, 'You are not allowed to access this group.'); // Block unexpected access.
        }
    }

    public function createMessage(ChatGroup $group, User $sender, array $payload): ChMessage
    {
        $message = new ChMessage(); // Instantiate message model.
        $message->id = Str::uuid()->toString(); // Provide UUID manually for clarity.
        $message->from_id = $sender->id; // Track sender.
        $message->to_id = $group->id; // Store group id in to_id column.
        $message->conversation_type = 'group'; // Tag message as group conversation.
        $message->body = $payload['body']; // Fill message body.
        $message->attachment = $payload['attachment'] ?? null; // Persist optional attachment.
        $message->save(); // Persist to database.
        return $message;
    }

    public function fetchMessages(ChatGroup $group, int $perPage = 30): LengthAwarePaginator
    {
        return $group->messages()
            ->latest()
            ->paginate($perPage); // Return paginated messages for UI.
    }
}
