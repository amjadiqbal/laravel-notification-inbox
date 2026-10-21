<?php

use AmjadIqbal\NotificationInbox\Tests\Fixtures\OrderShipped;
use Illuminate\Support\Facades\Notification;

it('prunes and clears from artisan', function () {
    $inbox = $this->inboxIn('local');
    Notification::route('telegram', '1')->notify(new OrderShipped(['telegram', 'slack']));

    touch(glob($this->inboxPath.'/*.json')[0], time() - 3 * 3600);

    $this->artisan('notification-inbox:prune', ['--hours' => 2])->expectsOutputToContain('Pruned 1')->assertSuccessful();
    $this->artisan('notification-inbox:prune', ['--hours' => 'x'])->assertFailed();
    $this->artisan('notification-inbox:clear')->expectsOutputToContain('Deleted 1')->assertSuccessful();

    expect($inbox->all())->toBe([]);
});

it('adds a passive line to artisan about', function () {
    $this->inboxIn('local');

    $this->artisan('about', ['--only' => 'notification inbox'])
        ->expectsOutputToContain('catching notifications (not sent; mail, database still sent)')
        ->expectsOutputToContain('amjad.com.pk/contact')
        ->assertSuccessful();
});
