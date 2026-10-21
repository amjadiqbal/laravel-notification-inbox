<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Support\Markup;

/**
 * Slack Block Kit (Laravel's current SlackMessage) and the legacy webhook
 * message with attachments.
 */
final class SlackPreview implements Preview
{
    private const LEVEL_COLORS = ['success' => '#2EB67D', 'warning' => '#ECB22E', 'error' => '#E01E5A', 'danger' => '#E01E5A', 'info' => '#1D9BD1'];

    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $blocks = array_values(array_filter(Payload::array($p, 'blocks'), 'is_array'));
        $attachments = $this->attachments($p);

        $title = null;
        $texts = [];
        $buttons = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'header') {
                $title ??= Payload::firstString($block, ['text.text']);
            } elseif ($type === 'section') {
                $texts[] = Payload::firstString($block, ['text.text']);
                foreach ((array) ($block['fields'] ?? []) as $field) {
                    $texts[] = Payload::firstString($field, ['text']);
                }
            } elseif ($type === 'context') {
                foreach ((array) ($block['elements'] ?? []) as $element) {
                    $texts[] = Payload::firstString($element, ['text']);
                }
            } elseif ($type === 'actions') {
                foreach ((array) ($block['elements'] ?? []) as $element) {
                    if (($label = Payload::firstString($element, ['text.text'])) !== null) {
                        $buttons[] = ['label' => $label, 'url' => Payload::firstString($element, ['url'])];
                    }
                }
            }
        }

        $fallback = Payload::firstString($p, ['text', 'content']);

        if ($blocks === []) {
            $texts[] = $fallback;
            foreach ($attachments as $attachment) {
                $title ??= $attachment['title'];
                $texts[] = $attachment['text'];
            }
        }

        return [
            'title' => $title,
            'text' => Markup::plain(implode("\n", array_filter($texts))),
            'blocks' => $blocks,
            'attachments' => $attachments,
            'fallback' => $fallback,
            'username' => Payload::firstString($p, ['username']) ?? config('app.name'),
            'icon' => Payload::firstString($p, ['icon_emoji', 'icon', 'icon_url']),
            'channel' => Payload::firstString($p, ['channel']),
            'buttons' => $buttons,
        ];
    }

    /**
     * Legacy Messages\SlackMessage attachments, read from its properties.
     *
     * @return list<array{title: ?string, url: ?string, pretext: ?string, text: ?string, color: string, fields: list<array{title: string, value: string}>, footer: ?string, image: ?string}>
     */
    private function attachments(mixed $p): array
    {
        $level = Payload::firstString($p, ['level']);
        $out = [];

        foreach (Payload::array($p, 'attachments') as $a) {
            $fields = [];
            foreach ((array) (data_get($a, 'fields') ?? []) as $key => $value) {
                $fields[] = is_array($value)
                    ? ['title' => (string) ($value['title'] ?? ''), 'value' => (string) ($value['value'] ?? $value['content'] ?? '')]
                    : ['title' => (string) $key, 'value' => is_scalar($value) ? (string) $value : ''];
            }

            $color = Payload::firstString($a, ['color']) ?? ($level ? self::LEVEL_COLORS[$level] ?? null : null);

            $out[] = [
                'title' => Payload::firstString($a, ['title']),
                'url' => Payload::firstString($a, ['url', 'title_link']),
                'pretext' => Payload::firstString($a, ['pretext']),
                'text' => Payload::firstString($a, ['content', 'text']),
                'color' => self::LEVEL_COLORS[$color] ?? ($color && preg_match('/^#?[0-9a-f]{3,8}$/i', $color) ? '#'.ltrim($color, '#') : '#DDDDDD'),
                'fields' => $fields,
                'footer' => Payload::firstString($a, ['footer']),
                'image' => Payload::firstString($a, ['imageUrl', 'image_url', 'thumbUrl', 'thumb_url']),
            ];
        }

        return $out;
    }

    public function view(): string
    {
        return 'notification-inbox::previews.slack';
    }
}
