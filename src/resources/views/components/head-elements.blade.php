@php
    $headElements = config('head.elements', []);
@endphp

@foreach ($headElements as $element)
    @if (!($element['enabled'] ?? true))
        @continue
    @endif

    @switch($element['type'])
        @case('meta')
            <meta
                @foreach ($element['attrs'] as $attr => $value)
                    @include('components.attribute', ['attr' => $attr, 'value' => $value])
                @endforeach
            >
            @break

        @case('link')
            <link
                @foreach ($element['attrs'] as $attr => $value)
                    @include('components.attribute', ['attr' => $attr, 'value' => $value])
                @endforeach
            >
            @break

        @case('script')
            <script
                @foreach ($element['attrs'] as $attr => $value)
                    @include('components.attribute', ['attr' => $attr, 'value' => $value])
                @endforeach
            ></script>
            @break
    @endswitch
@endforeach

