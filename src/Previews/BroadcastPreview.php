<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;

/**
 * What a websocket client (Echo) would receive: channels, event name and data.
 */
final class BroadcastPreview implements Preview
{
    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $type = Payload::firstString($p, ['type']);

        return [
            'title' => $type !== null ? class_basename($type) : null,
            'text' => is_array($p) ? (string) json_encode(array_diff_key($p, ['id' => 1, 'type' => 1]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) json_encode($p),
            'channels' => array_values(array_map('strval', (array) $notification->route)),
            'event' => (string) ($notification->meta['event'] ?? ''),
            'connection' => $notification->meta['connection'] ?? null,
            'buttons' => [],
        ];
    }

    public function view(): string
    {
        return 'notification-inbox::previews.broadcast';
    }
}
