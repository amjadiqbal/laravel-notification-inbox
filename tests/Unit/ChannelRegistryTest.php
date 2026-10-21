<?php

use AmjadIqbal\NotificationInbox\Channels\ChannelRegistry;

it('knows the popular channels by alias and by class', function (string $channel, string $key, array $methods, ?string $route, string $preview) {
    $definition = (new ChannelRegistry)->for($channel);

    expect($definition->key)->toBe($key)
        ->and($definition->methods)->toBe($methods)
        ->and($definition->route)->toBe($route)
        ->and($definition->preview)->toBe($preview);
})->with([
    ['slack', 'slack', ['toSlack'], 'slack', 'slack'],
    ['vonage', 'vonage', ['toVonage'], 'vonage', 'sms'],
    ['broadcast', 'broadcast', ['toBroadcast', 'toArray'], null, 'broadcast'],
    ['NotificationChannels\WebPush\WebPushChannel', 'web-push', ['toWebPush'], 'WebPush', 'push'],
    ['NotificationChannels\Fcm\FcmChannel', 'fcm', ['toFcm'], 'fcm', 'push'],
    ['NotificationChannels\Apn\ApnChannel', 'apn', ['toApn'], 'apn', 'push'],
    ['NotificationChannels\Expo\ExpoChannel', 'expo', ['toExpo'], 'expo', 'push'],
    ['NotificationChannels\Telegram\TelegramChannel', 'telegram', ['toTelegram'], 'telegram', 'telegram'],
    ['NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel', 'microsoft-teams', ['toMicrosoftTeams'], 'microsoftTeams', 'teams'],
    ['NotificationChannels\Discord\DiscordChannel', 'discord', ['toDiscord'], 'discord', 'discord'],
    ['NotificationChannels\Twilio\TwilioChannel', 'twilio', ['toTwilio'], 'twilio', 'sms'],
]);

it('guesses the Laravel convention for unknown channels', function () {
    $definition = (new ChannelRegistry)->for('App\Channels\WhatsAppChannel');

    expect($definition->key)->toBe('whats-app')
        ->and($definition->methods)->toBe(['toWhatsApp'])
        ->and($definition->route)->toBe('whatsApp')
        ->and($definition->preview)->toBe('json');
});

it('lets the app define or override a channel', function () {
    $registry = new ChannelRegistry([
        'App\Channels\WhatsAppChannel' => ['label' => 'WhatsApp', 'method' => 'toWhatsApp', 'route' => 'whatsapp', 'preview' => 'sms'],
        'slack' => ['preview' => 'json'],
    ]);

    expect($registry->for('App\Channels\WhatsAppChannel')->preview)->toBe('sms')
        ->and($registry->for('App\Channels\WhatsAppChannel')->label)->toBe('WhatsApp')
        ->and($registry->for('slack')->preview)->toBe('json')
        ->and($registry->isKnown('App\Channels\WhatsAppChannel'))->toBeTrue();
});

it('shortens channel names', function (string $channel, string $short) {
    expect(ChannelRegistry::shortName($channel))->toBe($short);
})->with([
    ['slack', 'slack'],
    ['NotificationChannels\Telegram\TelegramChannel', 'telegram'],
    ['App\Channel', 'channel'],
    ['fcm-topic', 'fcm-topic'],
]);
