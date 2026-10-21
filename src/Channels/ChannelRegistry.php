<?php

namespace AmjadIqbal\NotificationInbox\Channels;

use Illuminate\Support\Str;

/**
 * Knows how the popular channels build their payload. Every method and route
 * key below was read from the channel package's own send() method.
 *
 * Channels are matched by the exact string a notification returns from via(),
 * which is an alias ('slack') or a class name (WebPushChannel::class).
 */
final class ChannelRegistry
{
    /**
     * 'route' lists the routeNotificationFor() keys in the order the channel tries them. A route the
     * real channel would not find is not found here either, so the preview shows what would happen.
     *
     * @var array<string, array{label: string, method: string|list<string>, route: string|list<string>|null, preview: string}>
     */
    private const BUILT_IN = [
        // Laravel's own channels.
        'broadcast' => ['label' => 'Broadcast', 'method' => ['toBroadcast', 'toArray'], 'route' => null, 'preview' => 'broadcast'],
        'database' => ['label' => 'Database', 'method' => ['toDatabase', 'toArray'], 'route' => null, 'preview' => 'json'],
        'slack' => ['label' => 'Slack', 'method' => 'toSlack', 'route' => 'slack', 'preview' => 'slack'],
        'vonage' => ['label' => 'Vonage SMS', 'method' => 'toVonage', 'route' => 'vonage', 'preview' => 'sms'],
        'nexmo' => ['label' => 'Vonage SMS', 'method' => 'toNexmo', 'route' => 'nexmo', 'preview' => 'sms'],
        'sms' => ['label' => 'SMS', 'method' => 'toSms', 'route' => 'sms', 'preview' => 'sms'],

        // laravel-notification-channels/*
        'NotificationChannels\WebPush\WebPushChannel' => ['label' => 'Web push', 'method' => 'toWebPush', 'route' => 'WebPush', 'preview' => 'push'],
        'NotificationChannels\Fcm\FcmChannel' => ['label' => 'FCM', 'method' => 'toFcm', 'route' => 'fcm', 'preview' => 'push'],
        'NotificationChannels\Fcm\FcmTopicChannel' => ['label' => 'FCM topic', 'method' => 'toFcm', 'route' => 'fcm-topic', 'preview' => 'push'],
        'NotificationChannels\Apn\ApnChannel' => ['label' => 'APNs', 'method' => 'toApn', 'route' => 'apn', 'preview' => 'push'],
        'NotificationChannels\Apn\ApnVoipChannel' => ['label' => 'APNs VoIP', 'method' => 'toApnVoip', 'route' => 'apn_voip', 'preview' => 'push'],
        'NotificationChannels\Expo\ExpoChannel' => ['label' => 'Expo push', 'method' => 'toExpo', 'route' => 'expo', 'preview' => 'push'],
        'expo' => ['label' => 'Expo push', 'method' => 'toExpo', 'route' => 'expo', 'preview' => 'push'],
        'NotificationChannels\OneSignal\OneSignalChannel' => ['label' => 'OneSignal', 'method' => 'toOneSignal', 'route' => 'OneSignal', 'preview' => 'push'],
        'NotificationChannels\Telegram\TelegramChannel' => ['label' => 'Telegram', 'method' => 'toTelegram', 'route' => ['telegram', 'NotificationChannels\Telegram\TelegramChannel'], 'preview' => 'telegram'],
        'telegram' => ['label' => 'Telegram', 'method' => 'toTelegram', 'route' => ['telegram', 'NotificationChannels\Telegram\TelegramChannel'], 'preview' => 'telegram'],
        'NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel' => ['label' => 'Microsoft Teams', 'method' => 'toMicrosoftTeams', 'route' => 'microsoftTeams', 'preview' => 'teams'],
        'NotificationChannels\Discord\DiscordChannel' => ['label' => 'Discord', 'method' => 'toDiscord', 'route' => 'discord', 'preview' => 'discord'],
        'NotificationChannels\Twilio\TwilioChannel' => ['label' => 'Twilio SMS', 'method' => 'toTwilio', 'route' => ['NotificationChannels\Twilio\TwilioChannel', 'twilio'], 'preview' => 'sms'],
        'twilio' => ['label' => 'Twilio SMS', 'method' => 'toTwilio', 'route' => ['NotificationChannels\Twilio\TwilioChannel', 'twilio'], 'preview' => 'sms'],
        'NotificationChannels\Messagebird\MessagebirdChannel' => ['label' => 'MessageBird SMS', 'method' => 'toMessagebird', 'route' => 'messagebird', 'preview' => 'sms'],
        'NotificationChannels\Plivo\PlivoChannel' => ['label' => 'Plivo SMS', 'method' => 'toPlivo', 'route' => 'plivo', 'preview' => 'sms'],
        'NotificationChannels\AwsSns\SnsChannel' => ['label' => 'AWS SNS', 'method' => 'toSns', 'route' => 'sns', 'preview' => 'sms'],
        'NotificationChannels\GoogleChat\GoogleChatChannel' => ['label' => 'Google Chat', 'method' => 'toGoogleChat', 'route' => 'googleChat', 'preview' => 'json'],
        'NotificationChannels\Pushover\PushoverChannel' => ['label' => 'Pushover', 'method' => 'toPushover', 'route' => 'pushover', 'preview' => 'push'],
    ];

    /** @var array<string, ChannelDefinition> */
    private array $resolved = [];

    /**
     * @param  array<string, array<string, mixed>>  $custom  config('notification-inbox.channels')
     */
    public function __construct(private readonly array $custom = []) {}

    public function for(string $channel): ChannelDefinition
    {
        if (isset($this->resolved[$channel])) {
            return $this->resolved[$channel];
        }

        /** @var array{label?: string, method?: string|list<string>, route?: string|list<string>|null, preview?: string}|null $config */
        $config = $this->custom[$channel] ?? self::BUILT_IN[$channel] ?? null;

        if ($config === null) {
            // Unknown channel: guess the Laravel convention from its short name
            // (App\Channels\WhatsAppChannel -> toWhatsApp, route 'whatsApp', then the class itself).
            $config = ['label' => self::studly($channel)];
        }

        return $this->resolved[$channel] = ChannelDefinition::fromConfig($channel, $config);
    }

    public function isKnown(string $channel): bool
    {
        return isset($this->custom[$channel]) || isset(self::BUILT_IN[$channel]);
    }

    /**
     * 'NotificationChannels\Telegram\TelegramChannel' -> 'telegram', 'slack' -> 'slack'.
     */
    public static function shortName(string $channel): string
    {
        $base = class_basename($channel);

        if ($base !== 'Channel' && str_ends_with($base, 'Channel')) {
            $base = substr($base, 0, -7);
        }

        return Str::kebab($base);
    }

    public static function studly(string $channel): string
    {
        return Str::studly(self::shortName($channel));
    }
}
