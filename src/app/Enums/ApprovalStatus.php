<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get the display label for this approval status
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get the color class for this approval status (MaryUI)
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'info',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'error',
        };
    }

    /**
     * Get the icon for this approval status
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'lucide-file-edit',
            self::PENDING => 'lucide-clock',
            self::APPROVED => 'lucide-check-circle',
            self::REJECTED => 'lucide-x-circle',
        };
    }

    /**
     * Create from string value
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'draft' => self::DRAFT,
            'pending' => self::PENDING,
            'approved' => self::APPROVED,
            'rejected' => self::REJECTED,
            default => throw new \ValueError("Unknown approval status: {$value}"),
        };
    }
}
