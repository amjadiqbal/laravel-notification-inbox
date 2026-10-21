@php
    use AmjadIqbal\NotificationInbox\Support\Markup;
    $type = $el['type'] ?? '';
    $classes = trim(implode(' ', array_filter([
        'pv-teams-block',
        ! empty($el['separator']) ? 'sep' : null,
        isset($el['size']) ? 't-'.$el['size'] : null,
        isset($el['weight']) ? 't-'.$el['weight'] : null,
        ! empty($el['isSubtle']) ? 't-subtle' : null,
        isset($el['color']) ? 'c-'.strtolower((string) $el['color']) : null,
        ($el['style'] ?? null) === 'heading' ? 't-large t-bolder' : null,
    ])));
@endphp
@switch($type)
    @case('TextBlock')
        <div class="{{ $classes }}">{{ Markup::markdown((string) ($el['text'] ?? '')) }}</div>
        @break
    @case('RichTextBlock')
        <div class="{{ $classes }}">@foreach ($el['inlines'] ?? [] as $run){{ is_array($run) ? ($run['text'] ?? '') : $run }}@endforeach</div>
        @break
    @case('FactSet')
        <dl class="pv-teams-block pv-teams-facts">@foreach ($el['facts'] ?? [] as $fact)<dt>{{ $fact['title'] ?? '' }}</dt><dd>{{ Markup::markdown((string) ($fact['value'] ?? '')) }}</dd>@endforeach</dl>
        @break
    @case('Image')
        @if ($src = Markup::imageUrl($el['url'] ?? null))<div class="pv-teams-block"><img src="{{ $src }}" alt="{{ $el['altText'] ?? '' }}" @if (($el['style'] ?? '') === 'person') style="border-radius: 50%; width: 48px" @endif></div>@endif
        @break
    @case('ImageSet')
        <div class="pv-teams-block pv-teams-cols">@foreach ($el['images'] ?? [] as $img)@if ($src = Markup::imageUrl($img['url'] ?? null))<div><img src="{{ $src }}" alt=""></div>@endif @endforeach</div>
        @break
    @case('ColumnSet')
        <div class="pv-teams-block pv-teams-cols">
            @foreach ($el['columns'] ?? [] as $column)
                <div>@foreach ($column['items'] ?? [] as $child)@include('notification-inbox::previews.teams-element', ['el' => $child])@endforeach</div>
            @endforeach
        </div>
        @break
    @case('Container')
        <div class="{{ $classes }}">@foreach ($el['items'] ?? [] as $child)@include('notification-inbox::previews.teams-element', ['el' => $child])@endforeach</div>
        @break
    @case('ActionSet')
        <div class="pv-teams-actions">@foreach ($el['actions'] ?? [] as $action)<span class="{{ $action['style'] ?? '' }}">{{ $action['title'] ?? '' }}</span>@endforeach</div>
        @break
    @default
        <pre class="pv-teams-block" style="white-space: pre-wrap; font-size: 12px">{{ json_encode($el, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endswitch
