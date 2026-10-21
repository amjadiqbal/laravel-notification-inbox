<?php

namespace AmjadIqbal\NotificationInbox\Tests;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\NotificationInbox;
use AmjadIqbal\NotificationInbox\NotificationInboxServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $inboxPath;

    protected function setUp(): void
    {
        $this->inboxPath = sys_get_temp_dir().'/notification-inbox-tests-'.bin2hex(random_bytes(6));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->inboxPath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [NotificationInboxServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('notification-inbox.storage.path', $this->inboxPath);
    }

    /**
     * Pretend the app runs in $environment with these inbox settings, and get a fresh inbox.
     *
     * @param  array<string, mixed>  $config
     */
    protected function inboxIn(string $environment, array $config = []): NotificationInbox
    {
        $this->app['env'] = $environment;

        foreach ($config as $key => $value) {
            $this->app['config']->set('notification-inbox.'.$key, $value);
        }

        $this->app->forgetInstance(NotificationInbox::class);

        return $this->app->make(NotificationInbox::class);
    }

    /**
     * Real payloads captured from the channel packages (see tests/Fixtures/captured.json).
     */
    public static function fixture(string $channel): CapturedNotification
    {
        /** @var array<string, array<string, mixed>> $all */
        $all = json_decode((string) file_get_contents(__DIR__.'/Fixtures/captured.json'), true);

        return CapturedNotification::fromArray($all[$channel]);
    }
}
