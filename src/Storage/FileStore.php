<?php

namespace AmjadIqbal\NotificationInbox\Storage;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Support\Redactor;
use DateTimeInterface;
use RuntimeException;
use Throwable;

/**
 * One JSON file per captured notification, named by its ULID so a directory
 * listing is already in capture order. Each file is written to a temporary name
 * and renamed into place, so queue workers writing at the same time never
 * corrupt each other and the dashboard never reads half a file.
 */
final class FileStore implements Store
{
    public function __construct(
        private readonly string $path,
        private readonly Redactor $redactor,
    ) {}

    public function put(CapturedNotification $notification): void
    {
        $this->ensureDirectory();

        $data = $notification->toArray();
        $data['payload'] = $this->redactor->payload($data['payload']);
        $data['route'] = $this->redactor->route($data['route']);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        $file = $this->file($notification->id);
        $temporary = $file.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (file_put_contents($temporary, $json) === false || ! rename($temporary, $file)) {
            @unlink($temporary);

            throw new RuntimeException("Notification Inbox could not write to [{$this->path}].");
        }
    }

    public function all(?string $after = null): array
    {
        $items = [];

        foreach ($this->ids() as $id) {
            if ($after !== null && strcmp($id, $after) <= 0) {
                break;
            }

            if ($item = $this->find($id)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function find(string $id): ?CapturedNotification
    {
        if (! $this->isValidId($id) || ! is_file($file = $this->file($id))) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? CapturedNotification::fromArray($data) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function delete(string $id): bool
    {
        return $this->isValidId($id) && is_file($file = $this->file($id)) && @unlink($file);
    }

    public function clear(): int
    {
        $count = 0;

        foreach ($this->ids() as $id) {
            $count += (int) $this->delete($id);
        }

        return $count;
    }

    public function prune(DateTimeInterface $before, int $keep): int
    {
        $count = 0;
        $cutoff = $before->getTimestamp();

        foreach ($this->ids() as $index => $id) {
            $file = $this->file($id);

            if ($index >= $keep || (int) @filemtime($file) < $cutoff) {
                $count += (int) $this->delete($id);
            }
        }

        return $count;
    }

    /**
     * Ids newest first.
     *
     * @return list<string>
     */
    private function ids(): array
    {
        $files = glob($this->path.DIRECTORY_SEPARATOR.'*.json') ?: [];
        $ids = array_map(fn (string $file): string => basename($file, '.json'), $files);
        $ids = array_values(array_filter($ids, fn (string $id): bool => $this->isValidId($id)));
        rsort($ids, SORT_STRING);

        return $ids;
    }

    private function file(string $id): string
    {
        return $this->path.DIRECTORY_SEPARATOR.$id.'.json';
    }

    private function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-z]{26}$/i', $id);
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->path) && ! @mkdir($this->path, 0755, true) && ! is_dir($this->path)) {
            throw new RuntimeException("Notification Inbox could not create [{$this->path}].");
        }

        // Captured notifications can hold personal data: keep them out of git.
        $ignore = $this->path.DIRECTORY_SEPARATOR.'.gitignore';

        if (! is_file($ignore)) {
            @file_put_contents($ignore, "*\n!.gitignore\n");
        }
    }
}
