<?php

namespace AmjadIqbal\NotificationInbox;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listens to NotificationSending, which Laravel dispatches with events->until()
 * right before each channel sends. Returning false cancels that one channel;
 * returning null lets it send and lets every other listener run.
 */
final class Interceptor
{
    public function __construct(private readonly NotificationInbox $inbox) {}

    public function handle(NotificationSending $event): ?bool
    {
        if (! $this->inbox->isActive() || ! $this->inbox->intercepts($event->channel)) {
            return null;
        }

        try {
            $this->inbox->capture($event->notifiable, $event->notification, $event->channel);
        } catch (Throwable $e) {
            // Fail closed: while the inbox is active nothing is sent, even if the capture itself broke.
            Log::error('Notification Inbox could not store a captured notification; it was not sent.', [
                'notification' => $event->notification::class,
                'channel' => $event->channel,
                'exception' => $e,
            ]);
        }

        return false;
    }
}
