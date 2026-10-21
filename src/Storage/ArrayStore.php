<?php

namespace AmjadIqbal\NotificationInbox\Storage;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use DateTimeInterface;

/**
 * In-memory store used by NotificationInbox::fake(). Nothing touches the disk
 * and payloads are kept unredacted, so tests can assert on every field.
 */
final class ArrayStore implements Store
{
    /** @var array<string, CapturedNotification> */
    private array $items = [];

    public function put(CapturedNotification $notification): void
    {
        $this->items[$notification->id] = $notification;
    }

    public function all(?string $after = null): array
    {
        $items = array_reverse($this->items, true);

        if ($after !== null) {
            $items = array_filter($items, fn (CapturedNotification $item): bool => strcmp($item->id, $after) > 0);
        }

        return array_values($items);
    }

    public function find(string $id): ?CapturedNotification
    {
        return $this->items[$id] ?? null;
    }

    public function delete(string $id): bool
    {
        $found = isset($this->items[$id]);
        unset($this->items[$id]);

        return $found;
    }

    public function clear(): int
    {
        $count = count($this->items);
        $this->items = [];

        return $count;
    }

    public function prune(DateTimeInterface $before, int $keep): int
    {
        $count = 0;

        foreach ($this->all() as $index => $item) {
            if ($item->capturedAt < $before || $index >= $keep) {
                $count += (int) $this->delete($item->id);
            }
        }

        return $count;
    }
}
