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
        return match ($this) {
            self::DRAFT     => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED  => 'Archivé',
        };
    }

    /**
     * Get the color for the enum value
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::PUBLISHED => 'green',
            self::ARCHIVED  => 'red',
        };
    }
}
