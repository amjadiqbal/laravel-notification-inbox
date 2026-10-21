<?php

namespace AmjadIqbal\NotificationInbox\Testing;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Assertions against what a channel would really have sent. Unlike
 * Notification::fake(), the toX() methods run, so a broken payload fails here.
 *
 * Every assertion takes an optional callback that receives a CapturedNotification.
 */
trait AssertsCapturedNotifications
{
    /**
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     * @return Collection<int, CapturedNotification>
     */
    public function captured(string|Closure|null $notification = null, ?Closure $callback = null): Collection
    {
        if ($notification instanceof Closure) {
            [$notification, $callback] = [null, $notification];
        }

        return collect($this->all())
            ->reverse()
            ->values()
            ->filter(fn (CapturedNotification $item): bool => ($notification === null || $item->is($notification))
                && ($callback === null || $callback($item)))
            ->values();
    }

    /**
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     */
    public function first(string|Closure|null $notification = null, ?Closure $callback = null): ?CapturedNotification
    {
        return $this->captured($notification, $callback)->first();
    }

    /**
     * @param  (Closure(CapturedNotification): bool)|int|null  $callback  a filter, or the expected count
     */
    public function assertCaptured(string|Closure $notification, Closure|int|null $callback = null): static
    {
        if (is_int($callback)) {
            return $this->assertCapturedTimes($notification, $callback);
        }

        $label = is_string($notification) ? $notification : 'a matching notification';

        PHPUnit::assertTrue(
            $this->captured($notification, $callback)->isNotEmpty(),
            "Expected [{$label}] to be captured, but it was not.".$this->capturedSummary(),
        );

        return $this;
    }

    public function assertCapturedTimes(string|Closure $notification, int $times): static
    {
        $count = $this->captured($notification)->count();
        $label = is_string($notification) ? $notification : 'a matching notification';

        PHPUnit::assertSame($times, $count, "Expected [{$label}] to be captured {$times} time(s), but it was captured {$count} time(s).");

        return $this;
    }

    /**
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     */
    public function assertNotCaptured(string|Closure $notification, ?Closure $callback = null): static
    {
        $label = is_string($notification) ? $notification : 'a matching notification';

        PHPUnit::assertTrue($this->captured($notification, $callback)->isEmpty(), "Unexpected [{$label}] was captured.");

        return $this;
    }

    public function assertNothingCaptured(): static
    {
        PHPUnit::assertSame([], $this->all(), 'Expected no notifications to be captured.'.$this->capturedSummary());

        return $this;
    }

    public function assertCapturedCount(int $count): static
    {
        $actual = count($this->all());

        PHPUnit::assertSame($count, $actual, "Expected {$count} captured notification(s), found {$actual}.");

        return $this;
    }

    /**
     * On a channel: 'slack', 'telegram', or a channel class such as TwilioChannel::class.
     *
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     */
    public function assertCapturedOn(string $channel, ?Closure $callback = null): static
    {
        PHPUnit::assertTrue(
            $this->captured(fn (CapturedNotification $item): bool => $item->isOn($channel) && ($callback === null || $callback($item)))->isNotEmpty(),
            "Expected a notification to be captured on [{$channel}], but none was.".$this->capturedSummary(),
        );

        return $this;
    }

    /**
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     */
    public function assertNotCapturedOn(string $channel, ?Closure $callback = null): static
    {
        PHPUnit::assertTrue(
            $this->captured(fn (CapturedNotification $item): bool => $item->isOn($channel) && ($callback === null || $callback($item)))->isEmpty(),
            "Unexpected notification was captured on [{$channel}].",
        );

        return $this;
    }

    /**
     * For a notifiable model, or a route such as a phone number or chat id.
     *
     * @param  (Closure(CapturedNotification): bool)|null  $callback
     */
    public function assertCapturedFor(mixed $notifiable, ?Closure $callback = null): static
    {
        PHPUnit::assertTrue(
            $this->captured(fn (CapturedNotification $item): bool => $item->isFor($notifiable) && ($callback === null || $callback($item)))->isNotEmpty(),
            'Expected a notification to be captured for the given notifiable, but none was.'.$this->capturedSummary(),
        );

        return $this;
    }

    /**
     * Title or body of a captured message contains the text.
     */
    public function assertCapturedContaining(string $text, ?string $channel = null): static
    {
        PHPUnit::assertTrue(
            $this->captured(fn (CapturedNotification $item): bool => ($channel === null || $item->isOn($channel)) && str_contains($item->text(), $text))->isNotEmpty(),
            "Expected a captured notification containing [{$text}]".($channel ? " on [{$channel}]" : '').'.'.$this->capturedSummary(),
        );

        return $this;
    }

    /**
     * Every captured message was built without an exception.
     */
    public function assertNoCaptureErrors(): static
    {
        $errors = $this->captured(fn (CapturedNotification $item): bool => $item->error !== null)
            ->map(fn (CapturedNotification $item): string => class_basename($item->notification).' via '.$item->channelLabel.': '.$item->error);

        PHPUnit::assertTrue($errors->isEmpty(), "Some notifications could not be built:\n".$errors->implode("\n"));

        return $this;
    }

    private function capturedSummary(): string
    {
        $items = $this->captured();

        if ($items->isEmpty()) {
            return ' Nothing was captured.';
        }

        return "\nCaptured:\n".$items
            ->map(fn (CapturedNotification $item): string => '  - '.class_basename($item->notification).' via '.$item->channelLabel.': '.$item->summary())
            ->implode("\n");
    }
}
