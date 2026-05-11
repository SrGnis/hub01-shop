<?php

if (! (bool) env('ENABLE_CUSTOM_HEAD_ELEMENTS', false)) {
    return ['elements' => []];
}

$elements = json_decode((string) env('HEAD_ELEMENTS_JSON', '[]'), true, 512, JSON_THROW_ON_ERROR);

if (! is_array($elements)) {
    $elements = [];
}

return [
    'elements' => array_values(array_filter(
        $elements,
        static fn ($item) => is_array($item) && array_key_exists('html', $item) && array_key_exists('key', $item)
    )),
];
