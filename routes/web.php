<?php

use AmjadIqbal\NotificationInbox\Http\Controllers\InboxController;
use AmjadIqbal\NotificationInbox\Http\Middleware\Authorize;
use AmjadIqbal\NotificationInbox\NotificationInbox;
use Illuminate\Support\Facades\Route;

// No routes at all unless the inbox is active here and the dashboard is on: in
// production (without the override) the path is a plain 404.
if (! config('notification-inbox.dashboard.enabled') || ! app(NotificationInbox::class)->isActive()) {
    return;
}

Route::prefix(trim((string) config('notification-inbox.dashboard.path', 'notification-inbox'), '/'))
    ->middleware(array_merge((array) config('notification-inbox.dashboard.middleware', ['web']), [Authorize::class]))
    ->name('notification-inbox.')
    ->group(function (): void {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::get('/api/messages', [InboxController::class, 'messages'])->name('messages');
        Route::delete('/messages/{id}', [InboxController::class, 'destroy'])->name('destroy');
        Route::delete('/messages', [InboxController::class, 'clear'])->name('clear');
    });
