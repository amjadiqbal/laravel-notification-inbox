<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;

/**
 * Channels without a look-alike view: the payload itself, with a best guess at its text.
 */
final class JsonPreview implements Preview
{
    public function normalize(CapturedNotification $notification): array
    {
        $payload = $notification->payload;

        return [
            'title' => Payload::firstString($payload, ['title', 'subject', 'name']),
            'text' => is_string($payload) ? $payload : (Payload::firstString($payload, ['text', 'body', 'content', 'message', 'description']) ?? ''),
            'buttons' => [],
        ];
    }

    public function view(): string
    {
        return 'notification-inbox::previews.json';
    }
}
