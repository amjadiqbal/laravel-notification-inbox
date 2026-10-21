<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Support\Markup;
use Illuminate\Support\HtmlString;

/**
 * Telegram messages, files, locations, contacts and polls as a chat bubble
 * with its inline keyboard.
 */
final class TelegramPreview implements Preview
{
    private const MEDIA = ['photo', 'animation', 'video', 'document', 'audio', 'voice', 'sticker', 'video_note'];

    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $raw = Payload::firstString($p, ['text', 'caption']) ?? '';
        $mode = strtolower((string) Payload::firstString($p, ['parse_mode']));

        $media = null;
        foreach (self::MEDIA as $type) {
            if (($value = Payload::firstString($p, [$type])) !== null) {
                $media = ['type' => $type, 'url' => $value];
                break;
            }
        }

        $markup = data_get($p, 'reply_markup');
        if (is_string($markup)) {
            $markup = json_decode($markup, true);
        }

        $rows = [];
        $buttons = [];
        foreach ((array) data_get($markup, 'inline_keyboard', []) as $row) {
            $cells = [];
            foreach ((array) $row as $button) {
                $label = Payload::firstString($button, ['text']) ?? '';
                $url = Payload::firstString($button, ['url', 'web_app.url', 'login_url.url']);
                $cells[] = ['label' => $label, 'url' => $url];
                $buttons[] = ['label' => $label, 'url' => $url];
            }
            $rows[] = $cells;
        }

        $poll = Payload::firstString($p, ['question']) !== null ? [
            'question' => Payload::firstString($p, ['question']),
            'options' => array_map(fn ($o) => is_array($o) ? (string) ($o['text'] ?? '') : (string) $o, $this->decoded($p, 'options')),
        ] : null;

        return [
            'title' => null,
            'text' => match ($mode) {
                'markdown' => Markup::telegramPlain($raw),
                'markdownv2' => Markup::telegramPlain($raw, v2: true),
                default => Markup::plain($raw),
            },
            'html' => $this->html($raw, $mode),
            'chat' => Payload::firstString($p, ['chat_id']) ?? (is_scalar($notification->route) ? (string) $notification->route : null),
            'media' => $media,
            'location' => data_get($p, 'latitude') !== null ? ['lat' => data_get($p, 'latitude'), 'lng' => data_get($p, 'longitude')] : null,
            'contact' => Payload::firstString($p, ['phone_number']) !== null ? trim(Payload::firstString($p, ['first_name']).' '.Payload::firstString($p, ['last_name']).' '.Payload::firstString($p, ['phone_number'])) : null,
            'poll' => $poll,
            'keyboard' => $rows,
            'buttons' => $buttons,
        ];
    }

    private function html(string $raw, string $mode): HtmlString
    {
        return match ($mode) {
            'html' => Markup::telegramHtml($raw),
            'markdown' => Markup::telegramMarkdown($raw),
            'markdownv2' => Markup::telegramMarkdown($raw, v2: true),
            default => new HtmlString(nl2br(e($raw), false)),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decoded(mixed $p, string $key): array
    {
        $value = data_get($p, $key);

        return is_string($value) ? (array) json_decode($value, true) : (array) $value;
    }

    public function view(): string
    {
        return 'notification-inbox::previews.telegram';
    }
}
