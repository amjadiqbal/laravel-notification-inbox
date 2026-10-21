<?php

use AmjadIqbal\NotificationInbox\Support\Redactor;

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
