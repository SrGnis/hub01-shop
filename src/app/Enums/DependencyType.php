<?php

namespace App\Enums;

enum DependencyType: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
    case EMBEDDED = 'embedded';

    /**
     * Get the background color class for this dependency type
     */
    public function bgColorClass(): string
    {
        return match ($this) {
            self::REQUIRED => 'bg-red-700',
            self::OPTIONAL => 'bg-blue-700',
            self::EMBEDDED => 'bg-green-700',
        };
    }

    /**
     * Get the display name for this dependency type
     */
    public function displayName(): string
    {
        return match ($this) {
            self::REQUIRED => 'Required',
            self::OPTIONAL => 'Optional',
            self::EMBEDDED => 'Embedded',
        };
    }

    /**
     * Create from string value
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'required' => self::REQUIRED,
            'optional' => self::OPTIONAL,
            'embedded' => self::EMBEDDED,
            default => throw new \ValueError("Unknown dependency type: {$value}"),
        };
    }
}
