<?php

namespace App\Enums;

enum CollectionSystemType: string
{
    case FAVORITES = 'favorites';

    /**
     * Create from string value.
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'favorites' => self::FAVORITES,
            default => throw new \ValueError("Unknown collection system type: {$value}"),
        };
    }
}

