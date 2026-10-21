<?php

use AmjadIqbal\NotificationInbox\Support\Redactor;
use Illuminate\Notifications\Notification;

it('masks configured keys at any depth, case-insensitively', function () {
    $r = new Redactor(['password', 'auth', 'webhook_url']);

    expect($r->payload(['body' => 'hi', 'keys' => ['Auth' => 'abc', 'p' => 'k'], 'password' => '', 'Webhook_URL' => 'https://x']))
        ->toBe(['body' => 'hi', 'keys' => ['Auth' => '[redacted]', 'p' => 'k'], 'password' => '', 'Webhook_URL' => '[redacted]']);
});

it('keeps only the host of webhook routes', function () {
    $r = new Redactor([]);

    expect($r->route('https://hooks.slack.com/services/T/B/SECRET'))->toBe('https://hooks.slack.com/…')
        ->and($r->route(['https://discord.com/api/webhooks/1/abc', '+15550100']))->toBe(['https://discord.com/…', '+15550100'])
        ->and($r->route('https://example.test'))->toBe('https://example.test')
        ->and($r->route('123456'))->toBe('123456');
});

it('masks keys that contain a secret word', function () {
    $r = new Redactor(['auth'], ['token', 'otp', 'code', 'secret', 'password']);

    expect($r->payload(['reset_token' => 'abc', 'OTP_Code' => '1234', 'client_secret' => 's', 'author' => 'Sam', 'auth' => 'x', 'body' => 'hi']))
        ->toBe(['reset_token' => '[redacted]', 'OTP_Code' => '[redacted]', 'client_secret' => '[redacted]', 'author' => 'Sam', 'auth' => '[redacted]', 'body' => 'hi']);
});

it('masks one-time codes in text only when asked, never phone numbers or routes', function () {
    $off = new Redactor([]);
    $on = new Redactor([], [], maskCodes: true);
    $text = 'Your code is 482913. Call +447700900123 or 020-7946-0018 about order 2026.';

    expect($off->payload(['content' => $text])['content'])->toBe($text)
        ->and($on->payload(['content' => $text, 'n' => 482913])['content'])->toBe('Your code is ••••••. Call +447700900123 or 020-7946-0018 about order ••••••.')
        ->and($on->payload(['n' => 482913])['n'])->toBe(482913)
        ->and($on->route(['424242', '+15550100']))->toBe(['424242', '+15550100'])
        ->and($on->route('424242'))->toBe('424242');
});

it('uses the shipped defaults on disk', function () {
    $this->inboxIn('local');

    Illuminate\Support\Facades\Notification::route('vonage', '+15550100')->notify(new class extends Notification
    {
        public function via(object $n): array
        {
            return ['vonage'];
        }

        public function toVonage(object $n): array
        {
            return ['content' => 'Code 482913', 'verification_code' => '482913', 'reset_token' => 't0k', 'author' => 'Sam'];
        }
    });

    $stored = json_decode(file_get_contents(glob($this->inboxPath.'/*.json')[0]), true)['payload'];
    expect($stored)->toBe(['content' => 'Code 482913', 'verification_code' => '[redacted]', 'reset_token' => '[redacted]', 'author' => 'Sam']);
});
