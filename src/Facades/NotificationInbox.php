<?php

namespace AmjadIqbal\NotificationInbox\Facades;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\NotificationInbox as Inbox;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Inbox fake()
 * @method static bool isActive()
 * @method static bool isFaked()
 * @method static list<CapturedNotification> all(?string $after = null)
 * @method static CapturedNotification|null find(string $id)
 * @method static int clear()
 * @method static int prune(?int $hours = null)
 * @method static Collection<int, CapturedNotification> captured(string|Closure|null $notification = null, ?Closure $callback = null)
 * @method static CapturedNotification|null first(string|Closure|null $notification = null, ?Closure $callback = null)
 * @method static Inbox assertCaptured(string|Closure $notification, Closure|int|null $callback = null)
 * @method static Inbox assertCapturedTimes(string|Closure $notification, int $times)
 * @method static Inbox assertNotCaptured(string|Closure $notification, ?Closure $callback = null)
 * @method static Inbox assertNothingCaptured()
 * @method static Inbox assertCapturedCount(int $count)
 * @method static Inbox assertCapturedOn(string $channel, ?Closure $callback = null)
 * @method static Inbox assertNotCapturedOn(string $channel, ?Closure $callback = null)
 * @method static Inbox assertCapturedFor(mixed $notifiable, ?Closure $callback = null)
 * @method static Inbox assertCapturedContaining(string $text, ?string $channel = null)
 * @method static Inbox assertNoCaptureErrors()
 *
 * @see Inbox
 */
final class NotificationInbox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Inbox::class;
    }
}
