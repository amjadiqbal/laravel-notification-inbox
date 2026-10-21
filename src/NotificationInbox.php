<?php

namespace AmjadIqbal\NotificationInbox;

use AmjadIqbal\NotificationInbox\Channels\ChannelDefinition;
use AmjadIqbal\NotificationInbox\Channels\ChannelRegistry;
use AmjadIqbal\NotificationInbox\Storage\ArrayStore;
use AmjadIqbal\NotificationInbox\Storage\Store;
use AmjadIqbal\NotificationInbox\Support\PayloadSerializer;
use AmjadIqbal\NotificationInbox\Testing\AssertsCapturedNotifications;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

class NotificationInbox
{
    use AssertsCapturedNotifications;

    private bool $faked = false;

    /**
     * @param  array<string, mixed>  $config  config('notification-inbox')
     */
    public function __construct(
        private Store $store,
        private readonly ChannelRegistry $channels,
        private readonly PayloadSerializer $serializer,
        private readonly array $config,
        private readonly string $environment,
    ) {}

    /**
     * Is the inbox catching notifications right now?
     */
    public function isActive(): bool
    {
        return $this->faked || ((bool) ($this->config['enabled'] ?? false) && $this->environmentAllowed());
    }

    /**
     * The current environment is on the allow-list, and production only with
     * the separate allow_production switch.
     */
    public function environmentAllowed(): bool
    {
        if (! in_array($this->environment, $this->environments(), true)) {
            return false;
        }

        return $this->environment !== 'production' || (bool) ($this->config['allow_production'] ?? false);
    }

    /**
     * Production is intercepting because someone set both switches. Loud on purpose.
     */
    public function isInterceptingProduction(): bool
    {
        return ! $this->faked && $this->environment === 'production' && $this->isActive();
    }

    /**
     * Production is on the allow-list but allow_production is off, so it is ignored.
     */
    public function productionListedButIgnored(): bool
    {
        return $this->environment === 'production'
            && in_array('production', $this->environments(), true)
            && ! (bool) ($this->config['allow_production'] ?? false);
    }

    public function intercepts(string $channel): bool
    {
        $short = ChannelRegistry::shortName($channel);
        $passThrough = (array) ($this->config['pass_through'] ?? []);

        if (in_array($channel, $passThrough, true) || in_array($short, $passThrough, true)) {
            return false;
        }

        $intercept = (array) ($this->config['intercept'] ?? ['*']);

        return in_array('*', $intercept, true) || in_array($channel, $intercept, true) || in_array($short, $intercept, true);
    }

    /**
     * Build the message exactly as the channel would, and keep it instead of sending it.
     */
    public function capture(object $notifiable, Notification $notification, string $channel): CapturedNotification
    {
        $definition = $this->channels->for($channel);
        $meta = [];
        $route = null;
        $payload = null;
        $messageClass = null;
        $error = null;

        try {
            $message = $this->buildMessage($definition, $notifiable, $notification);
            $messageClass = is_object($message) ? $message::class : null;

            if ($definition->preview === 'broadcast') {
                [$payload, $route, $meta] = $this->broadcastDetails($notifiable, $notification, $message);
            } else {
                $payload = $this->serializer->serialize($message);
                $route = $this->serializer->serialize($this->route($definition, $notifiable, $notification));
            }
        } catch (Throwable $e) {
            // The real channel would have thrown here too: show the developer why.
            $error = class_basename($e).': '.$e->getMessage();
        }

        $captured = new CapturedNotification(
            id: (string) Str::ulid(),
            notificationId: isset($notification->id) ? (string) $notification->id : null,
            notification: $notification::class,
            channel: $channel,
            channelKey: $definition->key,
            channelLabel: $definition->label,
            previewType: $definition->preview,
            notifiable: $this->describeNotifiable($notifiable),
            route: $route,
            payload: $payload,
            messageClass: $messageClass,
            error: $error,
            queued: $notification instanceof ShouldQueue,
            environment: $this->environment,
            capturedAt: CarbonImmutable::now(),
            meta: $meta,
        );

        $this->store->put($captured);
        $this->maybePrune();

        return $captured;
    }

    /**
     * @return list<CapturedNotification>
     */
    public function all(?string $after = null): array
    {
        return $this->store->all($after);
    }

    public function find(string $id): ?CapturedNotification
    {
        return $this->store->find($id);
    }

    public function delete(string $id): bool
    {
        return $this->store->delete($id);
    }

    public function clear(): int
    {
        return $this->store->clear();
    }

    public function prune(?int $hours = null): int
    {
        $hours ??= (int) ($this->config['retention_hours'] ?? 72);

        return $this->store->prune(
            CarbonImmutable::now()->subHours(max(0, $hours)),
            max(1, (int) ($this->config['max_messages'] ?? 1000)),
        );
    }

    /**
     * Catch notifications in memory for the rest of this test, whatever the environment.
     */
    public function fake(): static
    {
        $this->store = new ArrayStore;
        $this->faked = true;

        return $this;
    }

    public function isFaked(): bool
    {
        return $this->faked;
    }

    public function store(): Store
    {
        return $this->store;
    }

    public function channels(): ChannelRegistry
    {
        return $this->channels;
    }

    /**
     * @return list<string>
     */
    public function environments(): array
    {
        return array_values(array_map('strval', (array) ($this->config['environments'] ?? [])));
    }

    public function environment(): string
    {
        return $this->environment;
    }

    private function buildMessage(ChannelDefinition $definition, object $notifiable, Notification $notification): mixed
    {
        foreach ($definition->methods as $method) {
            if (method_exists($notification, $method)) {
                // Some channels pass the notification too (WebPushChannel does); extra arguments are harmless.
                return $notification->{$method}($notifiable, $notification);
            }
        }

        throw new \BadMethodCallException(sprintf(
            '%s has no %s() method, so the %s channel would fail to send it.',
            class_basename($notification),
            $definition->methods[0] ?? 'to'.ChannelRegistry::studly($definition->key),
            $definition->label,
        ));
    }

    private function route(ChannelDefinition $definition, object $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        foreach ($definition->routes as $key) {
            $route = $notifiable->routeNotificationFor($key, $notification);

            if ($route !== null && $route !== false && $route !== '' && $route !== []) {
                return $route;
            }
        }

        return null;
    }

    /**
     * The broadcast channel wraps the message in a BroadcastNotificationCreated
     * event. Build that same event to show the real channels, name and data.
     *
     * @return array{0: mixed, 1: mixed, 2: array<string, mixed>}
     */
    private function broadcastDetails(object $notifiable, Notification $notification, mixed $message): array
    {
        $data = is_object($message) && property_exists($message, 'data') ? $message->data : $message;
        $event = new BroadcastNotificationCreated($notifiable, $notification, is_array($data) ? $data : []);

        $channels = array_map(
            fn (mixed $channel): string => is_object($channel) && property_exists($channel, 'name') ? (string) $channel->name : (string) $channel,
            (array) $event->broadcastOn(),
        );

        return [
            $this->serializer->serialize($event->broadcastWith()),
            $channels,
            [
                'event' => $event->broadcastAs(),
                'connection' => is_object($message) && property_exists($message, 'connection') ? $message->connection : null,
            ],
        ];
    }

    /**
     * Type, key and a short label. Never the model's attributes: they are personal data.
     *
     * @return array{type: string, key: mixed, label: string}
     */
    private function describeNotifiable(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            // The route itself is shown with the preview; webhook routes are secrets, so not here.
            return ['type' => AnonymousNotifiable::class, 'key' => null, 'label' => 'On-demand'];
        }

        $key = $notifiable instanceof Model ? $notifiable->getKey() : (method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null);
        $key = is_scalar($key) ? $key : null;

        return [
            'type' => $notifiable::class,
            'key' => $key,
            'label' => class_basename($notifiable).($key !== null ? ' #'.$key : ''),
        ];
    }

    private function maybePrune(): void
    {
        // Like Laravel's session lottery: [2, 100] prunes on about 2 captures in 100; [0, 1] never.
        [$wins, $of] = array_map('intval', array_pad((array) ($this->config['prune_lottery'] ?? [1, 50]), 2, 1));

        if ($this->faked || $wins <= 0 || random_int(1, max(1, $of)) > $wins) {
            return;
        }

        try {
            $this->prune();
        } catch (Throwable) {
            // Pruning is housekeeping; never let it break the request that sent a notification.
        }
    }
}
