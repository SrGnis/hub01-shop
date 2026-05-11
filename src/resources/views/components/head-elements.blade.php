@php
    $headElements = config('head.elements', []);
@endphp

@foreach ($headElements as $element)
    @if (!($element['enabled'] ?? true) || !filled($element['html'] ?? null))
        @continue
    @endif
    {!! $element['html'] !!}
@endforeach

