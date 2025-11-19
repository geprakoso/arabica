<?php

namespace App\Http\Controllers\Chatify;

use App\Models\ChatGroup;
use App\Services\Chat\GroupChatService;
use Chatify\Facades\ChatifyMessenger as Chatify;
use Chatify\Http\Controllers\MessagesController as BaseMessagesController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessagesController extends BaseMessagesController
{
    public function __construct(private GroupChatService $groups)
    {
        // No need to call parent constructor because the base controller does not define one.
    }

    public function idFetchData(Request $request): JsonResponse
    {
        if ($request->get('type') === 'group') {
            $group = ChatGroup::with([
                'members' => fn ($query) => $query->orderBy('name')->with('karyawan'),
            ])
                ->withCount('members')
                ->findOrFail($request->get('id')); // Fetch group metadata.
            $this->groups->ensureMember($group, Auth::user()); // Block access for non members.

            return Response::json([
                'favorite' => false, // Favorites feature stays user-only for now.
                'fetch' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'members_count' => $group->members_count,
                    'description' => $group->description,
                ], // Provide trimmed payload for the sidebar.
                'members' => $group->members->map(function ($member) {
                    $display = Chatify::getUserWithAvatar($member);
                    $karyawanAvatar = $member->karyawan?->image_url
                        ? Storage::disk('public')->url($member->karyawan->image_url)
                        : null;
                    return [
                        'id' => $display->id,
                        'name' => $display->name,
                        'role' => $display->pivot->role ?? 'member',
                        'avatar' => $karyawanAvatar ?? $display->avatar,
                    ];
                }),
                'user_avatar' => $group->avatar
                    ? Chatify::getUserAvatarUrl($group->avatar)
                    : Chatify::getUserAvatarUrl(config('chatify.user_avatar.default')), // Fall back to default avatar asset.
                'type' => 'group', // Help the front-end know this is a group.
            ]);
        }

        return parent::idFetchData($request); // Fall back to base implementation for user chats.
    }

    public function send(Request $request): JsonResponse
    {
        if ($request->get('type') !== 'group') {
            return parent::send($request); // Delegate to parent for user chats.
        }

        $request->validate([
            'id' => ['required', 'integer', 'exists:ch_groups,id'], // Validate group id.
            'message' => ['nullable', 'string', 'max:5000'], // Validate body length.
        ]);

        $group = ChatGroup::with('members')->findOrFail($request->get('id')); // Load target group and eager-load members.
        $this->groups->ensureMember($group, Auth::user()); // Guard membership.

        $attachmentPayload = null; // Default attachment payload.
        $attachmentError = $this->handleAttachmentUpload($request, $attachmentPayload); // Re-use helper to upload file.

        if ($attachmentError['status'] === 1) {
            return Response::json([
                'status' => '422',
                'error' => $attachmentError,
            ], 422); // Early out if attachment failed validation/upload.
        }

        $message = $this->groups->createMessage($group, Auth::user(), [
            'body' => htmlentities(trim($request->get('message', '')), ENT_QUOTES, 'UTF-8'),
            'attachment' => $attachmentPayload,
        ]); // Store message row via service.

        $messageData = Chatify::parseMessage($message); // Parse message for blade partial.

        foreach ($group->members as $member) {
            if ($member->id === Auth::id()) {
                continue; // Skip echo-back to sender to prevent duplicate event.
            }

            Chatify::push("private-chatify-group.{$group->id}.{$member->id}", 'messaging', [
                'from_id' => Auth::id(),
                'group_id' => $group->id,
                'message' => Chatify::messageCard($messageData, true),
                'type' => 'group',
            ]); // Broadcast to each member-specific private channel.
        }

        return Response::json([
            'status' => '200',
            'error' => $attachmentError,
            'message' => Chatify::messageCard($messageData),
            'tempID' => $request->get('temporaryMsgId'),
            'type' => 'group',
        ]); // Return the rendered message card for optimistic UI.
    }

    public function fetch(Request $request): JsonResponse
    {
        if ($request->get('type') !== 'group') {
            return parent::fetch($request); // Keep default behaviour for user chats.
        }

        $request->validate([
            'id' => ['required', 'integer', 'exists:ch_groups,id'], // Validate group id.
        ]);

        $group = ChatGroup::findOrFail($request->get('id')); // Load group once.
        $this->groups->ensureMember($group, Auth::user()); // Ensure user belongs to group.

        $messages = $this->groups->fetchMessages($group, $request->per_page ?? $this->perPage); // Paginate conversation history.

        if ($messages->total() < 1) {
            return Response::json([
                'total' => 0,
                'last_page' => 1,
                'last_message_id' => null,
                'messages' => '<p class="message-hint center-el"><span>Invite teammates and start chatting</span></p>',
            ]); // Quick response if no history yet.
        }

        $buffer = ''; // Container for rendered blade snippets.
        foreach ($messages->reverse() as $message) {
            $buffer .= Chatify::messageCard(Chatify::parseMessage($message)); // Reuse package blade for each row.
        }

        return Response::json([
            'total' => $messages->total(),
            'last_page' => $messages->lastPage(),
            'last_message_id' => collect($messages->items())->last()->id ?? null,
            'messages' => $buffer,
        ]); // Mirror native payload for front-end compatibility.
    }

    public function seen(Request $request): JsonResponse
    {
        if ($request->get('type') === 'group') {
            return Response::json([
                'status' => 1,
            ], 200); // Group chats currently do not track seen state.
        }

        return parent::seen($request); // Keep base behaviour for direct chats.
    }

    public function getGroups(Request $request): JsonResponse
    {
        $groups = $this->groups->getUserGroups(Auth::user()); // Resolve accessible groups.

        return Response::json([
            'html' => view('Chatify::layouts.groupListItem', compact('groups'))->render(),
        ]); // Return rendered HTML so the client can drop it straight in.
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]); // Validate create payload.

        $group = ChatGroup::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(5),
            'description' => $data['description'] ?? null,
            'owner_id' => Auth::id(),
        ]); // Persist new group record.

        $members = collect($data['member_ids'] ?? [])
            ->push(Auth::id())
            ->unique(); // Always include creator in membership list.

        $group->members()->sync(
            $members->mapWithKeys(fn ($id) => [$id => ['role' => $id === Auth::id() ? 'owner' : 'member']])
        ); // Attach members with roles.

        return Response::json([
            'group' => $group->loadCount('members'),
        ], 201); // Return created group for UI consumption.
    }

    private function handleAttachmentUpload(Request $request, ?array &$payload): array
    {
        $error = [
            'status' => 0,
            'message' => null,
        ]; // Default success state.

        if (! $request->hasFile('file')) {
            $payload = null;
            return $error; // Nothing to upload.
        }

        $allowed = array_merge(Chatify::getAllowedImages(), Chatify::getAllowedFiles()); // Accepted extensions.
        $file = $request->file('file'); // Uploaded file reference.

        if ($file->getSize() >= Chatify::getMaxUploadSize()) {
            $error['status'] = 1;
            $error['message'] = 'File size you are trying to upload is too large!'; // Mirror base error message.
            return $error;
        }

        if (! in_array(strtolower($file->extension()), $allowed)) {
            $error['status'] = 1;
            $error['message'] = 'File extension not allowed!'; // Mirror base error message.
            return $error;
        }

        $attachmentName = Str::uuid() . '.' . $file->extension(); // Generate unique filename.
        $file->storeAs(config('chatify.attachments.folder'), $attachmentName, config('chatify.storage_disk_name')); // Persist file to storage disk.

        $payload = json_encode((object) [
            'new_name' => $attachmentName,
            'old_name' => htmlentities(trim($file->getClientOriginalName()), ENT_QUOTES, 'UTF-8'),
        ]); // Encode attachment payload to remain compatible with Chatify message parser.

        return $error;
    }
}
