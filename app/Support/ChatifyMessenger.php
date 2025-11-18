<?php

namespace App\Support;

use Chatify\ChatifyMessenger as BaseChatifyMessenger;
use Illuminate\Support\Facades\Log;

class ChatifyMessenger extends BaseChatifyMessenger
{
    /**
     * Gracefully handle push failures (e.g. offline Pusher) without breaking requests.
     */
    public function push($channel, $event, $data)
    {
        try {
            return $this->pusher->trigger($channel, $event, $data);
        } catch (\Throwable $e) {
            Log::warning('Chatify Pusher push failed, skipping realtime broadcast.', [
                'channel' => $channel,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
