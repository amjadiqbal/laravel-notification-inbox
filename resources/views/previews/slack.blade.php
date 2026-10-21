@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $text = fn ($obj) => is_array($obj) ? (($obj['type'] ?? 'plain_text') === 'mrkdwn' ? Markup::slack((string) ($obj['text'] ?? '')) : nl2br(e((string) ($obj['text'] ?? '')), false)) : '';
    $name = $p['username'] ?: 'App';
@endphp
<div class="ni-stage" style="background: var(--ni-sunken)">
    <div class="pv-slack">
        <div class="pv-slack-msg">
            <div class="pv-slack-avatar">{{ $p['icon'] && str_starts_with($p['icon'], ':') ? '☺' : mb_strtoupper(mb_substr($name, 0, 1)) }}</div>
            <div>
                <span class="pv-slack-name">{{ $name }}</span><span class="pv-slack-tag">APP</span><span class="pv-slack-time">{{ $n->capturedAt->format('g:i A') }}</span>
                @if ($p['blocks'] === [] && $p['fallback'])
                    <div>{{ Markup::slack($p['fallback']) }}</div>
                @endif
                @foreach ($p['blocks'] as $block)
                    @switch($block['type'] ?? '')
                        @case('header')
                            <h3>{{ $block['text']['text'] ?? '' }}</h3>
                            @break
                        @case('section')
                            <div class="pv-slack-section">
                                <div>
                                    @if (isset($block['text']))<div>{!! $text($block['text']) !!}</div>@endif
                                    @if (! empty($block['fields']))
                                        <div class="pv-slack-fields">@foreach ($block['fields'] as $field)<div>{!! $text($field) !!}</div>@endforeach</div>
                                    @endif
                                </div>
                                @php $accessory = $block['accessory'] ?? null; @endphp
                                @if (($accessory['type'] ?? null) === 'image' && ($src = Markup::imageUrl($accessory['image_url'] ?? null)))
                                    <img class="pv-slack-accessory" src="{{ $src }}" alt="{{ $accessory['alt_text'] ?? '' }}">
                                @elseif (($accessory['type'] ?? null) === 'button')
                                    <div class="pv-slack-buttons"><span class="{{ $accessory['style'] ?? '' }}">{{ $accessory['text']['text'] ?? '' }}</span></div>
                                @endif
                            </div>
                            @break
                        @case('context')
                            <div class="pv-slack-context">
                                @foreach ($block['elements'] ?? [] as $element)
                                    @if (($element['type'] ?? '') === 'image' && ($src = Markup::imageUrl($element['image_url'] ?? null)))<img src="{{ $src }}" alt="">@else<span>{!! $text($element) !!}</span>@endif
                                @endforeach
                            </div>
                            @break
                        @case('divider')
                            <hr>
                            @break
                        @case('image')
                            @if (isset($block['title']))<div class="pv-slack-foot">{{ $block['title']['text'] ?? '' }}</div>@endif
                            @if ($src = Markup::imageUrl($block['image_url'] ?? null))<img class="pv-slack-img" src="{{ $src }}" alt="{{ $block['alt_text'] ?? '' }}">@endif
                            @break
                        @case('actions')
                            <div class="pv-slack-buttons">
                                @foreach ($block['elements'] ?? [] as $element)
                                    <span class="{{ $element['style'] ?? '' }}" title="{{ $element['url'] ?? ($element['action_id'] ?? '') }}">{{ $element['text']['text'] ?? ($element['placeholder']['text'] ?? ucfirst(str_replace('_', ' ', (string) ($element['type'] ?? '')))) }}</span>
                                @endforeach
                            </div>
                            @break
                        @default
                            <pre>{{ json_encode($block, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endswitch
                @endforeach
                @foreach ($p['attachments'] as $a)
                    <div class="pv-slack-att" style="border-left-color: {{ $a['color'] }}">
                        @if ($a['pretext'])<div>{{ Markup::slack($a['pretext']) }}</div>@endif
                        @if ($a['title'])<div>@if ($a['url']){!! Markup::link($a['url'], e($a['title'])) !!}@else<b>{{ $a['title'] }}</b>@endif</div>@endif
                        @if ($a['text'])<div>{{ Markup::slack($a['text']) }}</div>@endif
                        @if ($a['fields'] !== [])
                            <div class="pv-slack-fields">@foreach ($a['fields'] as $f)<div><b>{{ $f['title'] }}</b><br>{{ Markup::slack($f['value']) }}</div>@endforeach</div>
                        @endif
                        @if ($src = Markup::imageUrl($a['image']))<img class="pv-slack-img" src="{{ $src }}" alt="">@endif
                        @if ($a['footer'])<div class="pv-slack-foot">{{ $a['footer'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="pv-facts">
    <span>to <b>{{ is_scalar($n->route) ? $n->route : 'route' }}</b></span>
    @if ($p['channel'])<span>channel <b>{{ $p['channel'] }}</b></span>@endif
    <span>{{ $p['blocks'] !== [] ? count($p['blocks']).' Block Kit blocks' : 'legacy message format' }}</span>
    @if ($p['blocks'] !== [] && ! $p['fallback'])<span class="pv-warn">No fallback text: notifications and screen readers will show nothing.</span>@endif
</div>
