<?php

namespace AmjadIqbal\NotificationInbox\Support;

/**
 * Masks secrets before a captured notification is written to disk.
 */
final class Redactor
{
    public const MASK = '[redacted]';

    public const CODE_MASK = '••••••';

    /** @var array<string, true> */
    private array $keys = [];

    /** @var list<string> */
    private array $fragments = [];

    /**
     * @param  list<string>  $keys  exact key names, case-insensitive
     * @param  list<string>  $containing  any key containing one of these, case-insensitive
     * @param  bool  $maskCodes  also mask 4-8 digit codes inside text values (one-time codes)
     */
    public function __construct(array $keys, array $containing = [], private readonly bool $maskCodes = false)
    {
        foreach ($keys as $key) {
            $this->keys[strtolower($key)] = true;
        }

        $this->fragments = array_values(array_filter(array_map('strtolower', $containing)));
    }

    public function payload(mixed $value, bool $maskCodes = true): mixed
    {
        if (is_string($value)) {
            return $maskCodes && $this->maskCodes ? $this->codes($value) : $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && $this->isSecretKey($key) && $item !== null && $item !== '' && $item !== []) {
                $value[$key] = self::MASK;
            } else {
                $value[$key] = $this->payload($item, $maskCodes);
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
            // Routes are recipients (chat ids, numbers), not message text: keys are redacted, codes are not masked.
            return array_map(fn (mixed $item): mixed => $this->route($item), $this->payload($value, maskCodes: false));
        }

        if (is_string($value) && preg_match('#^(https?://[^/?\#]+)[/?\#].+#i', $value, $match)) {
            return $match[1].'/…';
        }

        return $value;
    }

    public function isSecretKey(string $key): bool
    {
        $key = strtolower($key);

        if (isset($this->keys[$key])) {
            return true;
        }

        foreach ($this->fragments as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * "Your code is 482913" -> "Your code is ••••••". Longer digit runs (phone numbers) are left alone.
     */
    private function codes(string $text): string
    {
        return preg_replace('/(?<![\d+\-])\d{4,8}(?![\d\-])/', self::CODE_MASK, $text) ?? $text;
    }
}
