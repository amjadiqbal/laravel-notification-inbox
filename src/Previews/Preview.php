<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;

/**
 * Turns one channel's payload into the fields its look-alike view draws.
 */
interface Preview
{
    /**
     * Always includes 'title' (?string), 'text' (string, plain) and 'buttons'
     * (list of label/url); each preview adds its own fields.
     *
     * @return array<string, mixed>
     */
    public function normalize(CapturedNotification $notification): array;

    public function view(): string;
}
