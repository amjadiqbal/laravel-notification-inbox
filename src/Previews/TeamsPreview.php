<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Support\Markup;

/**
 * Microsoft Teams Adaptive Cards, and the older MessageCard format.
 */
final class TeamsPreview implements Preview
{
    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $card = data_get($p, 'attachments.0.content');

        if (! is_array($card) && data_get($p, 'type') === 'AdaptiveCard') {
            $card = $p;
        }

        if (is_array($card)) {
            $body = array_values(array_filter((array) ($card['body'] ?? []), 'is_array'));
            $actions = array_values(array_filter((array) ($card['actions'] ?? []), 'is_array'));
            $texts = $this->texts($body);

            return [
                'title' => $texts[0] ?? null,
                'text' => Markup::plain(implode("\n", array_slice($texts, 1))),
                'format' => 'adaptive',
                'body' => $body,
                'actions' => $actions,
                'buttons' => $this->buttons($actions),
            ];
        }

        // Legacy MessageCard (connectors): title, text, sections with facts, potentialAction.
        $actions = Payload::array($p, 'potentialAction');

        return [
            'title' => Payload::firstString($p, ['title', 'summary']),
            'text' => Markup::plain((string) Payload::firstString($p, ['text'])),
            'format' => 'message-card',
            'card' => $p,
            'color' => Payload::firstString($p, ['themeColor']),
            'buttons' => $this->buttons($actions),
        ];
    }

    /**
     * Every text in reading order, walking containers and columns.
     *
     * @param  array<array-key, mixed>  $elements
     * @return list<string>
     */
    private function texts(array $elements): array
    {
        $texts = [];

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            if (in_array($element['type'] ?? null, ['TextBlock', 'RichTextBlock'], true)) {
                $text = Payload::firstString($element, ['text']) ?? implode('', array_map(fn ($run) => is_array($run) ? (string) ($run['text'] ?? '') : (string) $run, (array) ($element['inlines'] ?? [])));
                if ($text !== '') {
                    $texts[] = $text;
                }
            } elseif (($element['type'] ?? null) === 'FactSet') {
                foreach ((array) ($element['facts'] ?? []) as $fact) {
                    $texts[] = trim(Payload::firstString($fact, ['title']).' '.Payload::firstString($fact, ['value']));
                }
            }

            foreach (['items', 'columns', 'body'] as $children) {
                if (isset($element[$children]) && is_array($element[$children])) {
                    array_push($texts, ...$this->texts($element[$children]));
                }
            }
        }

        return $texts;
    }

    /**
     * @param  array<array-key, mixed>  $actions
     * @return list<array{label: string, url: string|null}>
     */
    private function buttons(array $actions): array
    {
        $buttons = [];

        foreach ($actions as $action) {
            $label = Payload::firstString($action, ['title', 'name']);
            if ($label !== null) {
                $buttons[] = ['label' => $label, 'url' => Payload::firstString($action, ['url', 'targets.0.uri'])];
            }
        }

        return $buttons;
    }

    public function view(): string
    {
        return 'notification-inbox::previews.teams';
    }
}
