<?php

namespace AmjadIqbal\NotificationInbox\Previews;

/**
 * Small helpers for digging fields out of serialized payloads.
 */
final class Payload
{
    /**
     * The first non-empty string found at any of the dot paths.
     *
     * @param  list<string>  $paths
     */
    public static function firstString(mixed $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function array(mixed $payload, string $path): array
    {
        $value = data_get($payload, $path);

        return is_array($value) ? $value : [];
    }
}
