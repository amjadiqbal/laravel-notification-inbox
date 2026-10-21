<?php

namespace AmjadIqbal\NotificationInbox\Support;

/**
 * Masks secrets before a captured notification is written to disk.
 */
final class Redactor
{
    public const MASK = '[redacted]';

    /** @var array<string, true> */
    private array $keys = [];

    /**
     * @param  list<string>  $keys
     */
    public function __construct(array $keys)
    {
        foreach ($keys as $key) {
            $this->keys[strtolower($key)] = true;
        }
    }

    public function payload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && isset($this->keys[strtolower($key)]) && $item !== null && $item !== '') {
                $value[$key] = self::MASK;
            } else {
                $value[$key] = $this->payload($item);
            }
        }

        return $value;
    }

    /**
     * Webhook URLs carry their secret in the path, so routes keep only the host.
     */
    public function route(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->route($item), $this->payload($value));
        }

        if (is_string($value) && preg_match('#^(https?://[^/?\#]+)[/?\#].+#i', $value, $match)) {
            return $match[1].'/…';
        }

        return $value;
    }
}
