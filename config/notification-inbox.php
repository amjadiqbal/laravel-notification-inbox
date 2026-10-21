<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When the inbox is active, notifications on the intercepted channels are
    | captured and NOT sent. It is only ever active in the environments below.
    |
    */

    'enabled' => env('NOTIFICATION_INBOX_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environment allow-list
    |--------------------------------------------------------------------------
    |
    | The inbox does nothing outside these environments. Add staging with
    | NOTIFICATION_INBOX_ENVIRONMENTS=local,staging.
    |
    | "production" is ignored here unless allow_production is also true. That
    | second switch exists so production can never be caught by accident: a
    | production app that swallows its notifications loses real messages.
    |
    */

    'environments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOTIFICATION_INBOX_ENVIRONMENTS', 'local'))
    ))),

    'allow_production' => env('NOTIFICATION_INBOX_ALLOW_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | "intercept" is a list of channel names or channel classes to catch, or
    | ['*'] for every channel. "pass_through" always wins and is sent normally:
    | mail has its own catchers (Mailpit, Herd, Mailbox) and database stays in
    | your own database.
    |
    */

    'intercept' => ['*'],

    'pass_through' => ['mail', 'database'],

    /*
    |--------------------------------------------------------------------------
    | Extra channel definitions
    |--------------------------------------------------------------------------
    |
    | Popular channels are built in. Teach the inbox about your own channel:
    |
    |   App\Channels\WhatsAppChannel::class => [
    |       'label' => 'WhatsApp',
    |       'method' => 'toWhatsApp',   // the method your channel calls
    |       'route' => 'whatsapp',      // routeNotificationFor() key
    |       'preview' => 'sms',         // push, slack, teams, telegram, discord, sms, broadcast or json
    |   ],
    |
    */

    'channels' => [],

    /*
    |--------------------------------------------------------------------------
    | Storage and retention
    |--------------------------------------------------------------------------
    |
    | Captured notifications are written as JSON files, one per message, so
    | there is no migration to run. They can hold personal data (phone numbers,
    | one-time codes, reset links), so they are pruned after retention_hours and
    | capped at max_messages. Run notification-inbox:prune from the scheduler
    | as well if you capture on a shared staging server.
    |
    */

    'storage' => [
        'path' => env('NOTIFICATION_INBOX_PATH', storage_path('notification-inbox')),
    ],

    'retention_hours' => (int) env('NOTIFICATION_INBOX_RETENTION_HOURS', 72),

    'max_messages' => (int) env('NOTIFICATION_INBOX_MAX_MESSAGES', 1000),

    /*
    |--------------------------------------------------------------------------
    | Redaction
    |--------------------------------------------------------------------------
    |
    | Payload keys whose values are masked before they are written to disk
    | (case-insensitive). Webhook URLs used as routes are always shortened to
    | their host, because the path of a Slack, Teams or Discord webhook is a
    | secret.
    |
    */

    'redact' => [
        'password', 'secret', 'api_key', 'apikey', 'access_token', 'auth', 'auth_token',
        'authorization', 'p256dh', 'private_key', 'client_secret', 'webhook_url', 'webhookurl',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | The dashboard is behind the "viewNotificationInbox" gate. By default it
    | only opens in the local environment. To open it on staging, define the
    | gate yourself, for example in AppServiceProvider::boot():
    |
    |   Gate::define('viewNotificationInbox', fn ($user) => $user?->is_admin);
    |
    */

    'dashboard' => [
        'enabled' => env('NOTIFICATION_INBOX_DASHBOARD', true),
        'path' => env('NOTIFICATION_INBOX_DASHBOARD_PATH', 'notification-inbox'),
        'middleware' => ['web'],
        'poll_seconds' => 3,
    ],

];
