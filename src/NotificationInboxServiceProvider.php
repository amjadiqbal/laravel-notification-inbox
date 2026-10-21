<?php

namespace AmjadIqbal\NotificationInbox;

use AmjadIqbal\NotificationInbox\Channels\ChannelRegistry;
use AmjadIqbal\NotificationInbox\Console\ClearCommand;
use AmjadIqbal\NotificationInbox\Console\PruneCommand;
use AmjadIqbal\NotificationInbox\Storage\FileStore;
use AmjadIqbal\NotificationInbox\Support\PayloadSerializer;
use AmjadIqbal\NotificationInbox\Support\Redactor;
use Composer\InstalledVersions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class NotificationInboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notification-inbox.php', 'notification-inbox');

        $this->app->singleton(NotificationInbox::class, function (Application $app): NotificationInbox {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('notification-inbox', []);

            return new NotificationInbox(
                store: new FileStore(
                    (string) ($config['storage']['path'] ?? $app->storagePath('notification-inbox')),
                    new Redactor(
                        array_values(array_map('strval', (array) ($config['redact'] ?? []))),
                        array_values(array_map('strval', (array) ($config['redact_containing'] ?? []))),
                        (bool) ($config['mask_codes_in_text'] ?? false),
                    ),
                ),
                channels: new ChannelRegistry((array) ($config['channels'] ?? [])),
                serializer: new PayloadSerializer,
                config: $config,
                environment: (string) $app->environment(),
            );
        });

        // Registered here, not in boot(): NotificationSending is dispatched with until(), so the first
        // listener to return non-null decides. Registering during register() puts the inbox ahead of
        // every listener added in any provider's boot() and the app's own listeners.
        $this->app['events']->listen(NotificationSending::class, [Interceptor::class, 'handle']);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'notification-inbox');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/notification-inbox.php' => config_path('notification-inbox.php'),
            ], 'notification-inbox-config');

            $this->commands([PruneCommand::class, ClearCommand::class]);
        }

        // Apps open the dashboard elsewhere by defining this gate themselves (theirs boots later and wins).
        if (! Gate::has('viewNotificationInbox')) {
            Gate::define('viewNotificationInbox', fn ($user = null): bool => $this->app->environment('local'));
        }

        // Deleting is a separate right: someone allowed to look on staging is not automatically allowed to wipe.
        if (! Gate::has('manageNotificationInbox')) {
            Gate::define('manageNotificationInbox', fn ($user = null): bool => $this->app->environment('local'));
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->warnAboutProduction();

        AboutCommand::add('Notification Inbox', fn (): array => [
            'Version' => self::version(),
            'Status' => $this->status(),
            'Dashboard' => $this->dashboardEnabled() ? '/'.trim((string) config('notification-inbox.dashboard.path'), '/') : 'off',
            'Support' => 'https://amjad.com.pk/contact?utm_source=artisan&utm_medium=about&utm_campaign=laravel-notification-inbox',
        ]);
    }

    public function dashboardEnabled(): bool
    {
        return (bool) config('notification-inbox.dashboard.enabled') && $this->app->make(NotificationInbox::class)->isActive();
    }

    private function status(): string
    {
        $inbox = $this->app->make(NotificationInbox::class);

        return match (true) {
            $inbox->isInterceptingProduction() => '<fg=red;options=bold>CATCHING PRODUCTION NOTIFICATIONS (nothing is sent)</>',
            $inbox->isActive() => '<fg=yellow;options=bold>catching notifications (not sent)</>',
            $inbox->productionListedButIgnored() => 'off (production is listed but NOTIFICATION_INBOX_ALLOW_PRODUCTION is false)',
            default => 'off in this environment',
        };
    }

    private function warnAboutProduction(): void
    {
        $inbox = $this->app->make(NotificationInbox::class);

        if ($inbox->isInterceptingProduction()) {
            Log::warning('Notification Inbox is CATCHING notifications in PRODUCTION: none are being sent. Set NOTIFICATION_INBOX_ALLOW_PRODUCTION=false to stop.');
        } elseif ($inbox->productionListedButIgnored() && $this->app->runningInConsole()) {
            // Console only (deploys, artisan): a misconfiguration worth seeing, not worth a line per request.
            Log::warning('Notification Inbox: "production" is in NOTIFICATION_INBOX_ENVIRONMENTS but NOTIFICATION_INBOX_ALLOW_PRODUCTION is false, so it is ignored and notifications are sent normally.');
        }
    }

    public static function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('amjadiqbal/laravel-notification-inbox') ?? 'dev';
        } catch (Throwable) {
            return 'dev';
        }
    }
}
