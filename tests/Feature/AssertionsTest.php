<?php

use AmjadIqbal\NotificationInbox\Facades\NotificationInbox;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\BrokenNotification;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\OrderShipped;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\User;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(fn () => NotificationInbox::fake());

it('works in the testing environment through fake(), with nothing on disk', function () {
    Notification::route('telegram', '1')->notify(new OrderShipped);

    NotificationInbox::assertCaptured(OrderShipped::class);
    expect(is_dir($this->inboxPath))->toBeFalse();
});

it('asserts what was captured, how often, where and for whom', function () {
    $user = new User(['id' => 5]);
    $user->notifyNow(new OrderShipped(['telegram', 'vonage']));

    NotificationInbox::assertCaptured(OrderShipped::class)
        ->assertCaptured(OrderShipped::class, 2)
        ->assertCapturedTimes(OrderShipped::class, 2)
        ->assertCaptured(OrderShipped::class, fn ($n) => $n->isOn('vonage') && str_contains($n->body(), '482913'))
        ->assertCapturedOn('telegram', fn ($n) => $n->route === '123456')
        ->assertNotCapturedOn('slack')
        ->assertCapturedFor($user)
        ->assertCapturedFor('+15550100')
        ->assertCapturedContaining('Order A-1 shipped', 'telegram')
        ->assertCapturedCount(2)
        ->assertNotCaptured(BrokenNotification::class)
        ->assertNoCaptureErrors();

    expect(NotificationInbox::first(OrderShipped::class)->channelKey)->toBe('telegram')
        ->and(NotificationInbox::captured(fn ($n) => $n->isOn('vonage'))->first()->payload['content'])->toBe('Your code is 482913 for order A-1')
        ->and(NotificationInbox::captured(BaseNotification::class))->toHaveCount(2);
});

it('asserts nothing was captured', function () {
    NotificationInbox::assertNothingCaptured();

    Notification::route('telegram', '1')->notify(new OrderShipped);

    expect(fn () => NotificationInbox::assertNothingCaptured())
        ->toThrow(AssertionFailedError::class, 'OrderShipped via Telegram: Order A-1 shipped');
});

it('fails with a list of what was captured', function () {
    Notification::route('telegram', '1')->notify(new OrderShipped);

    expect(fn () => NotificationInbox::assertCapturedOn('slack'))->toThrow(AssertionFailedError::class, 'Captured:')
        ->and(fn () => NotificationInbox::assertCaptured(BrokenNotification::class))->toThrow(AssertionFailedError::class)
        ->and(fn () => NotificationInbox::assertCaptured(OrderShipped::class, 3))->toThrow(AssertionFailedError::class, 'captured 1 time(s)')
        ->and(fn () => NotificationInbox::assertCapturedContaining('nope'))->toThrow(AssertionFailedError::class)
        ->and(fn () => NotificationInbox::assertNotCaptured(OrderShipped::class))->toThrow(AssertionFailedError::class);
});

it('fails assertNoCaptureErrors when a toX() method throws', function () {
    Notification::route('telegram', '1')->notify(new BrokenNotification);

    expect(fn () => NotificationInbox::assertNoCaptureErrors())
        ->toThrow(AssertionFailedError::class, 'BrokenNotification via Telegram: RuntimeException: chat id missing');
});
