<?php

namespace AmjadIqbal\NotificationInbox\Storage;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use DateTimeInterface;

interface Store
{
    public function put(CapturedNotification $notification): void;

    /**
     * Newest first. With $after, only notifications captured after that id.
     *
     * @return list<CapturedNotification>
     */
    public function all(?string $after = null): array;

    public function find(string $id): ?CapturedNotification;

    public function delete(string $id): bool;

    public function clear(): int;

    /**
     * Delete everything captured before $before, then the oldest beyond $keep.
     */
    public function prune(DateTimeInterface $before, int $keep): int;
}
