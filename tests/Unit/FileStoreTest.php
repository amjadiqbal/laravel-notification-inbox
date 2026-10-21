<?php

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\Storage\FileStore;
use AmjadIqbal\NotificationInbox\Support\Redactor;
use AmjadIqbal\NotificationInbox\Tests\TestCase;
use Illuminate\Support\Str;

function storedCopy(string $channel, array $override = []): CapturedNotification
{
    return CapturedNotification::fromArray(array_merge(TestCase::fixture($channel)->toArray(), ['id' => (string) Str::ulid()], $override));
}

it('round-trips, lists newest first and pages with after', function () {
    $store = new FileStore($this->inboxPath, new Redactor([]));
    $first = storedCopy('slack');
    $second = storedCopy('telegram');
    $store->put($first);
    $store->put($second);

    expect(array_map(fn ($i) => $i->id, $store->all()))->toBe([$second->id, $first->id])
        ->and($store->all($first->id))->toHaveCount(1)
        ->and($store->all($second->id))->toBe([])
        ->and($store->find($first->id)->toArray())->toBe($first->toArray());
});

it('ignores ids that are not ULIDs, so paths cannot escape the folder', function () {
    $store = new FileStore($this->inboxPath, new Redactor([]));

    expect($store->find('../../etc/passwd'))->toBeNull()
        ->and($store->delete('../x'))->toBeFalse();
});

it('skips files it cannot read', function () {
    $store = new FileStore($this->inboxPath, new Redactor([]));
    $store->put(storedCopy('slack'));
    file_put_contents($this->inboxPath.'/'.Str::ulid().'.json', '{broken');

    expect($store->all())->toHaveCount(1);
});

it('clears and prunes', function () {
    $store = new FileStore($this->inboxPath, new Redactor([]));
    foreach (range(1, 3) as $i) {
        $store->put(storedCopy('slack'));
    }

    expect($store->prune(now()->subHour(), 2))->toBe(1)
        ->and($store->clear())->toBe(2)
        ->and($store->all())->toBe([]);
});
