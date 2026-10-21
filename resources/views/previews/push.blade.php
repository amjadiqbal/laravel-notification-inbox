@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $app = (string) config('app.name', 'App');
    $icon = Markup::imageUrl($p['icon'] ?? null);
    $image = Markup::imageUrl($p['image'] ?? null);
    $time = $n->capturedAt;
@endphp
<div class="ni-stage">
    <div class="pv-phone">
        <div class="pv-screen pv-lock">
            <div class="pv-notch"></div>
            <div class="pv-statusbar"><span>{{ $time->format('H:i') }}</span><span>5G ▮▮▮</span></div>
            <div class="pv-clock"><b>{{ $time->format('H:i') }}</b><span>{{ $time->format('l j F') }}</span></div>
            <div class="pv-alert">
                <div class="pv-alert-top">
                    <span class="pv-app">@if ($icon)<img src="{{ $icon }}" alt="">@else{{ mb_strtoupper(mb_substr($app, 0, 1)) }}@endif</span>
                    <span>{{ \Illuminate\Support\Str::upper($app) }}</span>
                    <span class="pv-when">now</span>
                </div>
                @if ($p['title'])<h4>{{ $p['title'] }}</h4>@endif
                @if ($p['subtitle'] ?? null)<h5>{{ $p['subtitle'] }}</h5>@endif
                @if ($p['text'] !== '')<p>{{ $p['text'] }}</p>@endif
                @if ($image)<img class="pv-alert-image" src="{{ $image }}" alt="">@endif
                @if ($p['buttons'] !== [])
                    <div class="pv-alert-actions">@foreach ($p['buttons'] as $button)<span>{{ $button['label'] }}</span>@endforeach</div>
                @endif
            </div>
            @if (($p['badge'] ?? null) !== null)
                <div class="pv-badge"><span>App icon badge</span><span class="pv-icon"><i>{{ $p['badge'] }}</i></span></div>
            @endif
        </div>
    </div>
</div>
<div class="pv-facts">
    <span><b>{{ $p['platform'] }}</b></span>
    <span>to {{ is_scalar($n->route) ? \Illuminate\Support\Str::limit((string) $n->route, 40) : (is_array($n->route) ? count($n->route).' device(s)' : 'no route') }}</span>
    @if (! $p['title'] && $p['text'] === '')<span class="pv-warn">No title or body: this would arrive as a silent or empty push.</span>@endif
</div>
@if (! empty($p['data']))
    <p class="ni-note">Data payload</p>
    <pre class="ni-code">{{ json_encode($p['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
@endif
