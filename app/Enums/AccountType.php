<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case PRIVATE = 'private';
    case PROFESSIONAL = 'professional';

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
            self::PRIVATE      => 'Compte Privé',
            self::PROFESSIONAL => 'Compte Professionnel',
        };
    }
}
