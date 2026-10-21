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
    | one-time codes, reset links). About one capture in fifty also prunes what
    | is older than retention_hours and beyond max_messages, so a quiet inbox
    | is not pruned on its own: schedule notification-inbox:prune on a shared
    | staging server.
    |
    */

    'storage' => [
        'path' => env('NOTIFICATION_INBOX_PATH', storage_path('notification-inbox')),
    ],

    'retention_hours' => (int) env('NOTIFICATION_INBOX_RETENTION_HOURS', 72),

    'max_messages' => (int) env('NOTIFICATION_INBOX_MAX_MESSAGES', 1000),

    // Chance that a capture also prunes: [1, 50] is about one in fifty, [0, 1] never.
    'prune_lottery' => [1, 50],

    /*
    |--------------------------------------------------------------------------
    | Redaction
    |--------------------------------------------------------------------------
    |
    | Applied before anything is written to disk (NotificationInbox::fake()
    | keeps everything in memory, unredacted, for assertions).
    |
    | "redact" masks keys with exactly these names; "redact_containing" masks
    | any key that contains one of these words (otp_code, reset_token,
    | client_secret...). All case-insensitive. Webhook URLs used as routes are
    | always shortened to their host, because the path of a Slack, Teams or
    | Discord webhook is a secret.
    |
    | "mask_codes_in_text" also replaces 4 to 8 digit numbers inside message
    | text ("Your code is 482913") so one-time codes are not stored. It is off
    | by default because it also hides order numbers and prices in previews;
    | turn it on for shared staging servers.
    |
    */

    'redact' => [
        'auth', 'authorization', 'p256dh', 'api_key', 'apikey',
    ],

    'redact_containing' => [
        'password', 'passcode', 'secret', 'token', 'otp', 'code', 'private_key', 'webhook',
    ],

    'mask_codes_in_text' => env('NOTIFICATION_INBOX_MASK_CODES', false),

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
