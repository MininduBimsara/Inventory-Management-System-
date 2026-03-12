<?php

namespace App\Enums;

enum BorrowTransactionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case RETURNED = 'RETURNED';
    case OVERDUE = 'OVERDUE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
