<?php

declare(strict_types=1);

namespace App\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get all enum values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for the enum value
     */
    public function label(): string
    {
        return __("enums.post_status.{$this->value}");
    }

    /**
     * Get the color for the enum value
     */
    public function color(): string
    {
        return __("enums.post_status_colors.{$this->value}");
    }
}
