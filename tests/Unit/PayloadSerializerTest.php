<?php

use AmjadIqbal\NotificationInbox\Support\PayloadSerializer;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\SmsMessage;
use AmjadIqbal\NotificationInbox\Tests\Fixtures\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

enum Priority: string
{
    case High = 'high';
}
enum Kind
{
    case Silent;
}

it('uses toArray and jsonSerialize like the channels do', function () {
    $s = new PayloadSerializer;

    $withToArray = new class
    {
        public $ignored = 'x';

        public function toArray(): array
        {
            return ['a' => 1];
        }
    };
    $json = new class implements JsonSerializable
    {
        public function jsonSerialize(): mixed
        {
            return ['b' => 2];
        }
    };

    expect($s->serialize($withToArray))->toBe(['a' => 1])
        ->and($s->serialize($json))->toBe(['b' => 2]);
});

it('reads all properties of plain message objects but never transports', function () {
    expect((new PayloadSerializer)->serialize(new SmsMessage('hi')))
        ->toBe(['content' => 'hi', 'from' => 'SHOP', 'secretHeader' => 'x']);
});

it('reduces models to type and key, and converts enums, dates and html', function () {
    $s = new PayloadSerializer;

    expect($s->serialize(new User(['id' => 9, 'email' => 'x@example.test'])))->toBe(['model' => User::class, 'key' => 9])
        ->and($s->serialize(Priority::High))->toBe('high')
        ->and($s->serialize(Kind::Silent))->toBe('Silent')
        ->and($s->serialize(CarbonImmutable::parse('2026-10-21 10:00:00', 'UTC')))->toBe('2026-10-21T10:00:00+00:00')
        ->and($s->serialize(new HtmlString('<b>x</b>')))->toBe('<b>x</b>')
        ->and($s->serialize(fn () => 1))->toBe('[closure]')
        ->and($s->serialize(collect([1, 2])))->toBe([1, 2]);
});

it('falls back to properties when toArray needs arguments or throws', function () {
    $needsArgs = new class
    {
        public $title = 't';

        public function toArray($x): array
        {
            return [];
        }
    };
    $throws = new class
    {
        public $title = 'u';

        public function toArray(): array
        {
            throw new RuntimeException;
        }
    };

    expect((new PayloadSerializer)->serialize($needsArgs))->toBe(['title' => 't'])
        ->and((new PayloadSerializer)->serialize($throws))->toBe(['title' => 'u']);
});

it('stops at a sane depth on self-referencing objects', function () {
    $a = new stdClass;
    $a->self = $a;

    expect(json_encode((new PayloadSerializer)->serialize($a)))->toContain('[too deep]');
});
