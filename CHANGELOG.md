# Changelog

All notable changes to `laravel-notification-inbox` are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Catches every non-mail notification in the allowed environments (default: local) through a
  `NotificationSending` listener, so nothing is sent and no channel driver or credentials are touched.
- Built-in channel map for Laravel's Slack, Vonage and broadcast channels and for
  laravel-notification-channels web push, FCM, APNs, Expo, OneSignal, Pushover, Telegram, Microsoft Teams,
  Discord, Twilio, MessageBird, Plivo, AWS SNS and Google Chat; unknown channels by convention or config.
- Inbox dashboard with look-alike previews: phone push alert, SMS thread with segment count, Slack Block
  Kit and legacy attachments, Teams Adaptive Card and MessageCard, Telegram bubble with inline keyboard,
  Discord embeds, broadcast event with its Echo listener; payload and details tabs, live updates, filters,
  search, delete and clear.
- `NotificationInbox::fake()` and assertions on real payloads: `assertCaptured`, `assertCapturedOn`,
  `assertCapturedFor`, `assertCapturedContaining`, `assertNoCaptureErrors` and more.
- Safety: environment allow-list, production only with a second switch plus loud warnings, a
  `viewNotificationInbox` gate (local only by default), fail-closed capture.
- Personal data: one JSON file per message with a `.gitignore`, redaction of secret keys and webhook
  paths, no notifiable attributes, retention and size limits, `notification-inbox:prune` and
  `notification-inbox:clear`.
- `php artisan about` section with the current status.
