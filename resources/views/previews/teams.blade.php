@php use AmjadIqbal\NotificationInbox\Support\Markup; @endphp
<div class="ni-stage" style="background: var(--ni-sunken)">
    <div class="pv-teams">
        <div class="pv-teams-head">
            <span class="pv-teams-avatar">W</span>
            <span><b>Workflows</b> · {{ $n->capturedAt->format('g:i A') }}<br>via incoming webhook</span>
        </div>
        <div class="pv-teams-card">
            @if ($p['format'] === 'adaptive')
                @foreach ($p['body'] as $element)
                    @include('notification-inbox::previews.teams-element', ['el' => $element])
                @endforeach
                @if ($p['actions'] !== [])
                    <div class="pv-teams-actions">@foreach ($p['actions'] as $action)<span class="{{ $action['style'] ?? '' }}" title="{{ $action['url'] ?? '' }}">{{ $action['title'] ?? '' }}</span>@endforeach</div>
                @endif
            @else
                @php $card = $p['card']; @endphp
                <div style="border-top: 4px solid {{ preg_match('/^#?[0-9a-f]{6}$/i', (string) ($p['color'] ?? '')) ? '#'.ltrim($p['color'], '#') : '#5B5FC7' }}; margin: -14px -16px 10px; border-radius: 6px 6px 0 0"></div>
                @if ($p['title'])<div class="pv-teams-block t-large t-bolder">{{ $p['title'] }}</div>@endif
                @if (! empty($card['text']))<div class="pv-teams-block">{{ Markup::markdown((string) $card['text']) }}</div>@endif
                @foreach ((array) ($card['sections'] ?? []) as $section)
                    <div class="pv-teams-block sep">
                        @if (! empty($section['activityTitle']))<div class="t-bolder">{{ $section['activityTitle'] }}</div>@endif
                        @if (! empty($section['activitySubtitle']))<div class="t-subtle">{{ $section['activitySubtitle'] }}</div>@endif
                        @if (! empty($section['text']))<div>{{ Markup::markdown((string) $section['text']) }}</div>@endif
                        @if (! empty($section['facts']))
                            <dl class="pv-teams-facts">@foreach ($section['facts'] as $fact)<dt>{{ $fact['name'] ?? '' }}</dt><dd>{{ Markup::markdown((string) ($fact['value'] ?? '')) }}</dd>@endforeach</dl>
                        @endif
                    </div>
                @endforeach
                @if ($p['buttons'] !== [])
                    <div class="pv-teams-actions">@foreach ($p['buttons'] as $button)<span>{{ $button['label'] }}</span>@endforeach</div>
                @endif
            @endif
        </div>
    </div>
</div>
<div class="pv-facts">
    <span>to <b>{{ is_scalar($n->route) ? $n->route : 'webhook' }}</b></span>
    <span>{{ $p['format'] === 'adaptive' ? 'Adaptive Card' : 'MessageCard (legacy connector)' }}</span>
</div>
