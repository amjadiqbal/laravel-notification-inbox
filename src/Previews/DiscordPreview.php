<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Support\Markup;

/**
 * Discord messages with embeds and button components.
 */
final class DiscordPreview implements Preview
{
    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $content = Payload::firstString($p, ['body', 'content']) ?? '';

        $embeds = Payload::array($p, 'embeds');
        if (($embed = Payload::array($p, 'embed')) !== []) {
            array_unshift($embeds, $embed);
        }
        $embeds = array_values(array_filter($embeds, fn ($e) => is_array($e) && $e !== []));

        $embeds = array_map(fn (array $e): array => [
            'title' => Payload::firstString($e, ['title']),
            'url' => Payload::firstString($e, ['url']),
            'description' => Payload::firstString($e, ['description']),
            'color' => is_int($e['color'] ?? null) ? sprintf('#%06X', $e['color'] & 0xFFFFFF) : null,
            'author' => Payload::firstString($e, ['author.name']),
            'fields' => array_values(array_map(fn ($f) => [
                'name' => (string) data_get($f, 'name', ''),
                'value' => (string) data_get($f, 'value', ''),
                'inline' => (bool) data_get($f, 'inline', false),
            ], array_filter((array) ($e['fields'] ?? []), 'is_array'))),
            'image' => Payload::firstString($e, ['image.url']),
            'thumbnail' => Payload::firstString($e, ['thumbnail.url']),
            'footer' => Payload::firstString($e, ['footer.text']),
        ], $embeds);

        $buttons = [];
        foreach (Payload::array($p, 'components') as $row) {
            foreach ((array) data_get($row, 'components', [$row]) as $component) {
                if (($label = Payload::firstString($component, ['label'])) !== null) {
                    $buttons[] = ['label' => $label, 'url' => Payload::firstString($component, ['url'])];
                }
            }
        }

        $texts = [$content];
        foreach ($embeds as $e) {
            $texts[] = $e['description'];
            foreach ($e['fields'] as $f) {
                $texts[] = $f['name'].' '.$f['value'];
            }
        }

        return [
            'title' => $embeds[0]['title'] ?? null,
            'text' => Markup::plain(implode("\n", array_filter($texts))),
            'content' => $content,
            'embeds' => $embeds,
            'username' => Payload::firstString($p, ['username']) ?? config('app.name'),
            'buttons' => $buttons,
        ];
    }

    public function view(): string
    {
        return 'notification-inbox::previews.discord';
    }
}
