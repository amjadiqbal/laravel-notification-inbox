<?php

namespace AmjadIqbal\NotificationInbox;

use AmjadIqbal\NotificationInbox\Previews\PreviewFactory;
use Carbon\CarbonImmutable;

/**
 * One notification on one channel that was caught instead of sent.
 */
final class CapturedNotification
{
    /** @var array<string, mixed>|null */
    private ?array $preview = null;

    /**
     * @param  array{type: string, key: mixed, label: string}  $notifiable
     * @param  array<string, mixed>  $meta  channel-specific extras, e.g. the broadcast event name
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $notificationId,
        public readonly string $notification,
        public readonly string $channel,
        public readonly string $channelKey,
        public readonly string $channelLabel,
        public readonly string $previewType,
        public readonly array $notifiable,
        public readonly mixed $route,
        public readonly mixed $payload,
        public readonly ?string $messageClass,
        public readonly ?string $error,
        public readonly bool $queued,
        public readonly string $environment,
        public readonly CarbonImmutable $capturedAt,
        public readonly array $meta = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array{type: string, key: mixed, label: string} $notifiable */
        $notifiable = $data['notifiable'] ?? ['type' => '', 'key' => null, 'label' => ''];

        return new self(
            id: (string) $data['id'],
            notificationId: isset($data['notification_id']) ? (string) $data['notification_id'] : null,
            notification: (string) $data['notification'],
            channel: (string) $data['channel'],
            channelKey: (string) $data['channel_key'],
            channelLabel: (string) $data['channel_label'],
            previewType: (string) $data['preview'],
            notifiable: $notifiable,
            route: $data['route'] ?? null,
            payload: $data['payload'] ?? null,
            messageClass: isset($data['message_class']) ? (string) $data['message_class'] : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
            queued: (bool) ($data['queued'] ?? false),
            environment: (string) ($data['environment'] ?? ''),
            capturedAt: CarbonImmutable::parse((string) $data['captured_at']),
            meta: (array) ($data['meta'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notificationId,
            'notification' => $this->notification,
            'channel' => $this->channel,
            'channel_key' => $this->channelKey,
            'channel_label' => $this->channelLabel,
            'preview' => $this->previewType,
            'notifiable' => $this->notifiable,
            'route' => $this->route,
            'payload' => $this->payload,
            'message_class' => $this->messageClass,
            'error' => $this->error,
            'queued' => $this->queued,
            'environment' => $this->environment,
            'captured_at' => $this->capturedAt->format('Y-m-d\TH:i:s.uP'),
            'meta' => $this->meta,
        ];
    }

    /**
     * Channel-neutral view of the message: title, body, buttons, image and so on.
     *
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        return $this->preview ??= PreviewFactory::make($this->previewType)->normalize($this);
    }

    public function title(): ?string
    {
        $title = $this->preview()['title'] ?? null;

        return is_string($title) && $title !== '' ? $title : null;
    }

    /**
     * The main text of the message, as plain text.
     */
    public function body(): string
    {
        $body = $this->preview()['text'] ?? '';

        return is_string($body) ? $body : '';
    }

    /**
     * Title and body together, for searching and assertions.
     */
    public function text(): string
    {
        return trim(($this->title() ?? '')."\n".$this->body());
    }

    /**
     * @return list<array{label: string, url: string|null}>
     */
    public function buttons(): array
    {
        /** @var list<array{label: string, url: string|null}> $buttons */
        $buttons = $this->preview()['buttons'] ?? [];

        return $buttons;
    }

    public function summary(): string
    {
        if ($this->error !== null) {
            return 'Could not build the message';
        }

        $line = $this->title() ?? strtok($this->body(), "\n");

        return $line === false || $line === '' ? class_basename($this->notification) : $line;
    }

    public function is(string $notification): bool
    {
        return $this->notification === $notification || is_subclass_of($this->notification, $notification);
    }

    public function isOn(string $channel): bool
    {
        return $this->channel === $channel || $this->channelKey === $channel;
    }

    public function isFor(mixed $notifiable): bool
    {
        if (is_object($notifiable) && method_exists($notifiable, 'getKey')) {
            return $this->notifiable['type'] === $notifiable::class && $this->notifiable['key'] == $notifiable->getKey();
        }

        return $this->routeContains($this->route, $notifiable);
    }

    private function routeContains(mixed $route, mixed $needle): bool
    {
        if (is_array($route)) {
            foreach ($route as $item) {
                if ($this->routeContains($item, $needle)) {
                    return true;
                }
            }

            return false;
        }

        return is_scalar($route) && is_scalar($needle) && (string) $route === (string) $needle;
    }
}
