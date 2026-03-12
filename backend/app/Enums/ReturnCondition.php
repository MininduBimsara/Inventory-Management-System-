<?php

namespace App\Enums;

enum ReturnCondition: string
{
    case GOOD = 'good';
    case DAMAGED = 'damaged';
    case MISSING = 'missing';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function shouldRestoreStock(): bool
    {
        return $this === self::GOOD;
    }
}
