<?php

namespace App\Enums;

enum ReleaseType: string
{
    case ALPHA = 'alpha';
    case BETA = 'beta';
    case RC = 'rc';
    case RELEASE = 'release';

    /**
     * Get the background color class for this release type
     */
    public function bgColorClass(): string
    {
        return match ($this) {
            self::ALPHA => 'bg-red-700',
            self::BETA => 'bg-amber-700',
            self::RC => 'bg-blue-700',
            self::RELEASE => 'bg-green-700',
        };
    }

    /**
     * Get the display name for this release type
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ALPHA => 'Alpha',
            self::BETA => 'Beta',
            self::RC => 'RC',
            self::RELEASE => 'Release',
        };
    }

    /**
     * Create from string value
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'alpha' => self::ALPHA,
            'beta' => self::BETA,
            'rc' => self::RC,
            'release' => self::RELEASE,
            default => throw new \ValueError("Unknown release type: {$value}"),
        };
    }
}
