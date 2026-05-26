@props([
    'title' => null,
])


<div {{ $attributes->merge(['class' => 'max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6']) }}>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <aside class="lg:col-span-3 lg:row-start-2">
            {{ $left ?? '' }}
        </aside>

        @if (isset($header) || filled($title))
            <header class="lg:col-span-12 lg:row-start-1">
                @if (isset($header))
                    {{ $header }}
                @else
                    <x-header :title="$title" />
                @endif
            </header>
        @endif

        <main class="lg:col-span-9 lg:col-start-4 lg:row-start-2 space-y-6">
            @if (isset($right))
                <div>
                    {{ $right }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @isset($footer)
        <footer>
            {{ $footer }}
        </footer>
    @endisset
</div>
