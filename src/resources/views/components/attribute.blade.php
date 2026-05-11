@if (is_bool($value))
    {{ $attr }}
@else
    {{ $attr }}="{{ $value }}"
@endif
