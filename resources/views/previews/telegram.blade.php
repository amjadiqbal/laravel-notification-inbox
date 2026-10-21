@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $bot = (string) config('app.name', 'Bot');
    $media = $p['media'];
    $src = $media && in_array($media['type'], ['photo', 'animation', 'sticker'], true) ? Markup::imageUrl($media['url']) : null;
@endphp
<div class="ni-stage" style="background: var(--ni-sunken)">
    <div class="pv-tg">
        <div class="pv-tg-head">
            <span class="pv-tg-avatar">{{ mb_strtoupper(mb_substr($bot, 0, 1)) }}</span>
            <span><b>{{ $bot }}</b><small>bot · chat {{ $p['chat'] ?? 'unknown' }}</small></span>
        </div>
        <div class="pv-tg-body">
            <div class="pv-tg-bubble">
                @if ($src)
                    <img src="{{ $src }}" alt="">
                @elseif ($media)
                    <div class="pv-tg-file"><i></i><span><b>{{ ucfirst(str_replace('_', ' ', $media['type'])) }}</b><br><small>{{ \Illuminate\Support\Str::limit(basename((string) parse_url($media['url'], PHP_URL_PATH)) ?: $media['url'], 40) }}</small></span></div>
                @endif
                @if ($p['location'])<div>📍 {{ $p['location']['lat'] }}, {{ $p['location']['lng'] }}</div>@endif
                @if ($p['contact'])<div>👤 {{ $p['contact'] }}</div>@endif
                @if ($p['poll'])
                    <div><b>{{ $p['poll']['question'] }}</b></div>
                    @foreach ($p['poll']['options'] as $option)<div>○ {{ $option }}</div>@endforeach
                @endif
                <div>{{ $p['html'] }}</div>
                <div class="pv-tg-time">{{ $n->capturedAt->format('H:i') }}</div>
            </div>
            @if ($p['keyboard'] !== [])
                <div class="pv-tg-keys">
                    @foreach ($p['keyboard'] as $row)
                        <div>@foreach ($row as $key)<span class="{{ $key['url'] ? 'link' : '' }}" title="{{ $key['url'] ?? 'callback' }}">{{ $key['label'] }}</span>@endforeach</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
<div class="pv-facts">
    <span>chat <b>{{ $p['chat'] ?? 'no chat id' }}</b></span>
    <span>parse mode <b>{{ data_get($n->payload, 'parse_mode') ?? 'none' }}</b></span>
    @if (mb_strlen($p['text']) > 4096)<span class="pv-warn">{{ mb_strlen($p['text']) }} characters: Telegram's limit is 4096 per message.</span>@endif
</div>
