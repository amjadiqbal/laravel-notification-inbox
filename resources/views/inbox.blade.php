@php
    /** @var \AmjadIqbal\NotificationInbox\CapturedNotification|null $selected */
    $channelColors = [
        'push' => '#8B5CF6', 'sms' => '#16A34A', 'slack' => '#611F69', 'teams' => '#5B5FC7',
        'telegram' => '#229ED9', 'discord' => '#5865F2', 'broadcast' => '#0284C7', 'json' => '#64748B',
    ];
    $query = fn (array $extra = []) => array_filter(array_merge(['channel' => $channel, 'q' => $search !== '' ? $search : null], $extra), fn ($v) => $v !== null && $v !== '');
    $indexUrl = route('notification-inbox.index');
    $canManage = \Illuminate\Support\Facades\Gate::check('manageNotificationInbox');
    $json = fn ($value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $selected ? $selected->summary().' · ' : '' }}Notification Inbox</title>
    <link rel="icon" href="data:image/svg+xml,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="#0F172A"/><path d="M5 18h7l2 3h4l2-3h7v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" fill="#8B5CF6"/><rect x="9" y="7" width="14" height="9" rx="2" fill="#F8FAFC"/></svg>') }}">
    <style>
        :root {
            --ni-bg: #F6F7FB; --ni-panel: #FFFFFF; --ni-sunken: #F1F3F9; --ni-border: #E3E6EF; --ni-border-strong: #CBD2E1;
            --ni-text: #0F172A; --ni-muted: #5B6478; --ni-faint: #8A93A6;
            --ni-accent: #6D28D9; --ni-accent-soft: #EDE9FE; --ni-accent-text: #5B21B6;
            --ni-danger: #DC2626; --ni-danger-soft: #FEE2E2; --ni-ok: #15803D;
            --ni-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 4px 16px rgba(15, 23, 42, .06);
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --ni-bg: #0B1020; --ni-panel: #121933; --ni-sunken: #0E1429; --ni-border: #222B4A; --ni-border-strong: #33406A;
                --ni-text: #E6E9F2; --ni-muted: #A2ABC2; --ni-faint: #737D98;
                --ni-accent: #A78BFA; --ni-accent-soft: #2A2257; --ni-accent-text: #C4B5FD;
                --ni-danger: #F87171; --ni-danger-soft: #3B1220; --ni-ok: #4ADE80;
                --ni-shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 6px 20px rgba(0, 0, 0, .25);
                color-scheme: dark;
            }
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--ni-bg); color: var(--ni-text); font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        a { color: inherit; }
        button, input, select { font: inherit; color: inherit; }
        code, pre, .ni-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

        .ni-top { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: 14px; padding: 10px 20px; background: var(--ni-panel); border-bottom: 1px solid var(--ni-border); }
        .ni-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-weight: 700; font-size: 15px; white-space: nowrap; }
        .ni-brand svg { width: 28px; height: 28px; flex: none; }
        .ni-status { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--ni-accent-soft); color: var(--ni-accent-text); font-size: 12px; font-weight: 600; white-space: nowrap; }
        .ni-status i { width: 7px; height: 7px; border-radius: 50%; background: var(--ni-accent); display: inline-block; }
        .ni-top .ni-spacer { flex: 1; }
        .ni-count { color: var(--ni-muted); font-size: 13px; white-space: nowrap; }
        .ni-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--ni-border-strong); background: var(--ni-panel); cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; }
        .ni-btn:hover { border-color: var(--ni-accent); color: var(--ni-accent-text); }
        .ni-btn.danger:hover { border-color: var(--ni-danger); color: var(--ni-danger); }

        .ni-banner { padding: 10px 20px; font-weight: 600; border-bottom: 1px solid var(--ni-border); }
        .ni-banner.danger { background: var(--ni-danger-soft); color: var(--ni-danger); }

        .ni-layout { display: grid; grid-template-columns: minmax(300px, 380px) 1fr; min-height: calc(100vh - 53px); }
        .ni-side { border-right: 1px solid var(--ni-border); background: var(--ni-panel); display: flex; flex-direction: column; min-width: 0; }
        .ni-filters { padding: 12px; border-bottom: 1px solid var(--ni-border); display: grid; gap: 10px; }
        .ni-search { width: 100%; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--ni-border-strong); background: var(--ni-sunken); }
        .ni-search:focus { outline: 2px solid var(--ni-accent); outline-offset: -1px; }
        .ni-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .ni-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 999px; border: 1px solid var(--ni-border); text-decoration: none; font-size: 12px; color: var(--ni-muted); }
        .ni-chip.on { border-color: var(--ni-accent); color: var(--ni-accent-text); background: var(--ni-accent-soft); }
        .ni-chip b { font-weight: 600; color: var(--ni-text); }
        .ni-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; display: inline-block; }

        .ni-list { list-style: none; margin: 0; padding: 0; overflow-y: auto; flex: 1; }
        .ni-list li a { display: grid; grid-template-columns: auto 1fr auto; gap: 2px 10px; padding: 11px 14px; border-bottom: 1px solid var(--ni-border); text-decoration: none; }
        .ni-list li a:hover { background: var(--ni-sunken); }
        .ni-list li a.on { background: var(--ni-accent-soft); box-shadow: inset 3px 0 0 var(--ni-accent); }
        .ni-list .ni-dot { margin-top: 7px; grid-row: span 2; }
        .ni-row-title { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ni-row-time { color: var(--ni-faint); font-size: 12px; white-space: nowrap; }
        .ni-row-meta { grid-column: 2 / span 2; color: var(--ni-muted); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ni-row-new { animation: ni-flash 1.6s ease-out; }
        @keyframes ni-flash { from { background: var(--ni-accent-soft); } to { background: transparent; } }
        .ni-err { color: var(--ni-danger); font-weight: 600; }

        .ni-main { min-width: 0; padding: 22px 28px 40px; }
        .ni-head h1 { margin: 0 0 6px; font-size: 20px; line-height: 1.3; word-break: break-word; }
        .ni-meta { display: flex; flex-wrap: wrap; gap: 6px 16px; color: var(--ni-muted); font-size: 13px; }
        .ni-meta span { white-space: nowrap; }
        .ni-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .ni-tabs { display: flex; gap: 2px; margin: 20px 0 16px; border-bottom: 1px solid var(--ni-border); }
        .ni-tab { padding: 8px 14px; border: 0; background: none; cursor: pointer; color: var(--ni-muted); font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .ni-tab[aria-selected="true"] { color: var(--ni-accent-text); border-bottom-color: var(--ni-accent); }
        .ni-panel[hidden] { display: none; }
        .ni-code { margin: 0; padding: 16px; border-radius: 10px; background: var(--ni-sunken); border: 1px solid var(--ni-border); overflow: auto; font-size: 12.5px; line-height: 1.55; max-height: 70vh; white-space: pre; }
        .ni-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ni-table th { text-align: left; color: var(--ni-muted); font-weight: 600; padding: 8px 12px 8px 0; vertical-align: top; width: 170px; white-space: nowrap; }
        .ni-table td { padding: 8px 0; border-bottom: 1px solid var(--ni-border); word-break: break-word; }
        .ni-table tr:first-child td { border-top: 1px solid var(--ni-border); }
        .ni-error { padding: 14px 16px; border-radius: 10px; background: var(--ni-danger-soft); color: var(--ni-danger); font-weight: 600; margin-bottom: 16px; word-break: break-word; }
        .ni-stage { display: flex; justify-content: center; padding: 28px 16px; border-radius: 14px; background: var(--ni-sunken); border: 1px solid var(--ni-border); overflow-x: auto; }
        .ni-note { color: var(--ni-muted); font-size: 12.5px; margin-top: 10px; text-align: center; }
        .ni-empty { max-width: 560px; margin: 8vh auto; text-align: center; color: var(--ni-muted); }
        .ni-empty h2 { color: var(--ni-text); margin: 14px 0 6px; font-size: 18px; }
        .ni-empty pre { text-align: left; }
        .ni-foot { color: var(--ni-faint); font-size: 12px; padding: 12px 14px; border-top: 1px solid var(--ni-border); }
        .ni-foot a { color: var(--ni-muted); }

        @media (max-width: 860px) {
            .ni-layout { grid-template-columns: 1fr; }
            .ni-side { border-right: 0; border-bottom: 1px solid var(--ni-border); }
            .ni-list { max-height: 40vh; }
            .ni-main { padding: 18px 16px 32px; }
            .ni-top { flex-wrap: wrap; padding: 10px 16px; }
            .ni-count { display: none; }
        }
    </style>
    @include('notification-inbox::previews.styles')
</head>
<body>
<header class="ni-top">
    <a class="ni-brand" href="{{ $indexUrl }}">
        <svg viewBox="0 0 512 512" aria-hidden="true"><rect width="512" height="512" rx="112" fill="#0F172A"/><path d="M72 300 L126 214 Q132 204 144 204 H368 Q380 204 386 214 L440 300" fill="#1E293B"/><rect x="152" y="160" width="208" height="150" rx="22" fill="#F8FAFC"/><rect x="174" y="184" width="40" height="40" rx="10" fill="#8B5CF6"/><rect x="228" y="190" width="110" height="13" rx="6.5" fill="#475569"/><path d="M72 300 H182 Q192 300 196 309 L208 334 Q213 344 224 344 H288 Q299 344 304 334 L316 309 Q320 300 330 300 H440 V404 a28 28 0 0 1 -28 28 H100 a28 28 0 0 1 -28 -28 Z" fill="#8B5CF6"/></svg>
        Notification Inbox
    </a>
    <span class="ni-status" title="Notifications on intercepted channels are captured here instead of being sent"><i></i>{{ $inbox->environment() }} · catching, nothing is sent</span>
    <span class="ni-spacer"></span>
    <span class="ni-count"><span data-ni-total>{{ $total }}</span> captured</span>
    @if ($total > 0 && $canManage)
        <form method="POST" action="{{ route('notification-inbox.clear') }}" onsubmit="return confirm('Delete all {{ $total }} captured notifications?')">
            @csrf @method('DELETE')
            <button class="ni-btn danger" type="submit">Clear all</button>
        </form>
    @endif
</header>

@if ($inbox->isInterceptingProduction())
    <div class="ni-banner danger" role="alert">This is PRODUCTION and notifications are being caught, not sent. Your users are not receiving them. Set NOTIFICATION_INBOX_ALLOW_PRODUCTION=false to stop.</div>
@endif

<div class="ni-layout">
    <aside class="ni-side">
        <form class="ni-filters" method="GET" action="{{ $indexUrl }}">
            @if ($channel)<input type="hidden" name="channel" value="{{ $channel }}">@endif
            <input class="ni-search" type="search" name="q" value="{{ $search }}" placeholder="Search text, recipient, class…" aria-label="Search captured notifications">
            @if ($channels !== [])
                <div class="ni-chips">
                    <a class="ni-chip {{ $channel === null ? 'on' : '' }}" href="{{ $indexUrl.'?'.http_build_query($query(['channel' => null])) }}">All <b>{{ $total }}</b></a>
                    @foreach ($channels as $key => $info)
                        <a class="ni-chip {{ $channel === $key ? 'on' : '' }}" href="{{ $indexUrl.'?'.http_build_query($query(['channel' => $key])) }}"><span class="ni-dot" style="background: {{ $channelColors[$info['preview']] ?? '#64748B' }}"></span>{{ $info['label'] }} <b>{{ $info['count'] }}</b></a>
                    @endforeach
                </div>
            @endif
        </form>

        <ul class="ni-list" data-ni-list>
            @foreach ($items as $item)
                <li>
                    <a href="{{ $indexUrl.'?'.http_build_query($query(['id' => $item->id])) }}" class="{{ $selected && $selected->id === $item->id ? 'on' : '' }}">
                        <span class="ni-dot" style="background: {{ $channelColors[$item->previewType] ?? '#64748B' }}"></span>
                        <span class="ni-row-title">@if ($item->error)<span class="ni-err">Error · </span>@endif{{ \Illuminate\Support\Str::limit($item->summary(), 90) }}</span>
                        <time class="ni-row-time" datetime="{{ $item->capturedAt->toIso8601String() }}">{{ $item->capturedAt->diffForHumans(short: true) }}</time>
                        <span class="ni-row-meta">{{ $item->channelLabel }} · {{ class_basename($item->notification) }} · {{ $item->notifiable['label'] ?? '' }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($items === [] && $total > 0)
            <p class="ni-foot">Nothing matches this filter.</p>
        @endif
        <div class="ni-foot">
            laravel-notification-inbox {{ $version }} · kept {{ (int) config('notification-inbox.retention_hours') }}h ·
            <a href="https://amjad.com.pk/contact?utm_source=dashboard&utm_medium=footer&utm_campaign=laravel-notification-inbox" target="_blank" rel="noopener">need a custom channel built?</a>
        </div>
    </aside>

    <main class="ni-main">
        @if ($selected)
            @php $data = $selected->preview(); @endphp
            <div class="ni-head">
                <h1>{{ $selected->summary() }}</h1>
                <div class="ni-meta">
                    <span><span class="ni-dot" style="background: {{ $channelColors[$selected->previewType] ?? '#64748B' }}"></span> {{ $selected->channelLabel }}</span>
                    <span class="ni-mono">{{ class_basename($selected->notification) }}</span>
                    <span>to {{ $selected->notifiable['label'] ?? 'unknown' }}</span>
                    <span title="{{ $selected->capturedAt->toDayDateTimeString() }}">{{ $selected->capturedAt->format('H:i:s') }} · {{ $selected->capturedAt->diffForHumans() }}</span>
                    @if ($selected->queued)<span>queued</span>@endif
                </div>
                @if ($siblings !== [])
                    <div class="ni-meta" style="margin-top: 6px">Also sent via:
                        @foreach ($siblings as $sibling)
                            <a href="{{ $indexUrl.'?'.http_build_query(['id' => $sibling->id]) }}">{{ $sibling->channelLabel }}</a>
                        @endforeach
                    </div>
                @endif
                <div class="ni-actions">
                    @if ($canManage)
                    <form method="POST" action="{{ route('notification-inbox.destroy', $selected->id) }}">
                        @csrf @method('DELETE')
                        @if ($channel)<input type="hidden" name="channel" value="{{ $channel }}">@endif
                        @if ($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                        <button class="ni-btn danger" type="submit">Delete</button>
                    </form>
                    @endif
                    <button class="ni-btn" type="button" data-ni-copy="{{ $json($selected->payload) }}">Copy payload</button>
                </div>
            </div>

            <div class="ni-tabs" role="tablist">
                <button class="ni-tab" role="tab" aria-selected="true" aria-controls="ni-preview" data-ni-tab>Preview</button>
                <button class="ni-tab" role="tab" aria-selected="false" aria-controls="ni-payload" data-ni-tab>Payload</button>
                <button class="ni-tab" role="tab" aria-selected="false" aria-controls="ni-details" data-ni-tab>Details</button>
            </div>

            <section class="ni-panel" id="ni-preview" role="tabpanel">
                @if ($selected->error)
                    <div class="ni-error">The {{ $selected->channelLabel }} channel could not build this message, so it would have failed in a real send too.<br><span class="ni-mono">{{ $selected->error }}</span></div>
                @else
                    @php
                        $routeKeys = $inbox->channels()->for($selected->channel)->routes;
                        $ownRecipient = data_get($selected->payload, 'chat_id') ?? data_get($selected->payload, 'to');
                    @endphp
                    @if ($selected->route === null && $routeKeys !== [] && $ownRecipient === null)
                        <div class="ni-error" style="font-weight: 500">No recipient: <span class="ni-mono">routeNotificationFor('{{ implode("') / ('", $routeKeys) }}')</span> returned nothing for {{ $selected->notifiable['label'] }}. Unless the message names its own recipient, the real {{ $selected->channelLabel }} channel would skip or reject it.</div>
                    @endif
                    @include($preview->view(), ['n' => $selected, 'p' => $data])
                @endif
            </section>

            <section class="ni-panel" id="ni-payload" role="tabpanel" hidden>
                <pre class="ni-code">{{ $json($selected->payload) }}</pre>
                <p class="ni-note">What {{ $selected->messageClass ? class_basename($selected->messageClass) : 'the channel' }} would have sent{{ $inbox->isFaked() ? '' : ', with secret-looking keys redacted before it was stored' }}.</p>
            </section>

            <section class="ni-panel" id="ni-details" role="tabpanel" hidden>
                <table class="ni-table">
                    <tr><th>Notification</th><td class="ni-mono">{{ $selected->notification }}</td></tr>
                    <tr><th>Channel</th><td class="ni-mono">{{ $selected->channel }}</td></tr>
                    <tr><th>Message class</th><td class="ni-mono">{{ $selected->messageClass ?? '—' }}</td></tr>
                    <tr><th>Notifiable</th><td><span class="ni-mono">{{ $selected->notifiable['type'] ?? '' }}</span>@if (($selected->notifiable['key'] ?? null) !== null) #{{ $selected->notifiable['key'] }}@endif</td></tr>
                    <tr><th>Route</th><td><pre class="ni-code" style="max-height: 200px">{{ $json($selected->route) }}</pre></td></tr>
                    @foreach ($selected->meta as $key => $value)
                        @if ($value !== null)<tr><th>{{ \Illuminate\Support\Str::headline($key) }}</th><td class="ni-mono">{{ is_scalar($value) ? $value : $json($value) }}</td></tr>@endif
                    @endforeach
                    <tr><th>Notification id</th><td class="ni-mono">{{ $selected->notificationId ?? '—' }}</td></tr>
                    <tr><th>Queued</th><td>{{ $selected->queued ? 'yes (ShouldQueue)' : 'no' }}</td></tr>
                    <tr><th>Environment</th><td>{{ $selected->environment }}</td></tr>
                    <tr><th>Captured at</th><td>{{ $selected->capturedAt->toDayDateTimeString() }} ({{ $selected->capturedAt->format('H:i:s.v') }})</td></tr>
                    <tr><th>Captured id</th><td class="ni-mono">{{ $selected->id }}</td></tr>
                </table>
            </section>
        @else
            <div class="ni-empty">
                <svg width="64" height="64" viewBox="0 0 512 512" aria-hidden="true"><rect width="512" height="512" rx="112" fill="#0F172A"/><path d="M72 300 H182 Q192 300 196 309 L208 334 Q213 344 224 344 H288 Q299 344 304 334 L316 309 Q320 300 330 300 H440 V404 a28 28 0 0 1 -28 28 H100 a28 28 0 0 1 -28 -28 Z" fill="#8B5CF6"/><path d="M72 300 L126 214 Q132 204 144 204 H368 Q380 204 386 214 L440 300" fill="none" stroke="#334155" stroke-width="10"/></svg>
                @if ($total > 0)
                    <h2>That notification is gone</h2>
                    <p>It was deleted or pruned. Pick another one from the list.</p>
                @else
                    <h2>No notifications caught yet</h2>
                    <p>Send any notification from your app. Push, Slack, Teams, Telegram, Discord, SMS and broadcast messages land here instead of being delivered. Mail and database notifications are sent as usual.</p>
                    <pre class="ni-code">$user->notify(new OrderShipped($order));</pre>
                    <p>This page updates by itself.</p>
                @endif
            </div>
        @endif
    </main>
</div>

<template data-ni-row>
    <li><a class="ni-row-new"><span class="ni-dot"></span><span class="ni-row-title"></span><time class="ni-row-time">now</time><span class="ni-row-meta"></span></a></li>
</template>

<script>
(() => {
    const colors = @json($channelColors);
    const tabs = document.querySelectorAll('[data-ni-tab]');
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((t) => {
            t.setAttribute('aria-selected', String(t === tab));
            document.getElementById(t.getAttribute('aria-controls')).hidden = t !== tab;
        });
    }));

    document.querySelectorAll('[data-ni-copy]').forEach((button) => button.addEventListener('click', async () => {
        const text = button.getAttribute('data-ni-copy');
        const done = (label) => { const old = button.textContent; button.textContent = label; setTimeout(() => button.textContent = old, 1500); };
        try {
            await navigator.clipboard.writeText(text);
            done('Copied');
        } catch (e) {
            const area = document.createElement('textarea');
            area.value = text; document.body.appendChild(area); area.select();
            const ok = document.execCommand && document.execCommand('copy');
            area.remove();
            done(ok ? 'Copied' : 'Press Ctrl+C');
        }
    }));

    const poll = {{ $pollSeconds }} * 1000;
    const list = document.querySelector('[data-ni-list]');
    const template = document.querySelector('[data-ni-row]');
    const filter = @json(['channel' => $channel, 'q' => $search]);
    let latest = @json($latest);
    const hadNone = latest === '';
    if (!poll || !list) return;

    const tick = async () => {
        try {
            const url = new URL(@json(route('notification-inbox.messages')), location.href);
            if (latest) url.searchParams.set('after', latest);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            document.querySelectorAll('[data-ni-total]').forEach((el) => el.textContent = data.total);
            if (!data.messages.length) return;
            latest = data.messages[0].id;
            if (hadNone) { location.reload(); return; }
            [...data.messages].reverse().forEach((m) => {
                if (filter.channel && filter.channel !== m.channel) return;
                if (filter.q && !(m.summary + ' ' + m.notification + ' ' + m.to + ' ' + m.channel_label).toLowerCase().includes(filter.q.toLowerCase())) return;
                const row = template.content.firstElementChild.cloneNode(true);
                const link = row.querySelector('a');
                const params = new URLSearchParams(location.search); params.set('id', m.id);
                link.href = '?' + params.toString();
                row.querySelector('.ni-dot').style.background = colors[m.preview] || '#64748B';
                row.querySelector('.ni-row-title').textContent = (m.error ? 'Error · ' : '') + m.summary;
                row.querySelector('.ni-row-meta').textContent = m.channel_label + ' · ' + m.notification + ' · ' + m.to;
                list.prepend(row);
            });
        } catch (e) { /* the next tick tries again */ }
    };
    setInterval(() => { if (!document.hidden) tick(); }, poll);
})();
</script>
</body>
</html>
