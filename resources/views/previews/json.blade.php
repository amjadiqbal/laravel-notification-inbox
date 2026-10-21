<div class="ni-stage" style="justify-content: flex-start; display: block">
    @if ($p['title'])<h3 style="margin: 0 0 6px">{{ $p['title'] }}</h3>@endif
    @if ($p['text'] !== '')<p style="margin: 0 0 12px; white-space: pre-line">{{ $p['text'] }}</p>@endif
    <pre class="ni-code">{{ json_encode($n->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    <p class="ni-note" style="text-align: left">{{ $n->channelLabel }} has no look-alike preview yet. Map it to one with the "channels" option in config/notification-inbox.php.</p>
</div>
