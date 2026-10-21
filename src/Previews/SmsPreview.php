<?php

namespace AmjadIqbal\NotificationInbox\Previews;

use AmjadIqbal\NotificationInbox\CapturedNotification;

/**
 * Twilio, Vonage and other SMS channels as a phone conversation, with the
 * encoding and segment count the carrier will bill for.
 */
final class SmsPreview implements Preview
{
    /** GSM 03.38 basic set; anything outside it forces UCS-2. */
    private const GSM = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /** Extension set: each costs two characters. */
    private const GSM_EXTENDED = '^{}\\[~]|€';

    public function normalize(CapturedNotification $notification): array
    {
        $p = $notification->payload;
        $text = is_string($p) ? $p : (Payload::firstString($p, ['content', 'body', 'text', 'message', 'msg']) ?? '');

        return [
            'title' => null,
            'text' => $text,
            'from' => Payload::firstString($p, ['from', 'sender', 'originator', 'alphaNumSender', 'messagingServiceSid']),
            'to' => is_scalar($notification->route) ? (string) $notification->route : null,
            'media' => Payload::firstString($p, ['mediaUrl', 'media_url', 'mediaUrls.0']),
            ...self::segments($text),
            'buttons' => [],
        ];
    }

    /**
     * @return array{encoding: string, characters: int, segments: int}
     */
    public static function segments(string $text): array
    {
        $gsm = true;
        $length = 0;

        foreach (mb_str_split($text) as $char) {
            if (str_contains(self::GSM_EXTENDED, $char)) {
                $length += 2;
            } elseif (str_contains(self::GSM, $char)) {
                $length++;
            } else {
                $gsm = false;
                break;
            }
        }

        if (! $gsm) {
            // UCS-2 counts UTF-16 code units: emoji take two.
            $length = intdiv(strlen((string) mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')), 2);

            return ['encoding' => 'UCS-2', 'characters' => $length, 'segments' => $length <= 70 ? 1 : (int) ceil($length / 67)];
        }

        return ['encoding' => 'GSM-7', 'characters' => $length, 'segments' => $length <= 160 ? 1 : (int) ceil($length / 153)];
    }

    public function view(): string
    {
        return 'notification-inbox::previews.sms';
    }
}
