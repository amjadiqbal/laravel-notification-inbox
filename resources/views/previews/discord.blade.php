@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $name = $p['username'] ?: 'Bot';
@endphp
<div class="ni-stage" style="background: var(--ni-sunken)">
    <div class="pv-dc">
        <div class="pv-dc-msg">
            <div class="pv-dc-avatar">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</div>
            <div style="min-width: 0">
                <span class="pv-dc-name">{{ $name }}</span><span class="pv-dc-tag">APP</span><span class="pv-dc-time">Today at {{ $n->capturedAt->format('H:i') }}</span>
                @if ($p['content'] !== '')<div>{{ Markup::markdown($p['content']) }}</div>@endif
                @foreach ($p['embeds'] as $embed)
                    <div class="pv-dc-embed" @if ($embed['color']) style="border-left-color: {{ $embed['color'] }}" @endif>
                        <div style="min-width: 0">
                            @if ($embed['author'])<div style="font-size: 13px; font-weight: 600; color: #F2F3F5">{{ $embed['author'] }}</div>@endif
                            @if ($embed['title'])
                                @if ($embed['url'] && Markup::imageUrl($embed['url']))<a class="pv-dc-title" href="{{ $embed['url'] }}" target="_blank" rel="noopener noreferrer">{{ $embed['title'] }}</a>@else<div class="pv-dc-title">{{ $embed['title'] }}</div>@endif
                            @endif
                            @if ($embed['description'])<div style="font-size: 14px">{{ Markup::markdown($embed['description']) }}</div>@endif
                            @if ($embed['fields'] !== [])
                                <div class="pv-dc-fields">@foreach ($embed['fields'] as $field)<div class="{{ $field['inline'] ? 'inline' : '' }}"><b>{{ $field['name'] }}</b>{{ Markup::markdown($field['value']) }}</div>@endforeach</div>
                            @endif
                            @if ($src = Markup::imageUrl($embed['image']))<img class="pv-dc-image" src="{{ $src }}" alt="">@endif
                            @if ($embed['footer'])<div class="pv-dc-foot">{{ $embed['footer'] }}</div>@endif
                        </div>
                        @if ($src = Markup::imageUrl($embed['thumbnail']))<img class="pv-dc-thumb" src="{{ $src }}" alt="">@endif
                    </div>
                @endforeach
                @if ($p['buttons'] !== [])
                    <div class="pv-dc-buttons">@foreach ($p['buttons'] as $button)<span title="{{ $button['url'] ?? '' }}">{{ $button['label'] }}{{ $button['url'] ? ' ↗' : '' }}</span>@endforeach</div>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="pv-facts">
    <span>channel <b>{{ is_scalar($n->route) ? $n->route : 'unknown' }}</b></span>
    @if (mb_strlen($p['content']) > 2000)<span class="pv-warn">{{ mb_strlen($p['content']) }} characters: Discord's limit is 2000.</span>@endif
</div>
