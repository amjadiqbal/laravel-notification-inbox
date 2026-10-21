<?php

namespace AmjadIqbal\NotificationInbox\Http\Controllers;

use AmjadIqbal\NotificationInbox\CapturedNotification;
use AmjadIqbal\NotificationInbox\NotificationInbox;
use AmjadIqbal\NotificationInbox\NotificationInboxServiceProvider;
use AmjadIqbal\NotificationInbox\Previews\PreviewFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class InboxController
{
    private const LIST_LIMIT = 500;

    public function __construct(private readonly NotificationInbox $inbox) {}

    public function index(Request $request): View
    {
        $all = $this->inbox->all();
        $channel = $request->string('channel')->toString() ?: null;
        $search = trim($request->string('q')->toString());

        $channels = [];
        foreach ($all as $item) {
            $channels[$item->channelKey] ??= ['label' => $item->channelLabel, 'preview' => $item->previewType, 'count' => 0];
            $channels[$item->channelKey]['count']++;
        }

        $items = array_values(array_filter($all, fn (CapturedNotification $item): bool => $this->matches($item, $channel, $search)));
        $selected = $request->filled('id') ? $this->inbox->find($request->string('id')->toString()) : ($items[0] ?? null);

        return view('notification-inbox::inbox', [
            'items' => array_slice($items, 0, self::LIST_LIMIT),
            'total' => count($all),
            'channels' => $channels,
            'channel' => $channel,
            'search' => $search,
            'selected' => $selected,
            'preview' => $selected ? PreviewFactory::make($selected->previewType) : null,
            'siblings' => $selected ? $this->siblings($all, $selected) : [],
            'latest' => $all[0]->id ?? '',
            'inbox' => $this->inbox,
            'version' => NotificationInboxServiceProvider::version(),
            'pollSeconds' => max(0, (int) config('notification-inbox.dashboard.poll_seconds', 3)),
        ]);
    }

    /**
     * Notifications captured after ?after=<id>, for the live list.
     */
    public function messages(Request $request): JsonResponse
    {
        $after = $request->string('after')->toString() ?: null;
        $items = $this->inbox->all($after);

        return response()->json([
            'total' => count($this->inbox->all()),
            'messages' => array_map(fn (CapturedNotification $item): array => self::row($item), array_slice($items, 0, 100)),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $deleted = $this->inbox->delete($id);

        return $request->expectsJson()
            ? response()->json(['deleted' => $deleted])
            : redirect()->route('notification-inbox.index', $request->only('channel', 'q'));
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $count = $this->inbox->clear();

        return $request->expectsJson()
            ? response()->json(['deleted' => $count])
            : redirect()->route('notification-inbox.index');
    }

    /**
     * @return array<string, mixed>
     */
    public static function row(CapturedNotification $item): array
    {
        return [
            'id' => $item->id,
            'channel' => $item->channelKey,
            'channel_label' => $item->channelLabel,
            'preview' => $item->previewType,
            'summary' => Str::limit($item->summary(), 90),
            'notification' => class_basename($item->notification),
            'to' => $item->notifiable['label'],
            'time' => $item->capturedAt->toIso8601String(),
            'error' => $item->error !== null,
        ];
    }

    private function matches(CapturedNotification $item, ?string $channel, string $search): bool
    {
        if ($channel !== null && $item->channelKey !== $channel) {
            return false;
        }

        if ($search === '') {
            return true;
        }

        $haystack = implode(' ', [
            $item->text(),
            $item->notification,
            $item->channelLabel,
            $item->notifiable['label'],
            json_encode($item->route) ?: '',
        ]);

        return Str::contains($haystack, $search, ignoreCase: true);
    }

    /**
     * The same notification on its other channels (Laravel gives each send one id).
     *
     * @param  list<CapturedNotification>  $all
     * @return list<CapturedNotification>
     */
    private function siblings(array $all, CapturedNotification $selected): array
    {
        if ($selected->notificationId === null) {
            return [];
        }

        return array_values(array_filter(
            $all,
            fn (CapturedNotification $item): bool => $item->notificationId === $selected->notificationId && $item->id !== $selected->id,
        ));
    }
}
