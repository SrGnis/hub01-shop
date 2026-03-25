<?php

namespace App\Enums;

enum CollectionVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case HIDDEN = 'hidden';

    /**
     * Create from string value.
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'public' => self::PUBLIC,
            'private' => self::PRIVATE,
            'hidden' => self::HIDDEN,
            default => throw new \ValueError("Unknown collection visibility: {$value}"),
        };
    }
}

