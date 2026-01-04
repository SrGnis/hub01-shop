@php
    $links = config('footer-links.links', []);
@endphp

@if(!empty($links))
    <div class="flex gap-5">
        @foreach($links as $section => $items)
            <div class="text-center">
                <header class="footer-title">{{ $section }}</header>
                @foreach($items as $label => $url)
                <div>
                    <a href="{{ $url }}" class="link link-hover">{{ $label }}</a>
                </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
