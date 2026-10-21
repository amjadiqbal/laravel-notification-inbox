<?php

namespace AmjadIqbal\NotificationInbox\Channels;

/**
 * How one notification channel builds its message: the method it calls on the
 * notification, the routeNotificationFor() key it reads, and how to preview it.
 */
final readonly class ChannelDefinition
{
    /**
     * @param  list<string>  $methods  tried in order; the first one the notification has is used
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $methods,
        public ?string $route,
        public string $preview,
    ) {}

    /**
     * @param  array{label?: string, method?: string|list<string>, route?: string|null, preview?: string}  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        $methods = $config['method'] ?? 'to'.ChannelRegistry::studly($key);

        return new self(
            key: ChannelRegistry::shortName($key),
            label: $config['label'] ?? ChannelRegistry::studly($key),
            methods: is_array($methods) ? $methods : [$methods],
            route: array_key_exists('route', $config) ? $config['route'] : lcfirst(ChannelRegistry::studly($key)),
            preview: $config['preview'] ?? 'json',
        );
    }
}
