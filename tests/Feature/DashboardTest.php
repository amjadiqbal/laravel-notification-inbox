<?php

use AmjadIqbal\NotificationInbox\CapturedNotification;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->inbox = $this->inboxIn('local');
    $this->refreshRoutes = function () {
        $this->app['router']->setRoutes(new RouteCollection);
        require __DIR__.'/../../routes/web.php';
        $this->app['router']->getRoutes()->refreshNameLookups();
    };
    ($this->refreshRoutes)();
});

function seedFixtures($test): array
{
    $ids = [];
    foreach (['slack', 'vonage', 'web-push', 'fcm', 'apn', 'expo', 'telegram', 'microsoft-teams', 'discord', 'twilio', 'broadcast'] as $channel) {
        $fixture = $test::fixture($channel)->toArray();
        $fixture['id'] = (string) Str::ulid();
        $test->inbox->store()->put(CapturedNotification::fromArray($fixture));
        $ids[$channel] = $fixture['id'];
    }

    return $ids;
}

it('shows an empty inbox', function () {
    $this->get('/notification-inbox')->assertOk()->assertSee('No notifications caught yet');
});

it('renders every channel preview from real payloads', function (string $channel, array $expected) {
    $ids = seedFixtures($this);

    $response = $this->get('/notification-inbox?id='.$ids[$channel])->assertOk();

    foreach ($expected as $text) {
        $response->assertSee($text, false);
    }
})->with([
    'slack' => ['slack', ['pv-slack', 'Order A-1042 shipped', '<strong>Leeds</strong>', '<code>JD0002251</code>', 'Track parcel']],
    'vonage' => ['vonage', ['pv-sms', 'SHOP', 'GSM-7', '+447700900123']],
    'web push' => ['web-push', ['pv-lock', 'Arriving Thursday. Tap to track your parcel.', 'Web push']],
    'fcm' => ['fcm', ['pv-alert-image', 'https://picsum.photos/400/200', 'Android / iOS via FCM']],
    'apn' => ['apn', ['Express delivery', 'App icon badge']],
    'expo' => ['expo', ['Expo', 'Arriving Thursday.']],
    'telegram' => ['telegram', ['pv-tg', '<strong>Order A-1042 shipped</strong>', '<em>Thursday</em>', 'Track parcel']],
    'teams' => ['microsoft-teams', ['pv-teams', 'Carrier', 'Adaptive Card', 'Track parcel']],
    'discord' => ['discord', ['pv-dc', 'Shipment update', 'border-left-color: #5865F2', 'Shop bot']],
    'twilio' => ['twilio', ['482913', 'channel default']],
    'broadcast' => ['broadcast', ['private-App.Models.User.1', 'BroadcastNotificationCreated', "Echo.private('App.Models.User.1')"]],
]);

it('filters by channel and search', function () {
    seedFixtures($this);

    $this->get('/notification-inbox?channel=telegram')->assertOk()->assertSee('Telegram')->assertDontSee('Your code is 482913');
    $this->get('/notification-inbox?q=482913')->assertOk()->assertSee('Your code is 482913');
});

it('serves new messages as JSON for the live list', function () {
    $ids = seedFixtures($this);

    $this->getJson('/notification-inbox/api/messages')->assertOk()->assertJsonCount(11, 'messages')->assertJsonPath('total', 11);
    $this->getJson('/notification-inbox/api/messages?after='.$ids['discord'])->assertOk()
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.0.channel', 'broadcast');
});

it('deletes one and clears all', function () {
    // The app runs as "local" here, so the CSRF check is on: send a token like the forms do.
    $ids = seedFixtures($this);
    $this->withSession(['_token' => 'test-token']);

    $this->deleteJson('/notification-inbox/messages/'.$ids['slack'], [], ['X-CSRF-TOKEN' => 'test-token'])->assertOk()->assertJson(['deleted' => true]);
    expect($this->inbox->find($ids['slack']))->toBeNull();

    $this->delete('/notification-inbox/messages', ['_token' => 'wrong'])->assertStatus(419);
    $this->delete('/notification-inbox/messages', ['_token' => 'test-token'])->assertRedirect('/notification-inbox');
    expect($this->inbox->all())->toBe([]);
});

it('escapes payload content', function () {
    $fixture = self::fixture('twilio')->toArray();
    $fixture['payload']['content'] = '<script>alert(1)</script>';
    $this->inbox->store()->put(CapturedNotification::fromArray($fixture));

    $this->get('/notification-inbox')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('is forbidden outside local unless the app defines the gate', function () {
    $this->inboxIn('staging', ['environments' => ['staging']]);
    ($this->refreshRoutes)();

    $this->get('/notification-inbox')->assertForbidden();

    Gate::define('viewNotificationInbox', fn ($user = null) => true);
    $this->get('/notification-inbox')->assertOk();
});

it('has no routes at all in production or when the dashboard is off', function (string $env, array $config) {
    $this->inboxIn($env, $config);
    ($this->refreshRoutes)();

    $this->get('/notification-inbox')->assertNotFound();
})->with([
    'production' => ['production', []],
    'dashboard off' => ['local', ['dashboard.enabled' => false]],
]);

it('shows a loud banner when production is intercepted on purpose', function () {
    $this->inboxIn('production', ['environments' => ['production'], 'allow_production' => true]);
    ($this->refreshRoutes)();
    Gate::define('viewNotificationInbox', fn ($user = null) => true);

    $this->get('/notification-inbox')->assertOk()->assertSee('This is PRODUCTION and notifications are being caught');
});
