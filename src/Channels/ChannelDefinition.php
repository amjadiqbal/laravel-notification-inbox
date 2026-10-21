<?php

namespace AmjadIqbal\NotificationInbox\Channels;

/**
 * How one notification channel builds its message: the method it calls on the
 * notification, the routeNotificationFor() keys it reads, and how to preview it.
 */
final readonly class ChannelDefinition
{
    /**
     * @param  list<string>  $methods  tried in order; the first one the notification has is used
     * @param  list<string>  $routes  routeNotificationFor() keys, in the order the channel itself tries them
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $methods,
        public array $routes,
        public string $preview,
    ) {}

    /**
     * @param  array{label?: string, method?: string|list<string>, route?: string|list<string>|null, preview?: string}  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        $methods = $config['method'] ?? 'to'.ChannelRegistry::studly($key);

        return new self(
            key: ChannelRegistry::shortName($key),
            label: $config['label'] ?? ChannelRegistry::studly($key),
            methods: is_array($methods) ? $methods : [$methods],
            routes: array_key_exists('route', $config) ? array_values(array_filter((array) $config['route'])) : array_values(array_unique([lcfirst(ChannelRegistry::studly($key)), $key])),
            preview: $config['preview'] ?? 'json',
        );
    }
}
