<div class="container mx-auto max-w-7xl">
    <header class="mb-6">
        {{ $header ?? '' }}
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-24 gap-4">
        <aside class="lg:col-span-5">
            {{ $left ?? '' }}
        </aside>

        <section class="lg:col-span-19">
            {{ $right ?? '' }}
        </section>
    </div>

    <footer class="mt-6">
        {{ $footer ?? '' }}
    </footer>
</div>
