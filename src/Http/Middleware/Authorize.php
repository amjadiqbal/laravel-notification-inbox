<?php

namespace AmjadIqbal\NotificationInbox\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class Authorize
{
    public function handle(Request $request, Closure $next, string $ability = 'viewNotificationInbox'): Response
    {
        abort_unless(Gate::check($ability), 403);

        return $next($request);
    }
}
