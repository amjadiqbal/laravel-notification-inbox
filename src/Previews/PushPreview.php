<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;

/**
 * Web push, FCM, APNs, Expo, OneSignal and Pushover drawn as a phone lock-screen alert.
 */
final class PushPreview implements Preview
{
    private const PLATFORMS = [
        'web-push' => 'Web push',
        'fcm' => 'Android / iOS via FCM',
        'fcm-topic' => 'FCM topic',
        'apn' => 'iOS via APNs',
        'apn-voip' => 'iOS VoIP via APNs',
        'expo' => 'Expo',
        'one-signal' => 'OneSignal',
        'pushover' => 'Pushover',
    ];

    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;

        $title = Payload::firstString($p, [
            'title', 'notification.title', 'aps.alert.title', 'android.notification.title',
            'apns.payload.aps.alert.title', 'webpush.notification.title', 'headings.en', 'heading',
        ]);
        $body = Payload::firstString($p, [
            'body', 'notification.body', 'aps.alert.body', 'android.notification.body',
            'apns.payload.aps.alert.body', 'webpush.notification.body', 'contents.en', 'message', 'content', 'text',
        ]);

        $buttons = [];
        foreach (array_merge(Payload::array($p, 'actions'), Payload::array($p, 'buttons')) as $action) {
            $label = Payload::firstString($action, ['title', 'text', 'label']);
            if ($label !== null) {
                $buttons[] = ['label' => $label, 'url' => Payload::firstString($action, ['url', 'action_url'])];
            }
        }

        $badge = data_get($p, 'badge') ?? data_get($p, 'aps.badge') ?? data_get($p, 'apns.payload.aps.badge');

        return [
            'title' => $title,
            'subtitle' => Payload::firstString($p, ['subtitle', 'aps.alert.subtitle', 'apns.payload.aps.alert.subtitle']),
            'text' => $body ?? '',
            'image' => Payload::firstString($p, ['image', 'notification.image', 'android.notification.image', 'richContent.image', 'big_picture', 'url_image']),
            'icon' => Payload::firstString($p, ['icon', 'android.notification.icon', 'small_icon']),
            'badge' => is_int($badge) ? $badge : null,
            'platform' => self::PLATFORMS[$notification->channelKey] ?? $notification->channelLabel,
            'data' => data_get($p, 'data'),
            'buttons' => $buttons,
        ];
    }

    public function view(): string
    {
        return 'notification-inbox::previews.push';
    }
}
