@props(['content', 'pageName'])

<x-card>
    <x-markdown class="prose max-w-none dark:prose-invert">
        {!! $content !!}
    </x-markdown>
</x-card>
