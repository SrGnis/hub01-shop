<?php

$enabled = (bool) config('enable_custom_head_elements', env('ENABLE_CUSTOM_HEAD_ELEMENTS', false));

if (! $enabled) {
    return ['elements' => []];
}

$allowedScriptDomains = config('head.allowed_script_domains');
if (! is_array($allowedScriptDomains)) {
    $allowedScriptDomains = array_map(
        static fn ($domain) => trim($domain),
        array_filter(
            explode(',', (string) env('HEAD_ELEMENTS_ALLOWED_SCRIPT_DOMAINS', '')),
            static fn ($domain) => trim($domain) !== ''
        )
    );
}

$headJson = config('head.json', env('HEAD_ELEMENTS_JSON', '[]'));

try {
    $elements = json_decode((string) $headJson, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($elements)) {
        $elements = [];
    }
} catch (JsonException $e) {
    $elements = [];
}

$normalized = [];
foreach ($elements as $item) {
    if (! is_array($item) || ! isset($item['key'], $item['type'], $item['attrs']) || ! is_array($item['attrs'])) {
        continue;
    }

    $type = $item['type'];
    $attrs = $item['attrs'];
    $enabled = $item['enabled'] ?? true;
    $allowDataAttrs = false;

    // Validate type and required attributes
    $valid = false;
    switch ($type) {
        case 'meta':
            $valid = isset($attrs['name'], $attrs['content']) || isset($attrs['property'], $attrs['content']);
            $allowedAttrs = ['name', 'content', 'property', 'charset'];
            // Note: http-equiv removed for security (could enable redirects or CSP bypasses)
            break;
        case 'link':
            $valid = isset($attrs['rel'], $attrs['href']);
            $allowedAttrs = ['rel', 'href', 'type', 'sizes', 'crossorigin', 'media', 'as', 'referrerpolicy'];
            $allowedRelValues = ['stylesheet', 'icon', 'apple-touch-icon', 'manifest', 'canonical', 'preconnect', 'dns-prefetch', 'alternate'];

            if (isset($attrs['rel']) && ! in_array($attrs['rel'], $allowedRelValues, true)) {
                $valid = false;
            }

            // Validate link href domain if applicable
            // Note: Relative URLs (no host) are allowed for local resources like favicons
            if (isset($attrs['href'])) {
                $hrefUrl = parse_url($attrs['href']);
                $hrefDomain = $hrefUrl['host'] ?? '';
                if (!empty($hrefDomain) && !in_array($hrefDomain, $allowedScriptDomains, true)) {
                    $valid = false;
                }
            }
            break;
        case 'script':
            $valid = isset($attrs['src']);
            $allowedAttrs = ['src', 'async', 'defer', 'crossorigin', 'integrity', 'type', 'nonce'];
            $allowDataAttrs = true;
            break;
        default:
            $valid = false;
    }

    if (! $valid) {
        continue;
    }

    // Filter allowed attributes
    $filteredAttrs = [];
    foreach ($attrs as $attr => $value) {
        if (in_array($attr, $allowedAttrs, true) || ($allowDataAttrs && str_starts_with($attr, 'data-'))) {
            $filteredAttrs[$attr] = $value;
        }
    }

    // Validate script src domain if applicable
    if ($type === 'script' && isset($filteredAttrs['src'])) {
        $srcUrl = parse_url($filteredAttrs['src'] ?? '');
        $srcDomain = $srcUrl['host'] ?? '';
        if (! in_array($srcDomain, $allowedScriptDomains, true)) {
            continue;
        }
    }

    $normalized[] = [
        'key' => $item['key'],
        'enabled' => $enabled,
        'type' => $type,
        'attrs' => $filteredAttrs,
    ];
}

return ['elements' => array_values($normalized)];
