@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $from = $p['from'] ?? null;
    $media = Markup::imageUrl($p['media'] ?? null);
@endphp
<div class="ni-stage">
    <div class="pv-phone">
        <div class="pv-screen pv-sms">
            <div class="pv-notch"></div>
            <div class="pv-statusbar"><span>{{ $n->capturedAt->format('H:i') }}</span><span>5G ▮▮▮</span></div>
            <div class="pv-sms-head">
                <div class="pv-avatar">{{ $from ? mb_strtoupper(mb_substr($from, 0, 1)) : '#' }}</div>
                <div>{{ $from ?? 'Default sender' }}</div>
                <small>Text Message · SMS</small>
            </div>
            <div class="pv-thread">
                <div class="pv-day">Today {{ $n->capturedAt->format('H:i') }}</div>
                <div class="pv-bubble">@if ($media)<img src="{{ $media }}" alt="">@endif{!! preg_replace_callback('#https?://[^\s<]+#', fn ($m) => '<a href="'.e(html_entity_decode($m[0])).'" target="_blank" rel="noopener noreferrer">'.$m[0].'</a>', e($p['text'])) !!}</div>
            </div>
        </div>
    </div>
</div>
<div class="pv-facts">
    <span>to <b>{{ $p['to'] ?? 'no route' }}</b></span>
    <span>from <b>{{ $from ?? 'channel default' }}</b></span>
    <span><b>{{ $p['characters'] }}</b> chars · {{ $p['encoding'] }}</span>
    <span class="{{ $p['segments'] > 1 ? 'pv-warn' : '' }}"><b>{{ $p['segments'] }}</b> {{ \Illuminate\Support\Str::plural('segment', $p['segments']) }}{{ $p['segments'] > 1 ? ' (billed as '.$p['segments'].' messages)' : '' }}</span>
    @if ($p['encoding'] === 'UCS-2')<span>An emoji or special character switched this to UCS-2 (70 chars per segment).</span>@endif
    @if ($p['text'] === '')<span class="pv-warn">Empty message body.</span>@endif
</div>
