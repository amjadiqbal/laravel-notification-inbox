<?php

namespace AmjadIqbal\NotificationInbox\Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(public array $channels = ['telegram'], public string $order = 'A-1') {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toTelegram(object $notifiable): array
    {
        return ['text' => "*Order {$this->order} shipped*", 'parse_mode' => 'Markdown'];
    }

    public function toVonage(object $notifiable): SmsMessage
    {
        return new SmsMessage("Your code is 482913 for order {$this->order}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toSlack(object $notifiable): array
    {
        return ['text' => "Order {$this->order}", 'blocks' => [['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => "Order {$this->order} shipped"]]]];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(['order' => $this->order]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['order' => $this->order];
    }

    public function toMail(object $notifiable): never
    {
        throw new \LogicException('mail is passed through, so toMail() should only run in the real mail channel');
    }
}

class QueuedOrderShipped extends OrderShipped implements ShouldQueue {}

class BrokenNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['telegram', 'discord'];
    }

    public function toTelegram(object $notifiable): never
    {
        throw new \RuntimeException('chat id missing');
    }
}

final class SmsMessage
{
    /** @var object a transport some message classes carry; it must never be serialized */
    public object $client;

    public function __construct(public string $content, protected string $from = 'SHOP', private string $secretHeader = 'x')
    {
        $this->client = new \ArrayObject(['api_key' => 'live_123']);
    }
}

class User extends Model
{
    use Notifiable;

    protected $guarded = [];

    public $timestamps = false;

    public function routeNotificationForTelegram(): string
    {
        return '123456';
    }

    public function routeNotificationForVonage(): string
    {
        return '+15550100';
    }
}
