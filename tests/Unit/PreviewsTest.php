<?php

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Previews\SmsPreview;

it('normalizes every real channel payload', function (string $channel, ?string $title, string $text, array $buttons) {
    $n = self::fixture($channel);

    expect($n->title())->toBe($title)
        ->and($n->body())->toBe($text)
        ->and(array_column($n->buttons(), 'label'))->toBe($buttons);
})->with([
    'slack' => ['slack', 'Order A-1042 shipped', "Customer #1287 · Express\nThe parcel left the Leeds warehouse. Tracking: JD0002251\nCarrier:\nDHL\nETA:\nThursday", ['Track parcel', 'Refund']],
    'vonage' => ['vonage', null, 'Your order A-1042 has shipped. Track: https://example.test/t/JD0002251', []],
    'web push' => ['web-push', 'Order A-1042 shipped', 'Arriving Thursday. Tap to track your parcel.', ['Track']],
    'fcm' => ['fcm', 'Order A-1042 shipped', 'Arriving Thursday.', []],
    'apn' => ['apn', 'Order A-1042 shipped', 'Arriving Thursday.', []],
    'expo' => ['expo', 'Order A-1042 shipped', 'Arriving Thursday.', []],
    'telegram' => ['telegram', null, "Order A-1042 shipped\nArriving Thursday. Tracking JD0002251.", ['Track parcel', 'Help']],
    'teams' => ['microsoft-teams', 'Order A-1042 shipped', "The parcel left the Leeds warehouse.\nCarrier DHL\nETA Thursday", ['Track parcel']],
    'discord' => ['discord', 'Shipment update', "Order A-1042 shipped\nThe parcel left the Leeds warehouse.\nCarrier DHL\nETA Thursday", []],
    'twilio' => ['twilio', null, 'Your code is 482913. Order A-1042 is on its way.', []],
    'broadcast' => ['broadcast', 'OrderShipped', '{"order":"A-1042","status":"shipped","eta":"Thursday"}', []],
]);

it('reads push extras: subtitle, badge, image and platform', function () {
    expect(self::fixture('apn')->preview())->toMatchArray(['subtitle' => 'Express delivery', 'badge' => 3, 'platform' => 'iOS via APNs'])
        ->and(self::fixture('fcm')->preview()['image'])->toBe('https://picsum.photos/400/200')
        ->and(self::fixture('expo')->preview()['badge'])->toBe(2);
});

it('decodes the Telegram inline keyboard and Discord embed colour', function () {
    expect(self::fixture('telegram')->preview()['keyboard'])->toBe([[
        ['label' => 'Track parcel', 'url' => 'https://example.test/track/JD0002251'],
        ['label' => 'Help', 'url' => 'https://example.test/help'],
    ]])->and(self::fixture('discord')->preview()['embeds'][0]['color'])->toBe('#5865F2');
});

it('reads the legacy Slack message with attachments', function () {
    $n = CapturedNotification::fromArray(array_merge(self::fixture('slack')->toArray(), ['payload' => [
        'level' => 'success', 'content' => 'Deploy finished', 'attachments' => [[
            'title' => 'Release 2.4', 'url' => 'https://example.test/r', 'content' => 'All green',
            'fields' => ['Env' => 'staging'], 'color' => null, 'footer' => 'CI',
        ]],
    ]]));

    expect($n->title())->toBe('Release 2.4')
        ->and($n->body())->toBe("Deploy finished\nAll green")
        ->and($n->preview()['attachments'][0])->toMatchArray(['color' => '#2EB67D', 'fields' => [['title' => 'Env', 'value' => 'staging']]]);
});

it('reads a legacy Teams MessageCard', function () {
    $n = CapturedNotification::fromArray(array_merge(self::fixture('microsoft-teams')->toArray(), ['payload' => [
        '@type' => 'MessageCard', 'title' => 'Build failed', 'text' => '**main** is red',
        'potentialAction' => [['@type' => 'OpenUri', 'name' => 'Open', 'targets' => [['os' => 'default', 'uri' => 'https://ci.test']]]],
    ]]));

    expect($n->title())->toBe('Build failed')
        ->and($n->body())->toBe('main is red')
        ->and($n->buttons())->toBe([['label' => 'Open', 'url' => 'https://ci.test']]);
});

it('counts SMS segments like the carriers do', function (string $text, string $encoding, int $characters, int $segments) {
    expect(SmsPreview::segments($text))->toBe(['encoding' => $encoding, 'characters' => $characters, 'segments' => $segments]);
})->with([
    'short' => ['Your code is 1234', 'GSM-7', 17, 1],
    'exactly 160' => [str_repeat('a', 160), 'GSM-7', 160, 1],
    '161 splits' => [str_repeat('a', 161), 'GSM-7', 161, 2],
    'extended chars count double' => ['€10 [off]', 'GSM-7', 12, 1],
    'emoji forces UCS-2' => ['Hi 😀', 'UCS-2', 5, 1],
    '71 UCS-2 splits' => [str_repeat('é', 70).'ж', 'UCS-2', 71, 2],
]);

it('falls back to a JSON preview for unknown channels', function () {
    $n = CapturedNotification::fromArray(array_merge(self::fixture('discord')->toArray(), ['preview' => 'json', 'payload' => ['subject' => 'Hi', 'message' => 'There']]));

    expect($n->title())->toBe('Hi')->and($n->body())->toBe('There');
});
