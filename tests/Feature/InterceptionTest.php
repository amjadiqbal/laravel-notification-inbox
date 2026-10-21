<?php

use AmjadIqbal\NotificationInbox\NotificationInbox;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\BrokenNotification;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\OrderShipped;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\QueuedOrderShipped;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\User;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('catches notifications in local instead of sending them', function () {
    $inbox = $this->inboxIn('local');
    $sent = [];
    Event::listen(NotificationSent::class, function ($e) use (&$sent) {
        $sent[] = $e->channel;
    });

    Notification::route('telegram', '998877')->notify(new OrderShipped(['telegram', 'slack']));

    expect($sent)->toBe([])
        ->and($inbox->all())->toHaveCount(2)
        ->and($inbox->all()[0]->channel)->toBe('slack')
        ->and($inbox->all()[1]->route)->toBe('998877')
        ->and($inbox->all()[1]->payload)->toBe(['text' => '*Order A-1 shipped*', 'parse_mode' => 'Markdown']);
});

it('stores one file per captured notification', function () {
    $this->inboxIn('local');

    Notification::route('telegram', '1')->notify(new OrderShipped);

    expect(glob($this->inboxPath.'/*.json'))->toHaveCount(1)
        ->and(file_get_contents($this->inboxPath.'/.gitignore'))->toContain('*');
});

it('never intercepts in production by default, even when production is listed', function (array $config) {
    $inbox = $this->inboxIn('production', $config);

    expect($inbox->isActive())->toBeFalse();

    // The real channel runs: there is no "telegram" driver in this app, so it throws.
    expect(fn () => Notification::route('telegram', '1')->notify(new OrderShipped))
        ->toThrow(InvalidArgumentException::class);
    expect($inbox->all())->toBe([]);
})->with([
    'default config' => [[]],
    'production listed only' => [['environments' => ['local', 'production']]],
    'allow flag without listing' => [['allow_production' => true]],
]);

it('intercepts production only with both the allow-list and the override', function () {
    $inbox = $this->inboxIn('production', ['environments' => ['production'], 'allow_production' => true]);

    Notification::route('telegram', '1')->notify(new OrderShipped);

    expect($inbox->isActive())->toBeTrue()
        ->and($inbox->isInterceptingProduction())->toBeTrue()
        ->and($inbox->all())->toHaveCount(1);
});

it('reports production listed without the override as ignored', function () {
    $inbox = $this->inboxIn('production', ['environments' => ['production']]);

    expect($inbox->productionListedButIgnored())->toBeTrue()
        ->and($inbox->isInterceptingProduction())->toBeFalse();
});

it('does nothing outside the allow-list or when disabled', function (string $env, array $config) {
    expect($this->inboxIn($env, $config)->isActive())->toBeFalse();
})->with([
    'staging not listed' => ['staging', []],
    'testing not listed' => ['testing', []],
    'disabled in local' => ['local', ['enabled' => false]],
]);

it('catches staging once it is listed', function () {
    expect($this->inboxIn('staging', ['environments' => ['local', 'staging']])->isActive())->toBeTrue();
});

it('passes mail and database through to the real channels', function () {
    $inbox = $this->inboxIn('local');

    // toMail() throws in the fixture, which proves the real mail channel ran.
    expect(fn () => Notification::route('mail', 'a@example.test')->notify(new OrderShipped(['mail'])))
        ->toThrow(LogicException::class, 'passed through');
    expect($inbox->all())->toBe([]);
});

it('only intercepts the listed channels', function () {
    $inbox = $this->inboxIn('local', ['intercept' => ['slack']]);

    expect($inbox->intercepts('slack'))->toBeTrue()
        ->and($inbox->intercepts('telegram'))->toBeFalse()
        ->and($inbox->intercepts('NotificationChannels\Telegram\TelegramChannel'))->toBeFalse();
});

it('matches channel classes by class name and by short name', function () {
    $inbox = $this->inboxIn('local', ['intercept' => ['telegram'], 'pass_through' => ['twilio']]);

    expect($inbox->intercepts('NotificationChannels\Telegram\TelegramChannel'))->toBeTrue()
        ->and($inbox->intercepts('NotificationChannels\Twilio\TwilioChannel'))->toBeFalse();
});

it('lets other NotificationSending listeners run when it passes a channel through', function () {
    $this->inboxIn('local', ['intercept' => ['slack']]);
    $seen = [];
    Event::listen(NotificationSending::class, function (NotificationSending $e) use (&$seen) {
        $seen[] = $e->channel;

        return false;
    });

    Notification::route('telegram', '1')->notify(new OrderShipped(['telegram']));

    expect($seen)->toBe(['telegram']);
});

it('records a toX() exception instead of sending, and keeps going with the other channels', function () {
    $inbox = $this->inboxIn('local');

    Notification::route('telegram', '1')->notify(new BrokenNotification);

    $items = $inbox->all();
    expect($items)->toHaveCount(2)
        ->and($items[1]->error)->toBe('RuntimeException: chat id missing')
        ->and($items[0]->error)->toContain('has no toDiscord() method')
        ->and($items[0]->summary())->toBe('Could not build the message');
});

it('builds the real broadcast event: channel, event name and data', function () {
    $inbox = $this->inboxIn('local');
    $user = new User(['id' => 7]);

    $user->notifyNow(new OrderShipped(['broadcast']));

    $item = $inbox->all()[0];
    expect($item->route)->toBe(['private-AmjadIqbal.NotificationInbox.Tests.Fixtures.User.7'])
        ->and($item->meta['event'])->toBe('Illuminate\Notifications\Events\BroadcastNotificationCreated')
        ->and($item->payload['order'])->toBe('A-1')
        ->and($item->payload['type'])->toBe(OrderShipped::class)
        ->and($item->notifiable)->toBe(['type' => User::class, 'key' => 7, 'label' => 'User #7']);
});

it('uses routeNotificationFor on models and never stores their attributes', function () {
    $inbox = $this->inboxIn('local');
    $user = new User(['id' => 3, 'email' => 'private@example.test']);

    $user->notifyNow(new OrderShipped(['telegram', 'vonage']));

    expect($inbox->all()[1]->route)->toBe('123456')
        ->and($inbox->all()[0]->route)->toBe('+15550100')
        ->and(json_encode(array_map(fn ($i) => $i->toArray(), $inbox->all())))->not->toContain('private@example.test');
});

it('reads routes only the way the real channel does', function () {
    $inbox = $this->inboxIn('local');
    $teams = 'NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel';
    $twilio = 'NotificationChannels\Twilio\TwilioChannel';

    // Teams only reads routeNotificationFor('microsoftTeams'), so a route keyed by the class is not found,
    // exactly as the real channel would not find it. Twilio tries its class first, then 'twilio'.
    Notification::route($teams, 'https://example.test/hook')->route($twilio, '+15550111')
        ->notify(new class([$teams, $twilio]) extends OrderShipped
        {
            public function toMicrosoftTeams(): array
            {
                return ['text' => 'x'];
            }

            public function toTwilio(): array
            {
                return ['content' => 'y'];
            }
        });

    expect($inbox->all()[1]->route)->toBeNull()
        ->and($inbox->all()[0]->route)->toBe('+15550111');
});

it('marks queued notifications and groups channels by notification id', function () {
    $inbox = $this->inboxIn('local');
    $notification = new QueuedOrderShipped(['telegram', 'slack']);
    $notification->id = 'fixed-id';

    Notification::route('telegram', '1')->notifyNow($notification);

    expect(collect($inbox->all())->pluck('queued')->all())->toBe([true, true])
        ->and(collect($inbox->all())->pluck('notificationId')->unique()->all())->toBe(['fixed-id']);
});

it('redacts secrets and webhook paths on disk but not in a fake', function () {
    $inbox = $this->inboxIn('local');

    Notification::route('vonage', 'https://hooks.example.test/services/T0/B0/SECRET')->notify(new OrderShipped(['vonage']));

    $raw = file_get_contents(glob($this->inboxPath.'/*.json')[0]);
    expect($raw)->not->toContain('SECRET')
        ->and($raw)->toContain('https://hooks.example.test/…')
        ->and($raw)->not->toContain('live_123')
        ->and($inbox->all()[0]->payload)->toBe(['content' => 'Your code is 482913 for order A-1', 'from' => 'SHOP', 'secretHeader' => 'x']);
});

it('prunes by age and by count', function () {
    $inbox = $this->inboxIn('local', ['max_messages' => 2]);

    foreach (range(1, 4) as $i) {
        Notification::route('telegram', (string) $i)->notify(new OrderShipped(order: "N{$i}"));
    }

    expect($inbox->prune())->toBe(2)
        ->and(collect($inbox->all())->map->body()->all())->toBe(['Order N4 shipped', 'Order N3 shipped']);

    touch(glob($this->inboxPath.'/*.json')[0], time() - 7200);
    expect($inbox->prune(1))->toBe(1)->and($inbox->all())->toHaveCount(1);
});

it('is resolved once per app and swapped by fake()', function () {
    $inbox = app(NotificationInbox::class);

    expect(app(NotificationInbox::class))->toBe($inbox)
        ->and($inbox->fake()->isFaked())->toBeTrue()
        ->and($inbox->isActive())->toBeTrue();
});
