<?php

namespace AmjadIqbal\NotificationInbox\Previews;

final class PreviewFactory
{
    /** @var array<string, class-string<Preview>> */
    private const PREVIEWS = [
        'push' => PushPreview::class,
        'sms' => SmsPreview::class,
        'slack' => SlackPreview::class,
        'teams' => TeamsPreview::class,
        'telegram' => TelegramPreview::class,
        'discord' => DiscordPreview::class,
        'broadcast' => BroadcastPreview::class,
        'json' => JsonPreview::class,
    ];

    public static function make(string $type): Preview
    {
        $class = self::PREVIEWS[$type] ?? JsonPreview::class;

        return new $class;
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::PREVIEWS);
    }
}
