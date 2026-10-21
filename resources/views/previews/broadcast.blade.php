@php
    $first = $p['channels'][0] ?? null;
    $echoName = $first ? preg_replace('/^(private|presence)-/', '', $first) : 'App.Models.User.1';
    $method = $first && str_starts_with($first, 'presence-') ? 'join' : ($first && ! str_starts_with($first, 'private-') ? 'channel' : 'private');
@endphp
<div class="ni-stage" style="justify-content: flex-start">
    <div class="pv-bc">
        <div class="pv-bc-row"><span>Channels</span><span>@forelse ($p['channels'] as $c)<span class="pv-bc-pill ni-mono">{{ $c }}</span>@empty<span class="pv-bc-pill">none</span>@endforelse</span></div>
        <div class="pv-bc-row"><span>Event</span><span class="ni-mono">{{ $p['event'] }}</span></div>
        @if ($p['connection'])<div class="pv-bc-row"><span>Connection</span><span class="ni-mono">{{ $p['connection'] }}</span></div>@endif
        <div class="pv-bc-row"><span>Echo</span><pre class="ni-code" style="margin: 0">Echo.{{ $method }}('{{ $echoName }}')
    .notification((notification) => {
        console.log(notification.type); // {{ data_get($n->payload, 'type') }}
    });</pre></div>
        <div class="pv-bc-row"><span>Data</span><pre class="ni-code" style="margin: 0">{{ json_encode($n->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
    </div>
</div>
